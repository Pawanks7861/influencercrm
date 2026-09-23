<?php

namespace Tests\Feature\Crm;

use App\Enums\DeliverableStatus;
use App\Enums\DeliverableType;
use App\Models\Campaign;
use App\Models\CampaignInfluencer;
use App\Models\Deliverable;
use App\Models\Influencer;
use App\Models\User;
use App\Services\Influencer\InfluencerLoginService;
use App\Support\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InfluencerPortalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Influencer, 1: User}
     */
    protected function createInfluencerAccount(array $influencerAttributes = [], string $password = 'password'): array
    {
        app(PermissionRegistrar::class)->register();

        $influencer = Influencer::factory()->create(array_merge([
            'email' => 'riya.portal@example.com',
            'name' => 'Riya Patel',
        ], $influencerAttributes));

        $user = app(InfluencerLoginService::class)->createLogin(
            $influencer,
            $password,
            $influencer->email
        );

        return [$influencer->fresh(), $user->fresh()];
    }

    public function test_admin_can_create_login_for_influencer(): void
    {
        $this->actingAsAdmin();

        $influencer = Influencer::factory()->create([
            'email' => 'create.login@example.com',
            'user_id' => null,
            'login_enabled' => false,
        ]);

        $response = $this->post(route('influencers.login.store', $influencer), [
            'email' => 'create.login@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();
        $influencer->refresh();

        $this->assertNotNull($influencer->user_id);
        $this->assertTrue($influencer->login_enabled);
        $this->assertTrue($influencer->user->hasRole('influencer'));
        $this->assertTrue(Hash::check('password', $influencer->user->password));
        $this->assertTrue($influencer->user->can('influencer_portal.access'));
    }

    public function test_influencer_can_login_and_redirects_to_portal_dashboard(): void
    {
        [, $user] = $this->createInfluencerAccount();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('influencer.portal.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_influencer_cannot_access_staff_crm_routes(): void
    {
        [$influencer, $user] = $this->createInfluencerAccount();
        $this->actingAs($user);

        $this->get('/influencers')->assertForbidden();
        $this->get('/clients')->assertForbidden();
        $this->get('/campaigns')->assertForbidden();
        $this->get('/reports')->assertForbidden();
        $this->get('/settings')->assertForbidden();
        $this->get(route('influencers.show', $influencer))->assertForbidden();
    }

    public function test_influencer_isolation_across_portal_and_crm(): void
    {
        [$influencerA, $userA] = $this->createInfluencerAccount([
            'email' => 'influencer.a@example.com',
            'name' => 'Influencer A',
        ]);

        [$influencerB] = $this->createInfluencerAccount([
            'email' => 'influencer.b@example.com',
            'name' => 'Influencer B',
        ]);

        $campaign = Campaign::factory()->create();
        $rowA = CampaignInfluencer::factory()->create([
            'campaign_id' => $campaign->id,
            'influencer_id' => $influencerA->id,
        ]);
        $rowB = CampaignInfluencer::factory()->create([
            'campaign_id' => $campaign->id,
            'influencer_id' => $influencerB->id,
        ]);

        $this->actingAs($userA);

        $this->get(route('influencer.campaigns.show', $rowA->id))->assertOk();
        $this->get(route('influencer.campaigns.show', $rowB->id))->assertNotFound();
        $this->get(route('influencers.show', $influencerB))->assertForbidden();

        $index = $this->get(route('influencer.campaigns.index'));
        $index->assertOk();
        $index->assertInertia(fn ($page) => $page
            ->component('InfluencerPortal/Campaigns/Index')
            ->has('campaigns.data', 1)
            ->where('campaigns.data.0.id', $rowA->id)
        );
    }

    public function test_influencer_cannot_approve_deliverable(): void
    {
        [$influencer, $user] = $this->createInfluencerAccount([
            'email' => 'deliverable.owner@example.com',
        ]);

        $row = CampaignInfluencer::factory()->create([
            'influencer_id' => $influencer->id,
        ]);

        $deliverable = Deliverable::factory()->create([
            'campaign_id' => $row->campaign_id,
            'campaign_influencer_id' => $row->id,
            'type' => DeliverableType::InstagramReel,
            'status' => DeliverableStatus::Pending,
        ]);

        $this->actingAs($user);

        $this->from(route('influencer.deliverables.index'))
            ->patch(route('influencer.deliverables.update', $deliverable), [
                'content_url' => 'https://example.com/content',
                'status' => DeliverableStatus::Approved->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(DeliverableStatus::Pending, $deliverable->fresh()->status);

        $this->patch(route('influencer.deliverables.update', $deliverable), [
            'content_url' => 'https://example.com/content',
            'status' => DeliverableStatus::Submitted->value,
        ])->assertRedirect();

        $deliverable->refresh();
        $this->assertSame(DeliverableStatus::Submitted, $deliverable->status);
        $this->assertSame('https://example.com/content', $deliverable->content_url);
        $this->assertNotNull($deliverable->submitted_at);

        $this->patch(route('influencer.deliverables.update', $deliverable), [
            'status' => DeliverableStatus::InProgress->value,
        ])->assertRedirect();

        $this->assertSame(DeliverableStatus::InProgress, $deliverable->fresh()->status);
    }

    public function test_influencer_cannot_update_another_influencers_deliverable(): void
    {
        [, $userA] = $this->createInfluencerAccount([
            'email' => 'owner.a@example.com',
        ]);

        [$influencerB] = $this->createInfluencerAccount([
            'email' => 'owner.b@example.com',
        ]);

        $rowB = CampaignInfluencer::factory()->create([
            'influencer_id' => $influencerB->id,
        ]);

        $deliverable = Deliverable::factory()->create([
            'campaign_id' => $rowB->campaign_id,
            'campaign_influencer_id' => $rowB->id,
            'status' => DeliverableStatus::Pending,
        ]);

        $this->actingAs($userA);

        $this->patch(route('influencer.deliverables.update', $deliverable), [
            'status' => DeliverableStatus::Submitted->value,
        ])->assertForbidden();
    }
}
