<?php

namespace Tests\Feature\Crm;

use App\Enums\RequirementType;
use App\Enums\ShortlistItemStatus;
use App\Enums\ShortlistStatus;
use App\Models\Client;
use App\Models\ClientRequirement;
use App\Models\Influencer;
use App\Models\InfluencerShortlistItem;
use App\Services\Client\ClientLoginService;
use App\Services\Shortlist\ShortlistService;
use App\Support\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalShortlistTest extends TestCase
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
            'title' => 'Shortlist brief',
            'status' => 'shortlisting',
            'submitted_at' => now(),
        ]);
    }

    public function test_staff_can_create_shortlist_with_independent_client_price(): void
    {
        $this->actingAsAdmin();
        [$client] = $this->createClientAccount('sl.client@example.com');
        $requirement = $this->makeRequirement($client);

        $influencer = Influencer::factory()->create(['default_price' => 12000]);

        $this->post(route('requirements.shortlists.store', $requirement), [
            'title' => 'Option A',
            'items' => [
                [
                    'influencer_id' => $influencer->id,
                    'client_price' => 18000,
                    'description' => 'Great fit',
                    'show_name' => true,
                    'show_instagram' => true,
                    'show_price' => true,
                    'show_location' => false,
                    'show_type' => false,
                    'show_note' => true,
                ],
            ],
        ])->assertRedirect();

        $item = InfluencerShortlistItem::query()->first();
        $this->assertSame('18000.00', $item->client_price);
        $this->assertSame('12000.00', (string) $influencer->fresh()->default_price);
        $this->assertSame($influencer->name, $item->display_name);
    }

    public function test_draft_shortlist_invisible_to_client(): void
    {
        [$client, $user] = $this->createClientAccount('draft@example.com');
        $requirement = $this->makeRequirement($client);
        $influencer = Influencer::factory()->create(['default_price' => 10000]);

        $shortlist = app(ShortlistService::class)->create($requirement, 'Draft list', [
            ['influencer_id' => $influencer->id, 'client_price' => 15000],
        ]);

        $this->assertSame(ShortlistStatus::Draft, $shortlist->status);

        $this->actingAs($user);
        $this->get(route('client.shortlists.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('shortlists', 0));

        $this->get(route('client.shortlists.show', $shortlist))->assertNotFound();
    }

    public function test_shared_shortlist_shows_only_client_price_and_snapshot_persists(): void
    {
        $admin = $this->actingAsAdmin();
        [$client, $user] = $this->createClientAccount('shared@example.com');
        $requirement = $this->makeRequirement($client);
        $influencer = Influencer::factory()->create([
            'name' => 'Riya Patel',
            'default_price' => 12000,
        ]);

        $shortlist = app(ShortlistService::class)->create($requirement, 'Shared list', [
            ['influencer_id' => $influencer->id, 'client_price' => 18000],
        ], $admin);

        app(ShortlistService::class)->share($shortlist, $admin);

        $influencer->update(['default_price' => 25000]);

        $this->actingAs($user);
        $this->get(route('client.shortlists.show', $shortlist))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ClientPortal/Shortlists/Show')
                ->where('shortlist.items.0.client_price', fn ($value) => (float) $value === 18000.0)
                ->missing('shortlist.items.0.default_price')
                ->missing('shortlist.items.0.internal_price')
                ->missing('shortlist.items.0.influencer_cost')
            );

        $this->assertSame('25000.00', (string) $influencer->fresh()->default_price);
    }

    public function test_client_a_cannot_access_client_b_shortlist(): void
    {
        $admin = $this->actingAsAdmin();
        [, $userA] = $this->createClientAccount('sla@example.com');
        [$clientB] = $this->createClientAccount('slb@example.com');
        $requirement = $this->makeRequirement($clientB);
        $influencer = Influencer::factory()->create();

        $shortlist = app(ShortlistService::class)->create($requirement, 'B list', [
            ['influencer_id' => $influencer->id, 'client_price' => 10000],
        ], $admin);
        app(ShortlistService::class)->share($shortlist, $admin);

        $this->actingAs($userA);
        $this->get(route('client.shortlists.show', $shortlist))->assertNotFound();
    }

    public function test_client_response_updates_visible_to_staff(): void
    {
        $admin = $this->actingAsAdmin();
        [$client, $user] = $this->createClientAccount('respond@example.com');
        $requirement = $this->makeRequirement($client);
        $influencer = Influencer::factory()->create();

        $shortlist = app(ShortlistService::class)->create($requirement, 'Respond list', [
            ['influencer_id' => $influencer->id, 'client_price' => 20000],
        ], $admin);
        app(ShortlistService::class)->share($shortlist, $admin);
        $item = $shortlist->items()->first();

        $this->actingAs($user);
        $this->post(route('client.shortlists.respond', $item), [
            'status' => ShortlistItemStatus::Interested->value,
            'client_remark' => 'Looks good',
        ])->assertRedirect();

        $item->refresh();
        $this->assertSame(ShortlistItemStatus::Interested, $item->status);
        $this->assertSame('Looks good', $item->client_remark);
        $this->assertSame(ShortlistStatus::Responded, $shortlist->fresh()->status);

        $this->actingAs($admin);
        $this->get(route('requirements.show', $requirement))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('shortlists.0.items.0.status', 'interested')
                ->where('shortlists.0.items.0.client_remark', 'Looks good')
            );
    }

    public function test_withdrawn_shortlist_not_accessible_to_client(): void
    {
        $admin = $this->actingAsAdmin();
        [$client, $user] = $this->createClientAccount('withdraw@example.com');
        $requirement = $this->makeRequirement($client);
        $influencer = Influencer::factory()->create();

        $shortlist = app(ShortlistService::class)->create($requirement, 'Withdraw me', [
            ['influencer_id' => $influencer->id, 'client_price' => 11000],
        ], $admin);
        app(ShortlistService::class)->share($shortlist, $admin);
        app(ShortlistService::class)->withdraw($shortlist);

        $this->actingAs($user);
        $this->get(route('client.shortlists.show', $shortlist))->assertNotFound();
    }
}
