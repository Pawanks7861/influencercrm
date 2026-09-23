<?php

namespace Tests\Feature\Crm;

use App\Enums\FollowUpStatus;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Influencer;
use App\Services\FollowUp\FollowUpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_past_due_incomplete_follow_up_appears_under_overdue(): void
    {
        $admin = $this->actingAsAdmin();
        $influencer = Influencer::factory()->create();

        $overdue = FollowUp::factory()->overdue()->create([
            'influencer_id' => $influencer->id,
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
            'note' => 'Call Riya about product',
            'status' => FollowUpStatus::Pending,
        ]);

        FollowUp::factory()->create([
            'influencer_id' => $influencer->id,
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
            'follow_up_date' => now()->addDay()->toDateString(),
            'status' => FollowUpStatus::Pending,
            'note' => 'Future follow-up',
        ]);

        $this->assertTrue(
            app(FollowUpService::class)->overdueQuery()->whereKey($overdue->id)->exists()
        );

        $response = $this->get(route('follow-ups.index', ['filter' => 'overdue']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('FollowUps/Index')
            ->has('followUps.data', 1)
            ->where('followUps.data.0.id', $overdue->id)
            ->where('filters.filter', 'overdue')
        );
    }

    public function test_client_follow_ups_appear_in_today_upcoming_and_overdue(): void
    {
        $admin = $this->actingAsAdmin();
        $client = Client::factory()->create(['company_name' => 'ABC Foods']);

        $today = FollowUp::factory()->create([
            'influencer_id' => null,
            'client_id' => $client->id,
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
            'follow_up_date' => now()->toDateString(),
            'status' => FollowUpStatus::Pending,
            'note' => 'Today client follow-up',
        ]);

        $upcoming = FollowUp::factory()->create([
            'influencer_id' => null,
            'client_id' => $client->id,
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
            'follow_up_date' => now()->addDays(3)->toDateString(),
            'status' => FollowUpStatus::Pending,
            'note' => 'Upcoming client follow-up',
        ]);

        $overdue = FollowUp::factory()->overdue()->create([
            'influencer_id' => null,
            'client_id' => $client->id,
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
            'note' => 'Overdue client follow-up',
        ]);

        $this->assertTrue(app(FollowUpService::class)->todayQuery()->whereKey($today->id)->exists());
        $this->assertTrue(app(FollowUpService::class)->overdueQuery()->whereKey($overdue->id)->exists());

        $this->get(route('follow-ups.index', ['filter' => 'today', 'entity' => 'client']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('FollowUps/Index')
                ->has('followUps.data', 1)
                ->where('followUps.data.0.id', $today->id)
            );

        $this->get(route('follow-ups.index', ['filter' => 'upcoming', 'entity' => 'client']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('FollowUps/Index')
                ->has('followUps.data', 1)
                ->where('followUps.data.0.id', $upcoming->id)
            );

        $this->get(route('follow-ups.index', ['filter' => 'overdue', 'entity' => 'client']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('FollowUps/Index')
                ->has('followUps.data', 1)
                ->where('followUps.data.0.id', $overdue->id)
            );
    }

    public function test_client_follow_up_can_be_created(): void
    {
        $this->actingAsAdmin();
        $client = Client::factory()->create();

        $response = $this->post(route('follow-ups.store'), [
            'client_id' => $client->id,
            'follow_up_date' => now()->addDay()->toDateString(),
            'follow_up_time' => '11:00',
            'note' => 'Discuss proposal',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('follow_ups', [
            'client_id' => $client->id,
            'influencer_id' => null,
            'note' => 'Discuss proposal',
        ]);
    }
}
