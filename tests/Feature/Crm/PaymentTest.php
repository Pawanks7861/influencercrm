<?php

namespace Tests\Feature\Crm;

use App\Enums\CampaignStatus;
use App\Enums\CollaborationStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\Influencer;
use App\Services\Campaign\CampaignService;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_then_full_payment_updates_pending_and_status(): void
    {
        $admin = $this->actingAsAdmin();

        $client = Client::factory()->create();
        $influencer = Influencer::factory()->create();

        $campaign = app(CampaignService::class)->create([
            'campaign_name' => 'Payment Campaign',
            'client_id' => $client->id,
            'status' => CampaignStatus::Active->value,
        ], [
            [
                'influencer_id' => $influencer->id,
                'influencer_cost' => 12000,
                'grovera_fee' => 3000,
                'status' => CollaborationStatus::Posted->value,
            ],
        ]);

        $row = $campaign->campaignInfluencers()->first();

        $this->post(route('payments.store'), [
            'campaign_influencer_id' => $row->id,
            'amount' => 5000,
            'payment_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::Upi->value,
            'payment_type' => 'advance',
        ])->assertRedirect();

        $status = app(PaymentService::class)->deriveStatus($row->fresh());

        $this->assertEquals(5000.0, $status['amount_paid']);
        $this->assertEquals(10000.0, $status['amount_pending']);
        $this->assertSame(PaymentStatus::PartiallyPaid->value, $status['payment_status']);

        $this->post(route('payments.store'), [
            'campaign_influencer_id' => $row->id,
            'amount' => 10000,
            'payment_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::BankTransfer->value,
            'payment_type' => 'balance',
            'created_by' => $admin->id,
        ])->assertRedirect();

        $status = app(PaymentService::class)->deriveStatus($row->fresh());

        $this->assertEquals(15000.0, $status['amount_paid']);
        $this->assertEquals(0.0, $status['amount_pending']);
        $this->assertSame(PaymentStatus::Paid->value, $status['payment_status']);
    }
}
