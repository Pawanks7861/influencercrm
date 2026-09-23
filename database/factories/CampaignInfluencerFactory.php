<?php

namespace Database\Factories;

use App\Enums\CollaborationStatus;
use App\Enums\ContentApprovalStatus;
use App\Models\Campaign;
use App\Models\CampaignInfluencer;
use App\Models\Influencer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignInfluencer>
 */
class CampaignInfluencerFactory extends Factory
{
    protected $model = CampaignInfluencer::class;

    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 5000, 20000);
        $fee = fake()->randomFloat(2, 1000, 5000);

        return [
            'campaign_id' => Campaign::factory(),
            'influencer_id' => Influencer::factory(),
            'influencer_cost' => $cost,
            'grovera_fee' => $fee,
            'final_amount' => round($cost + $fee, 2),
            'final_amount_overridden' => false,
            'status' => CollaborationStatus::NewLead,
            'negotiated_price' => null,
            'content_deadline' => null,
            'posting_date' => null,
            'content_url' => null,
            'content_approval_status' => ContentApprovalStatus::NotSubmitted,
            'remarks' => null,
        ];
    }
}
