<?php

namespace Tests\Feature\Crm;

use App\Enums\RequirementStatus;
use App\Enums\RequirementType;
use App\Enums\ShortlistItemStatus;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientCampaignVisibility;
use App\Models\ClientRequirement;
use App\Models\Influencer;
use App\Services\Client\ClientLoginService;
use App\Services\Shortlist\ShortlistService;
use App\Support\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalConvertCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function createClientAccount(string $email): array
    {
        app(PermissionRegistrar::class)->register();
        $client = Client::factory()->create(['email' => $email]);
        $user = app(ClientLoginService::class)->createLogin($client, 'password', $email);

        return [$client->fresh(), $user->fresh()];
    }

    protected function makeRequirement(Client $client): ClientRequirement
    {
        return ClientRequirement::query()->create([
            'requirement_number' => 'REQ-2026-'.str_pad((string) (ClientRequirement::count() + 1), 4, '0', STR_PAD_LEFT),
            'client_id' => $client->id,
            'requirement_type' => RequirementType::InfluencerMarketing,
            'title' => 'Convert brief',
            'brand_name' => 'Satvam',
            'status' => 'shortlisting',
            'submitted_at' => now(),
        ]);
    }

    public function test_convert_creates_campaign_using_default_price_not_client_price(): void
    {
        $admin = $this->actingAsAdmin();
        [$client] = $this->createClientAccount('convert@example.com');
        $requirement = $this->makeRequirement($client);

        $influencer = Influencer::factory()->create(['default_price' => 12000]);

        $shortlist = app(ShortlistService::class)->create($requirement, 'Option A', [
            ['influencer_id' => $influencer->id, 'client_price' => 18000],
        ], $admin);

        $item = $shortlist->items()->first();
        $item->update(['status' => ShortlistItemStatus::Selected]);

        $this->post(route('requirements.convert-to-campaign', $requirement), [
            'shortlist_id' => $shortlist->id,
            'item_ids' => [$item->id],
            'include_interested' => false,
        ])->assertRedirect();

        $campaign = Campaign::query()->where('client_id', $client->id)->first();
        $this->assertNotNull($campaign);
        $this->assertSame('Convert brief', $campaign->campaign_name);
        $this->assertSame('Satvam', $campaign->brand_name);
        $this->assertStringContainsString($requirement->requirement_number, (string) $campaign->remarks);

        $ci = $campaign->campaignInfluencers()->first();
        $this->assertSame('12000.00', (string) $ci->influencer_cost);
        $this->assertNotSame('18000.00', (string) $ci->influencer_cost);
        $this->assertSame('0.00', (string) $ci->grovera_fee);
        $this->assertSame('0.00', (string) $ci->additional_cost);

        $requirement->refresh();
        $this->assertSame(RequirementStatus::ConvertedToCampaign, $requirement->status);
        $this->assertSame($campaign->id, $requirement->converted_campaign_id);

        $visibility = ClientCampaignVisibility::query()->where('campaign_id', $campaign->id)->first();
        $this->assertNotNull($visibility);
        $this->assertFalse($visibility->visible_to_client);
    }

    public function test_staff_price_revise_keeps_previous_client_price(): void
    {
        $admin = $this->actingAsAdmin();
        [$client] = $this->createClientAccount('revise@example.com');
        $requirement = $this->makeRequirement($client);
        $influencer = Influencer::factory()->create(['default_price' => 10000]);

        $shortlist = app(ShortlistService::class)->create($requirement, 'Revise list', [
            ['influencer_id' => $influencer->id, 'client_price' => 15000],
        ], $admin);
        app(ShortlistService::class)->share($shortlist, $admin);
        $item = $shortlist->items()->first();

        $this->patch(route('shortlists.update', $shortlist), [
            'items' => [
                ['id' => $item->id, 'client_price' => 17500],
            ],
        ])->assertRedirect();

        $item->refresh();
        $this->assertSame('17500.00', $item->client_price);
        $this->assertSame('15000.00', $item->previous_client_price);
        $this->assertNotNull($item->price_updated_at);
    }
}
