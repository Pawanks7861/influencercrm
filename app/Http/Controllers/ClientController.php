<?php

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Enums\ClientActivityType;
use App\Enums\FollowUpStatus;
use App\Http\Requests\ClientStoreRequest;
use App\Http\Requests\ClientUpdateRequest;
use App\Models\Client;
use App\Models\ClientActivity;
use App\Models\User;
use App\Services\Client\ClientDuplicateDetectionService;
use App\Services\Client\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(
        protected ClientService $clientService,
        protected ClientDuplicateDetectionService $duplicateDetection
    ) {
        $this->middleware('permission:clients.view')->only(['index', 'show', 'checkDuplicate']);
        $this->middleware('permission:clients.create')->only(['create', 'store']);
        $this->middleware('permission:clients.edit')->only(['edit', 'update', 'storeActivity']);
        $this->middleware('permission:clients.delete')->only(['destroy']);
    }

    public function index(Request $request): Response|JsonResponse
    {
        if ($request->wantsJson() || $request->boolean('select')) {
            $clients = Client::query()
                ->when($request->get('search'), function ($q, $search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")
                            ->orWhere('contact_person', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy('company_name')
                ->limit(50)
                ->get(['id', 'name', 'company_name', 'contact_person', 'mobile', 'email', 'status']);

            return response()->json($clients);
        }

        $query = Client::query()
            ->withCount([
                'campaigns as active_campaigns_count' => fn ($q) => $q->where('status', CampaignStatus::Active->value),
                'followUps as pending_follow_ups_count' => fn ($q) => $q->where('status', FollowUpStatus::Pending->value),
            ]);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('website', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($request->boolean('has_active_campaign')) {
            $query->whereHas('campaigns', fn ($q) => $q->where('status', CampaignStatus::Active->value));
        }

        if ($campaignType = $request->get('campaign_type')) {
            $query->whereHas('campaigns', fn ($q) => $q->where('campaign_type', $campaignType));
        }

        if ($request->get('follow_up_status') === 'pending') {
            $query->whereHas('followUps', fn ($q) => $q->where('status', FollowUpStatus::Pending->value));
        } elseif ($request->get('follow_up_status') === 'overdue') {
            $query->whereHas('followUps', function ($q) {
                $q->where('status', FollowUpStatus::Pending->value)
                    ->whereDate('follow_up_date', '<', today());
            });
        }

        $sort = $request->get('sort', 'company_name');
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $allowed = ['company_name', 'contact_person', 'mobile', 'email', 'status', 'created_at'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'company_name';
        }

        $clients = $query->orderBy($sort, $direction)
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only([
                'search', 'status', 'campaign_type', 'has_active_campaign',
                'follow_up_status', 'sort', 'direction', 'per_page',
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Clients/Create');
    }

    public function store(ClientStoreRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $client = $this->clientService->create(
                $request->validated(),
                (bool) $request->boolean('force')
            );
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                throw $e;
            }

            return back()->withInput()->withErrors($e->errors());
        }

        if ($request->wantsJson()) {
            return response()->json($client, 201);
        }

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client created.');
    }

    public function show(Request $request, Client $client): Response
    {
        $client->load([
            'user:id,name,email,username',
            'campaigns' => fn ($q) => $q->latest()->limit(50),
            'followUps' => fn ($q) => $q->with(['assignee', 'campaign'])->latest('follow_up_date')->limit(50),
            'noteEntries.creator',
            'activities' => fn ($q) => $q->with('creator')->latest('activity_at')->limit(50),
        ]);

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'clientNotes' => $client->noteEntries,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'clientActivityTypes' => ClientActivityType::options(),
            'canManageLogin' => $request->user()?->can('clients.manage_login')
                || $request->user()?->can('clients.edit'),
        ]);
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('Clients/Edit', [
            'client' => $client,
        ]);
    }

    public function update(ClientUpdateRequest $request, Client $client): RedirectResponse
    {
        try {
            $this->clientService->update(
                $client,
                $request->validated(),
                (bool) $request->boolean('force')
            );
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client updated.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->clientService->delete($client);

        return redirect()
            ->route('clients.index')
            ->with('success', 'Client archived.');
    }

    public function checkDuplicate(Request $request): JsonResponse
    {
        $duplicates = $this->duplicateDetection->findDuplicates(
            $request->only(['company_name', 'name', 'mobile', 'phone', 'email']),
            $request->integer('exclude_id') ?: null
        );

        return response()->json([
            'has_duplicates' => $duplicates->isNotEmpty(),
            'duplicates' => $duplicates,
        ]);
    }

    public function storeActivity(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'activity_type' => ['required', Rule::enum(ClientActivityType::class)],
            'activity_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
        ]);

        ClientActivity::create([
            'client_id' => $client->id,
            'campaign_id' => $validated['campaign_id'] ?? null,
            'activity_type' => $validated['activity_type'],
            'activity_at' => $validated['activity_at'] ?? now(),
            'note' => $validated['note'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Activity logged.');
    }
}
