<?php

namespace Tests\Feature\Crm;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Influencer;
use App\Services\Campaign\CampaignService;
use App\Services\Campaign\CampaignVisibilityService;
use App\Services\Client\ClientLoginService;
use App\Support\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalCampaignVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function createClientAccount(string $email): array
    {
        app(PermissionRegistrar::class)->register();
        $client = Client::factory()->create(['email' => $email]);
        $user = app(ClientLoginService::class)->createLogin($client, 'password', $email);

        return [$client->fresh(), $user->fresh()];
    }

    protected function makeCampaign(Client $client, Influencer $influencer): Campaign
    {
        return app(CampaignService::class)->create([
            'client_id' => $client->id,
            'campaign_name' => 'Visible Campaign',
            'brand_name' => 'Brand X',
            'campaign_type' => CampaignType::InfluencerMarketing->value,
            'status' => CampaignStatus::Active->value,
            'remarks' => 'Internal notes only',
        ], [
            [
                'influencer_id' => $influencer->id,
                'influencer_cost' => 9000,
                'grovera_fee' => 1500,
                'additional_cost' => 250,
            ],
        ]);
    }

    public function test_client_a_cannot_see_client_b_campaign(): void
    {
        $admin = $this->actingAsAdmin();
        [, $userA] = $this->createClientAccount('campa@example.com');
        [$clientB] = $this->createClientAccount('campb@example.com');
        $influencer = Influencer::factory()->create(['default_price' => 9000, 'name' => 'Riya']);

        $campaign = $this->makeCampaign($clientB, $influencer);
        app(CampaignVisibilityService::class)->share($campaign, $admin);

        $this->actingAs($userA);
        $this->get(route('client.campaigns.show', $campaign))->assertNotFound();
        $this->get(route('client.campaigns.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('campaigns', 0));
    }

    public function test_unshared_campaign_invisible_to_client(): void
    {
        $this->actingAsAdmin();
        [$client, $user] = $this->createClientAccount('unshared@example.com');
        $influencer = Influencer::factory()->create();
        $campaign = $this->makeCampaign($client, $influencer);

        $this->actingAs($user);
        $this->get(route('client.campaigns.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('campaigns', 0));
        $this->get(route('client.campaigns.show', $campaign))->assertNotFound();
    }

    public function test_shared_campaign_response_lacks_internal_price_fields(): void
    {
        $admin = $this->actingAsAdmin();
        [$client, $user] = $this->createClientAccount('sharedcamp@example.com');
        $influencer = Influencer::factory()->create([
            'name' => 'Riya Patel',
            'instagram_username' => 'riya.patel',
            'default_price' => 9000,
            'mobile' => '9999999999',
            'email' => 'riya@internal.test',
        ]);

        $campaign = $this->makeCampaign($client, $influencer);
        app(CampaignVisibilityService::class)->share($campaign, $admin, [
            'show_influencers' => true,
            'show_payment_summary' => false,
        ]);

        $this->actingAs($user);
        $this->get(route('client.campaigns.show', $campaign))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ClientPortal/Campaigns/Show')
                ->where('campaign.campaign_name', 'Visible Campaign')
                ->where('campaign.influencers.0.display_name', 'Riya Patel')
                ->where('campaign.influencers.0.instagram_username', 'riya.patel')
                ->missing('campaign.influencers.0.influencer_cost')
                ->missing('campaign.influencers.0.grovera_fee')
                ->missing('campaign.influencers.0.additional_cost')
                ->missing('campaign.influencers.0.margin')
                ->missing('campaign.influencers.0.mobile')
                ->missing('campaign.influencers.0.email')
                ->missing('campaign.payment_summary')
                ->missing('campaign.remarks')
            );
    }

    public function test_expired_shortlist_shows_message_and_blocks_respond(): void
    {
        $admin = $this->actingAsAdmin();
        [$client, $user] = $this->createClientAccount('expired@example.com');

        $requirement = \App\Models\ClientRequirement::query()->create([
            'requirement_number' => 'REQ-2026-0099',
            'client_id' => $client->id,
            'requirement_type' => \App\Enums\RequirementType::InfluencerMarketing,
            'title' => 'Expired brief',
            'status' => 'shortlisting',
            'submitted_at' => now(),
        ]);

        $influencer = Influencer::factory()->create();
        $shortlist = app(\App\Services\Shortlist\ShortlistService::class)->create($requirement, 'Expired list', [
            ['influencer_id' => $influencer->id, 'client_price' => 10000],
        ], $admin);
        app(\App\Services\Shortlist\ShortlistService::class)->share($shortlist, $admin, now()->subDay()->toDateString());
        $shortlist->update(['expires_at' => now()->subDay()->toDateString()]);
        $item = $shortlist->items()->first();

        $this->actingAs($user);
        $this->get(route('client.shortlists.show', $shortlist))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('shortlist.is_expired', true)
                ->where('shortlist.can_respond', false)
            );

        $this->post(route('client.shortlists.respond', $item), [
            'status' => 'interested',
        ])->assertNotFound();
    }
}
