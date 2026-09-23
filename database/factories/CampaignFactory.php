<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'campaign_name' => fake()->words(3, true).' Campaign',
            'client_id' => Client::factory(),
            'brand_name' => fake()->company(),
            'campaign_budget' => fake()->randomFloat(2, 10000, 500000),
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonth()->toDateString(),
            'posting_date' => null,
            'status' => CampaignStatus::Active,
            'remarks' => null,
        ];
    }
}
