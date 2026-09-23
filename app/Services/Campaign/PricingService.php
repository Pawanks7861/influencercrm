<?php

namespace App\Services\Campaign;

use App\Models\CampaignInfluencer;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;

class PricingService
{
    public function calculateFinalAmount(mixed $influencerCost, mixed $additionalCost = 0, mixed $groveraFee = 0): string
    {
        return Money::add(
            Money::add($influencerCost, $additionalCost),
            $groveraFee
        );
    }

    public function applyPricing(CampaignInfluencer $campaignInfluencer, array $data): CampaignInfluencer
    {
        if (array_key_exists('influencer_cost', $data)) {
            $campaignInfluencer->influencer_cost = Money::of($data['influencer_cost']);
        }

        if (array_key_exists('additional_cost', $data)) {
            $campaignInfluencer->additional_cost = Money::of($data['additional_cost']);
        }

        if (array_key_exists('grovera_fee', $data)) {
            $campaignInfluencer->grovera_fee = Money::of($data['grovera_fee']);
        }

        if (! empty($data['override_final_amount']) && array_key_exists('final_amount', $data)) {
            $campaignInfluencer->final_amount = Money::of($data['final_amount']);
            $campaignInfluencer->final_amount_overridden = true;
        } elseif (array_key_exists('final_amount_overridden', $data) && ! $data['final_amount_overridden']) {
            $campaignInfluencer->final_amount_overridden = false;
            $campaignInfluencer->final_amount = $this->calculateFinalAmount(
                $campaignInfluencer->influencer_cost,
                $campaignInfluencer->additional_cost ?? 0,
                $campaignInfluencer->grovera_fee
            );
        } else {
            $campaignInfluencer->recalculateFinalAmountIfNotOverridden();
        }

        if (array_key_exists('negotiated_price', $data)) {
            $campaignInfluencer->negotiated_price = $data['negotiated_price'];
        }

        $campaignInfluencer->updated_by = Auth::id();
        $campaignInfluencer->save();

        return $campaignInfluencer->fresh();
    }
}
