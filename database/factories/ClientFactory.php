<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $company = fake()->company();

        return [
            'name' => $company,
            'company_name' => $company,
            'contact_person' => fake()->name(),
            'mobile' => fake()->numerify('98########'),
            'alternate_mobile' => null,
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('98########'),
            'website' => null,
            'instagram_url' => null,
            'facebook_url' => null,
            'linkedin_url' => null,
            'youtube_url' => null,
            'twitter_url' => null,
            'address' => null,
            'notes' => null,
            'status' => ClientStatus::Active,
        ];
    }
}
