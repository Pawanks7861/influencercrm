<?php

namespace Database\Factories;

use App\Enums\InfluencerType;
use App\Models\Influencer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Influencer>
 */
class InfluencerFactory extends Factory
{
    protected $model = Influencer::class;

    public function definition(): array
    {
        $username = fake()->unique()->userName();

        return [
            'name' => fake()->name(),
            'instagram_username' => strtolower($username),
            'instagram_url' => 'https://instagram.com/'.strtolower($username),
            'mobile' => fake()->numerify('98########'),
            'email' => fake()->unique()->safeEmail(),
            'location' => fake()->city(),
            'influencer_type' => InfluencerType::Medium,
            'default_price' => fake()->randomFloat(2, 1000, 50000),
            'notes_summary' => null,
            'status' => 'active',
        ];
    }
}
