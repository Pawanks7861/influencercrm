<?php

namespace Database\Factories;

use App\Enums\DeliverableStatus;
use App\Enums\DeliverableType;
use App\Models\CampaignInfluencer;
use App\Models\Deliverable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deliverable>
 */
class DeliverableFactory extends Factory
{
    protected $model = Deliverable::class;

    public function definition(): array
    {
        return [
            'campaign_influencer_id' => CampaignInfluencer::factory(),
            'type' => DeliverableType::InstagramReel,
            'quantity' => 1,
            'title' => fake()->sentence(3),
            'description' => null,
            'deadline' => now()->addWeek()->toDateString(),
            'content_url' => null,
            'status' => DeliverableStatus::Pending,
            'remarks' => null,
        ];
    }
}
