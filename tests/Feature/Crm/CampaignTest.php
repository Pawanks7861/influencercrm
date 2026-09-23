<?php

namespace Tests\Feature\Crm;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\CollaborationStatus;
use App\Enums\DeliverableType;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Influencer;
use App\Services\Campaign\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_supports_multiple_influencers(): void
    {
        $this->actingAsAdmin();

        $client = Client::factory()->create(['name' => 'Satvam']);
        $first = Influencer::factory()->create(['name' => 'Riya Patel', 'default_price' => 15000]);
        $second = Influencer::factory()->create(['name' => 'Asha Shah', 'default_price' => 8000]);

        $response = $this->post(route('campaigns.store'), [
            'campaign_name' => 'Satvam Diwali Campaign',
            'client_id' => $client->id,
            'brand_name' => 'Satvam',
            'campaign_type' => CampaignType::InfluencerMarketing->value,
            'status' => CampaignStatus::Active->value,
            'influencers' => [
                [
                    'influencer_id' => $first->id,
                    'influencer_cost' => 12000,
                    'grovera_fee' => 3000,
                ],
                [
                    'influencer_id' => $second->id,
                    'influencer_cost' => 8000,
                    'grovera_fee' => 2000,
                ],
            ],
        ]);

        $campaign = Campaign::query()->where('campaign_name', 'Satvam Diwali Campaign')->first();

        $this->assertNotNull($campaign);
        $this->assertCount(2, $campaign->campaignInfluencers);
        $response->assertRedirect(route('campaigns.show', $campaign));
    }

    public function test_same_influencer_is_not_accidentally_added_twice(): void
    {
        $this->actingAsAdmin();

        $client = Client::factory()->create();
        $influencer = Influencer::factory()->create();

        $this->expectException(ValidationException::class);

        app(CampaignService::class)->create([
            'campaign_name' => 'Duplicate Guard Campaign',
            'client_id' => $client->id,
            'campaign_type' => CampaignType::InfluencerMarketing->value,
            'status' => CampaignStatus::Draft->value,
        ], [
            [
                'influencer_id' => $influencer->id,
                'influencer_cost' => 10000,
                'grovera_fee' => 1000,
            ],
            [
                'influencer_id' => $influencer->id,
                'influencer_cost' => 11000,
                'grovera_fee' => 1000,
            ],
        ]);
    }

    public function test_campaign_totals_are_correct(): void
    {
        $this->actingAsAdmin();

        $client = Client::factory()->create();
        $a = Influencer::factory()->create();
        $b = Influencer::factory()->create();

        $campaign = app(CampaignService::class)->create([
            'campaign_name' => 'Totals Campaign',
            'client_id' => $client->id,
            'campaign_type' => CampaignType::InfluencerMarketing->value,
            'status' => CampaignStatus::Active->value,
        ], [
            [
                'influencer_id' => $a->id,
                'influencer_cost' => 12000,
                'grovera_fee' => 3000,
                'status' => CollaborationStatus::PriceConfirmed->value,
            ],
            [
                'influencer_id' => $b->id,
                'influencer_cost' => 8000,
                'grovera_fee' => 2000,
            ],
        ]);

        $totals = app(CampaignService::class)->totals($campaign->fresh('campaignInfluencers'));

        $this->assertSame(20000.0, $totals['total_influencer_cost']);
        $this->assertSame(0.0, $totals['total_additional_cost']);
        $this->assertSame(5000.0, $totals['total_grovera_fee']);
        $this->assertSame(25000.0, $totals['total_final_amount']);
        $this->assertSame(5000.0, $totals['margin']);
        $this->assertSame(2, $totals['influencer_count']);
    }

    public function test_social_media_marketing_campaign_without_influencers(): void
    {
        $this->actingAsAdmin();

        $client = Client::factory()->create();

        $response = $this->post(route('campaigns.store'), [
            'campaign_name' => 'SMM Retainer',
            'client_id' => $client->id,
            'campaign_type' => CampaignType::SocialMediaMarketing->value,
            'platforms' => ['instagram', 'facebook'],
            'service_fee' => 25000,
            'status' => CampaignStatus::Active->value,
            'deliverables' => [
                [
                    'type' => DeliverableType::InstagramPost->value,
                    'platform' => 'instagram',
                    'quantity' => 8,
                ],
                [
                    'type' => DeliverableType::SocialMediaManagement->value,
                    'platform' => 'facebook',
                    'quantity' => 1,
                ],
            ],
        ]);

        $campaign = Campaign::query()->where('campaign_name', 'SMM Retainer')->first();

        $this->assertNotNull($campaign);
        $this->assertSame(CampaignType::SocialMediaMarketing, $campaign->campaign_type);
        $this->assertCount(0, $campaign->campaignInfluencers);
        $this->assertCount(2, $campaign->deliverables()->whereNull('campaign_influencer_id')->get());
        $response->assertRedirect(route('campaigns.show', $campaign));
    }

    public function test_both_campaign_type_syncs_influencers_and_deliverables(): void
    {
        $this->actingAsAdmin();

        $client = Client::factory()->create();
        $influencer = Influencer::factory()->create();

        $response = $this->post(route('campaigns.store'), [
            'campaign_name' => 'Hybrid Campaign',
            'client_id' => $client->id,
            'campaign_type' => CampaignType::Both->value,
            'platforms' => ['instagram'],
            'status' => CampaignStatus::Active->value,
            'influencers' => [
                [
                    'influencer_id' => $influencer->id,
                    'influencer_cost' => 10000,
                    'additional_cost' => 500,
                    'grovera_fee' => 2000,
                ],
            ],
            'deliverables' => [
                [
                    'type' => DeliverableType::ContentCalendar->value,
                    'platform' => 'instagram',
                    'quantity' => 1,
                ],
            ],
        ]);

        $campaign = Campaign::query()->where('campaign_name', 'Hybrid Campaign')->first();

        $this->assertNotNull($campaign);
        $this->assertCount(1, $campaign->campaignInfluencers);
        $this->assertEquals(500, (float) $campaign->campaignInfluencers->first()->additional_cost);
        $this->assertCount(1, $campaign->deliverables()->whereNull('campaign_influencer_id')->get());
        $response->assertRedirect(route('campaigns.show', $campaign));
    }
}
