<?php

namespace App\Http\Controllers;

use App\Enums\CollaborationStatus;
use App\Models\CampaignInfluencer;
use App\Services\Campaign\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampaignInfluencerController extends Controller
{
    public function __construct(
        protected PricingService $pricingService
    ) {
        $this->middleware('permission:campaigns.edit');
    }

    public function updateStatus(Request $request, CampaignInfluencer $campaignInfluencer): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(CollaborationStatus::class)],
            'content_url' => ['nullable', 'string', 'max:500'],
            'content_approval_status' => ['nullable', 'string'],
            'content_approval_notes' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        if (($validated['content_approval_status'] ?? null) === 'approved') {
            $validated['content_approved_at'] = now();
        }

        $validated['updated_by'] = $request->user()->id;
        $campaignInfluencer->update($validated);

        return back()->with('success', 'Collaboration status updated.');
    }

    public function updatePricing(Request $request, CampaignInfluencer $campaignInfluencer): RedirectResponse
    {
        $validated = $request->validate([
            'influencer_cost' => ['nullable', 'numeric', 'min:0'],
            'grovera_fee' => ['nullable', 'numeric', 'min:0'],
            'final_amount' => ['nullable', 'numeric', 'min:0'],
            'override_final_amount' => ['sometimes', 'boolean'],
            'final_amount_overridden' => ['sometimes', 'boolean'],
            'negotiated_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->pricingService->applyPricing($campaignInfluencer, $validated);

        return back()->with('success', 'Pricing updated.');
    }
}
