<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Campaign;
use App\Models\CampaignInfluencer;
use App\Models\Influencer;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'campaign_influencer_id' => CampaignInfluencer::factory(),
            'influencer_id' => Influencer::factory(),
            'amount' => fake()->randomFloat(2, 1000, 20000),
            'payment_type' => 'partial',
            'payment_status' => 'paid',
            'payment_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::Upi,
            'transaction_reference' => fake()->bothify('TXN-####'),
            'remarks' => null,
        ];
    }
}
