<?php

namespace App\Http\Controllers\InfluencerPortal;

use App\Enums\DeliverableStatus;
use App\Http\Controllers\Controller;
use App\Models\Deliverable;
use App\Models\Influencer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DeliverableController extends Controller
{
    public function index(Request $request): Response
    {
        $influencer = $this->influencer();

        $deliverables = Deliverable::query()
            ->whereHas('campaignInfluencer', fn ($q) => $q->where('influencer_id', $influencer->id))
            ->with([
                'campaign:id,campaign_name,brand_name',
                'campaignInfluencer:id,campaign_id,influencer_id,status',
            ])
            ->latest('deadline')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return Inertia::render('InfluencerPortal/Deliverables/Index', [
            'deliverables' => $deliverables,
        ]);
    }

    public function update(Request $request, Deliverable $deliverable): RedirectResponse
    {
        $this->authorizeOwn($deliverable);

        $validated = $request->validate([
            'content_url' => ['nullable', 'string', 'max:500'],
            'status' => [
                'nullable',
                Rule::in([
                    DeliverableStatus::InProgress->value,
                    DeliverableStatus::Submitted->value,
                ]),
            ],
        ]);

        if (($validated['status'] ?? null) === DeliverableStatus::Submitted->value) {
            $validated['submitted_at'] = now();
        }

        $deliverable->update($validated);

        return back()->with('success', 'Deliverable updated.');
    }

    protected function authorizeOwn(Deliverable $deliverable): void
    {
        $influencerId = $this->influencer()->id;

        $owns = $deliverable->campaignInfluencer()
            ->where('influencer_id', $influencerId)
            ->exists();

        if (! $owns) {
            abort(403);
        }
    }

    protected function influencer(): Influencer
    {
        return Auth::user()->influencer;
    }
}
