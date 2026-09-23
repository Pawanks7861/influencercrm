<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignStoreRequest;
use App\Http\Requests\CampaignUpdateRequest;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Influencer;
use App\Services\Campaign\CampaignService;
use App\Services\Campaign\CampaignVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function __construct(
        protected CampaignService $campaignService,
        protected CampaignVisibilityService $visibilityService
    ) {
        $this->middleware('permission:campaigns.view')->only(['index', 'show']);
        $this->middleware('permission:campaigns.create')->only(['create', 'store']);
        $this->middleware('permission:campaigns.edit')->only(['edit', 'update', 'shareWithClient', 'unshareWithClient', 'updateVisibility']);
        $this->middleware('permission:campaigns.delete')->only(['destroy']);
    }

    public function index(Request $request): Response|JsonResponse
    {
        if ($request->wantsJson() || $request->boolean('select')) {
            $campaigns = Campaign::query()
                ->when($request->get('search'), function ($q, $search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('campaign_name', 'like', "%{$search}%")
                            ->orWhere('brand_name', 'like', "%{$search}%");
                    });
                })
                ->orderBy('campaign_name')
                ->limit(50)
                ->get(['id', 'campaign_name', 'brand_name', 'status', 'client_id', 'campaign_type']);

            return response()->json($campaigns);
        }

        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowed = ['campaign_name', 'start_date', 'deadline', 'status', 'created_at', 'campaign_type'];

        if (! in_array($sort, $allowed, true)) {
            $sort = 'created_at';
        }

        $query = Campaign::query()->with(['client', 'campaignInfluencers']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('campaign_name', 'like', "%{$search}%")
                    ->orWhere('brand_name', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($campaignType = $request->get('campaign_type')) {
            $query->where('campaign_type', $campaignType);
        }

        $campaigns = $query->orderBy($sort, $direction)
            ->paginate($request->integer('per_page', 15))
            ->withQueryString()
            ->through(function (Campaign $campaign) {
                $campaign->setAttribute('totals', $this->campaignService->totals($campaign));

                return $campaign;
            });

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'filters' => $request->only(['search', 'status', 'client_id', 'campaign_type', 'sort', 'direction', 'per_page']),
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'name', 'company_name']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Campaigns/Create', [
            'clients' => Client::query()->where('status', 'active')->orderBy('company_name')->get(['id', 'name', 'company_name']),
            'influencers' => Influencer::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'instagram_username', 'influencer_type', 'default_price']),
            'prefillClientId' => $request->integer('client_id') ?: null,
        ]);
    }

    public function store(CampaignStoreRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['influencers', 'deliverables']);
        $campaign = $this->campaignService->create(
            $data,
            $request->input('influencers', []),
            $request->input('deliverables', [])
        );

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    public function show(Campaign $campaign): Response
    {
        $campaign->load([
            'client',
            'campaignInfluencers.influencer',
            'campaignInfluencers.deliverables',
            'campaignInfluencers.payments',
            'deliverables',
            'notes.creator',
            'payments.influencer',
            'clientVisibility.sharedByUser:id,name',
        ]);

        $visibility = $this->visibilityService->ensure($campaign);

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign,
            'totals' => $this->campaignService->totals($campaign),
            'clientVisibility' => $visibility->fresh(['sharedByUser:id,name']),
        ]);
    }

    public function edit(Campaign $campaign): Response
    {
        $campaign->load(['campaignInfluencers.influencer', 'client', 'deliverables']);

        return Inertia::render('Campaigns/Edit', [
            'campaign' => $campaign,
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'name', 'company_name']),
            'influencers' => Influencer::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'instagram_username', 'influencer_type', 'default_price']),
        ]);
    }

    public function update(CampaignUpdateRequest $request, Campaign $campaign): RedirectResponse
    {
        $data = $request->safe()->except(['influencers', 'deliverables']);
        $influencers = $request->has('influencers') ? $request->input('influencers', []) : null;
        $deliverables = $request->has('deliverables') ? $request->input('deliverables', []) : null;

        $this->campaignService->update($campaign, $data, $influencers, $deliverables);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        try {
            $this->campaignService->delete($campaign);
        } catch (ValidationException $e) {
            return redirect()
                ->route('campaigns.index')
                ->with('error', collect($e->errors())->flatten()->first() ?: 'Unable to archive campaign.');
        }

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Campaign archived successfully.');
    }

    public function shareWithClient(Request $request, Campaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'show_budget' => ['sometimes', 'boolean'],
            'show_deliverables' => ['sometimes', 'boolean'],
            'show_influencers' => ['sometimes', 'boolean'],
            'show_posting_dates' => ['sometimes', 'boolean'],
            'show_content_links' => ['sometimes', 'boolean'],
            'show_payment_summary' => ['sometimes', 'boolean'],
            'show_notes' => ['sometimes', 'boolean'],
        ]);

        $this->visibilityService->share($campaign, $request->user(), $validated);

        return back()->with('success', 'Campaign shared with client.');
    }

    public function unshareWithClient(Campaign $campaign): RedirectResponse
    {
        $this->visibilityService->unshare($campaign);

        return back()->with('success', 'Campaign unshared from client.');
    }

    public function updateVisibility(Request $request, Campaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'visible_to_client' => ['sometimes', 'boolean'],
            'show_budget' => ['sometimes', 'boolean'],
            'show_deliverables' => ['sometimes', 'boolean'],
            'show_influencers' => ['sometimes', 'boolean'],
            'show_posting_dates' => ['sometimes', 'boolean'],
            'show_content_links' => ['sometimes', 'boolean'],
            'show_payment_summary' => ['sometimes', 'boolean'],
            'show_notes' => ['sometimes', 'boolean'],
        ]);

        $this->visibilityService->updateFlags($campaign, $validated);

        return back()->with('success', 'Client visibility updated.');
    }
}
