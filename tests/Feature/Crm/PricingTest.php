<?php

namespace Tests\Feature\Crm;

use App\Enums\CampaignStatus;
use App\Enums\CollaborationStatus;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Influencer;
use App\Services\Campaign\CampaignService;
use App\Services\Campaign\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_influencer_cost_plus_additional_plus_grovera_fee_equals_final_amount(): void
    {
        $this->actingAsAdmin();

        $client = Client::factory()->create();
        $influencer = Influencer::factory()->create(['default_price' => 15000]);

        $campaign = app(CampaignService::class)->create([
            'campaign_name' => 'Pricing Campaign',
            'client_id' => $client->id,
            'status' => CampaignStatus::Active->value,
        ], [
            [
                'influencer_id' => $influencer->id,
                'influencer_cost' => 12000,
                'additional_cost' => 2000,
                'grovera_fee' => 3000,
                'status' => CollaborationStatus::PriceConfirmed->value,
            ],
        ]);

        $row = $campaign->campaignInfluencers()->first();

        $this->assertEquals(12000, (float) $row->influencer_cost);
        $this->assertEquals(2000, (float) $row->additional_cost);
        $this->assertEquals(3000, (float) $row->grovera_fee);
        $this->assertEquals(17000, (float) $row->final_amount);
        $this->assertEquals(3000, (float) $row->margin);
        $this->assertFalse($row->final_amount_overridden);
        $this->assertSame('17000.00', app(PricingService::class)->calculateFinalAmount(12000, 2000, 3000));
    }

    public function test_overridden_final_amount_is_not_reset_when_editing_campaign_remarks(): void
    {
        $this->actingAsAdmin();

        $client = Client::factory()->create();
        $influencer = Influencer::factory()->create();

        $campaign = app(CampaignService::class)->create([
            'campaign_name' => 'Override Campaign',
            'client_id' => $client->id,
            'status' => CampaignStatus::Active->value,
            'remarks' => 'Initial',
        ], [
            [
                'influencer_id' => $influencer->id,
                'influencer_cost' => 12000,
                'additional_cost' => 2000,
                'grovera_fee' => 3000,
            ],
        ]);

        $row = $campaign->campaignInfluencers()->first();

        app(PricingService::class)->applyPricing($row, [
            'override_final_amount' => true,
            'final_amount' => 14000,
        ]);

        $row->refresh();
        $this->assertEquals(14000, (float) $row->final_amount);
        $this->assertTrue($row->final_amount_overridden);

        $response = $this->put(route('campaigns.update', $campaign), [
            'campaign_name' => $campaign->campaign_name,
            'client_id' => $client->id,
            'status' => CampaignStatus::Active->value,
            'remarks' => 'Updated remarks only',
        ]);

        $response->assertRedirect(route('campaigns.show', $campaign));

        $row->refresh();
        $this->assertEquals(14000, (float) $row->final_amount);
        $this->assertTrue($row->final_amount_overridden);
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'remarks' => 'Updated remarks only',
        ]);
    }
}
