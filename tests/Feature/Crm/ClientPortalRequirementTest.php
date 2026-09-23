<?php

namespace Tests\Feature\Crm;

use App\Enums\RequirementType;
use App\Models\Client;
use App\Models\ClientRequirement;
use App\Models\User;
use App\Services\Client\ClientLoginService;
use App\Support\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalRequirementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Client, 1: User}
     */
    protected function createClientAccount(array $clientAttributes = []): array
    {
        app(PermissionRegistrar::class)->register();

        $client = Client::factory()->create(array_merge([
            'email' => 'req.client@example.com',
            'company_name' => 'Req Client',
        ], $clientAttributes));

        $user = app(ClientLoginService::class)->createLogin($client, 'password', $client->email);

        return [$client->fresh(), $user->fresh()];
    }

    public function test_client_can_create_influencer_marketing_requirement(): void
    {
        [, $user] = $this->createClientAccount(['email' => 'im@example.com']);
        $this->actingAs($user);

        $response = $this->post(route('client.requirements.store'), [
            'requirement_type' => RequirementType::InfluencerMarketing->value,
            'title' => 'Summer reel campaign',
            'brand_name' => 'Satvam',
            'budget_min' => 50000,
            'budget_max' => 100000,
            'influencers_required' => 5,
            'preferred_platforms' => ['instagram'],
            'preferred_category' => 'food',
            'client_id' => 9999,
        ]);

        $response->assertRedirect();
        $requirement = ClientRequirement::query()->first();
        $this->assertNotNull($requirement);
        $this->assertSame($user->client->id, $requirement->client_id);
        $this->assertSame(RequirementType::InfluencerMarketing, $requirement->requirement_type);
        $this->assertStringStartsWith('REQ-'.now()->format('Y').'-', $requirement->requirement_number);
    }

    public function test_client_can_create_social_media_marketing_requirement(): void
    {
        [, $user] = $this->createClientAccount(['email' => 'smm@example.com']);
        $this->actingAs($user);

        $this->post(route('client.requirements.store'), [
            'requirement_type' => RequirementType::SocialMediaMarketing->value,
            'title' => 'Monthly SMM retainer',
            'services_required' => ['content_creation', 'social_media_management'],
            'duration' => '3 months',
            'posting_frequency' => '3 / week',
            'goals' => 'Grow engagement',
        ])->assertRedirect();

        $requirement = ClientRequirement::query()->first();
        $this->assertSame(RequirementType::SocialMediaMarketing, $requirement->requirement_type);
        $this->assertSame(['content_creation', 'social_media_management'], $requirement->services_required);
    }

    public function test_client_a_cannot_read_client_b_requirement(): void
    {
        [, $userA] = $this->createClientAccount(['email' => 'client.a@example.com']);
        [$clientB] = $this->createClientAccount(['email' => 'client.b@example.com']);

        $requirement = ClientRequirement::query()->create([
            'requirement_number' => 'REQ-2026-0001',
            'client_id' => $clientB->id,
            'requirement_type' => RequirementType::InfluencerMarketing,
            'title' => 'Secret brief',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($userA);
        $this->get(route('client.requirements.show', $requirement))->assertNotFound();
    }
}
