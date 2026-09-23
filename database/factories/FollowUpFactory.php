<?php

namespace Database\Factories;

use App\Enums\FollowUpStatus;
use App\Models\FollowUp;
use App\Models\Influencer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUp>
 */
class FollowUpFactory extends Factory
{
    protected $model = FollowUp::class;

    public function definition(): array
    {
        return [
            'influencer_id' => Influencer::factory(),
            'client_id' => null,
            'campaign_id' => null,
            'assigned_to' => User::factory(),
            'follow_up_date' => now()->addDay()->toDateString(),
            'follow_up_time' => '10:00',
            'note' => fake()->sentence(),
            'status' => FollowUpStatus::Pending,
            'completed_at' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'follow_up_date' => now()->subDays(2)->toDateString(),
            'status' => FollowUpStatus::Pending,
            'completed_at' => null,
        ]);
    }
}
