<?php

namespace App\Http\Controllers;

use App\Http\Requests\FollowUpStoreRequest;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Influencer;
use App\Models\User;
use App\Services\FollowUp\FollowUpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FollowUpController extends Controller
{
    public function __construct(
        protected FollowUpService $followUpService
    ) {
        $this->middleware('permission:followups.view')->only(['index']);
        $this->middleware('permission:followups.create')->only(['store']);
        $this->middleware('permission:followups.edit')->only(['update', 'complete', 'reschedule', 'cancel']);
    }

    public function index(Request $request): Response
    {
        $query = FollowUp::query()->with(['influencer', 'client', 'campaign', 'assignee']);

        if ($request->get('filter') === 'today') {
            $query->where('status', 'pending')->whereDate('follow_up_date', today());
        } elseif ($request->get('filter') === 'overdue') {
            $query->where('status', 'pending')->whereDate('follow_up_date', '<', today());
        } elseif ($request->get('filter') === 'upcoming') {
            $query->where('status', 'pending')->whereDate('follow_up_date', '>', today());
        } elseif ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($entity = $request->get('entity')) {
            if ($entity === 'influencer') {
                $query->whereNotNull('influencer_id');
            } elseif ($entity === 'client') {
                $query->whereNotNull('client_id');
            } elseif ($entity === 'campaign') {
                $query->whereNotNull('campaign_id')->whereNull('influencer_id')->whereNull('client_id');
            }
        }

        if ($assignedTo = $request->get('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('influencer', fn ($i) => $i->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('client', function ($c) use ($search) {
                        $c->where('company_name', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('campaign', fn ($c) => $c->where('campaign_name', 'like', "%{$search}%"));
            });
        }

        $followUps = $query->orderBy('follow_up_date')
            ->orderBy('follow_up_time')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString()
            ->through(function (FollowUp $followUp) {
                $followUp->setAttribute('related_to', $followUp->relatedLabel());

                return $followUp;
            });

        return Inertia::render('FollowUps/Index', [
            'followUps' => $followUps,
            'filters' => $request->only(['filter', 'status', 'entity', 'assigned_to', 'search', 'per_page']),
            'counts' => $this->followUpService->counts(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'influencers' => Influencer::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'name', 'company_name']),
        ]);
    }

    public function store(FollowUpStoreRequest $request): RedirectResponse
    {
        $this->followUpService->create($request->validated());

        return back()->with('success', 'Follow-up scheduled.');
    }

    public function update(Request $request, FollowUp $followUp): RedirectResponse
    {
        $validated = $request->validate([
            'influencer_id' => ['nullable', 'exists:influencers,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'assigned_to' => ['sometimes', 'required', 'exists:users,id'],
            'follow_up_date' => ['sometimes', 'required', 'date'],
            'follow_up_time' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string'],
        ]);

        $followUp->update($validated);

        return back()->with('success', 'Follow-up updated.');
    }

    public function complete(FollowUp $followUp): RedirectResponse
    {
        $this->followUpService->complete($followUp);

        return back()->with('success', 'Follow-up completed.');
    }

    public function cancel(FollowUp $followUp): RedirectResponse
    {
        $this->followUpService->cancel($followUp);

        return back()->with('success', 'Follow-up cancelled.');
    }

    public function reschedule(Request $request, FollowUp $followUp): RedirectResponse
    {
        $validated = $request->validate([
            'follow_up_date' => ['required', 'date'],
            'follow_up_time' => ['nullable', 'date_format:H:i'],
        ]);

        $this->followUpService->reschedule(
            $followUp,
            $validated['follow_up_date'],
            $validated['follow_up_time'] ?? null
        );

        return back()->with('success', 'Follow-up rescheduled.');
    }
}
