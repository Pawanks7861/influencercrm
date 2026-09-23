<?php

namespace Tests\Feature\Crm;

use App\Enums\ActivityType;
use App\Enums\CampaignStatus;
use App\Enums\CollaborationStatus;
use App\Enums\DeliverableType;
use App\Enums\InfluencerType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Deliverable;
use App\Models\Influencer;
use App\Models\InfluencerActivity;
use App\Services\Campaign\CampaignService;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RiyaPatelSatvamWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_riya_patel_satvam_end_to_end_workflow(): void
    {
        $admin = $this->actingAsAdmin();

        $this->post(route('clients.store'), [
            'company_name' => 'Satvam Foods',
            'name' => 'Satvam',
            'contact_person' => 'Satvam Admin',
            'mobile' => '9988776655',
            'status' => 'active',
            'force' => true,
        ])->assertRedirect();

        $client = Client::query()->where('company_name', 'Satvam Foods')->firstOrFail();

        $this->post(route('influencers.store'), [
            'name' => 'Riya Patel',
            'instagram_username' => '@riyapatel',
            'location' => 'Ahmedabad',
            'influencer_type' => InfluencerType::Premium->value,
            'default_price' => 15000,
            'email' => 'riya.patel@example.com',
            'mobile' => '9876501234',
            'status' => 'active',
        ])->assertRedirect();

        $riya = Influencer::query()->where('instagram_username', 'riyapatel')->firstOrFail();
        $this->assertSame('Ahmedabad', $riya->location);
        $this->assertEquals(15000, (float) $riya->default_price);

        $this->post(route('campaigns.store'), [
            'campaign_name' => 'Satvam Diwali Campaign',
            'client_id' => $client->id,
            'brand_name' => 'Satvam',
            'campaign_type' => 'influencer_marketing',
            'status' => CampaignStatus::Active->value,
            'influencers' => [
                [
                    'influencer_id' => $riya->id,
                    'influencer_cost' => 12000,
                    'grovera_fee' => 3000,
                    'status' => CollaborationStatus::PriceConfirmed->value,
                ],
            ],
        ])->assertRedirect();

        $campaign = Campaign::query()->where('campaign_name', 'Satvam Diwali Campaign')->firstOrFail();
        $row = $campaign->campaignInfluencers()->firstOrFail();

        $this->assertEquals(12000, (float) $row->influencer_cost);
        $this->assertEquals(3000, (float) $row->grovera_fee);
        $this->assertEquals(15000, (float) $row->final_amount);

        $this->post(route('deliverables.store'), [
            'campaign_influencer_id' => $row->id,
            'type' => DeliverableType::InstagramReel->value,
            'quantity' => 1,
            'title' => 'Diwali Reel',
        ])->assertRedirect();

        $this->post(route('deliverables.store'), [
            'campaign_influencer_id' => $row->id,
            'type' => DeliverableType::InstagramStory->value,
            'quantity' => 3,
            'title' => 'Diwali Stories',
        ])->assertRedirect();

        $this->assertSame(2, Deliverable::query()->where('campaign_influencer_id', $row->id)->count());
        $this->assertSame(3, (int) Deliverable::query()
            ->where('campaign_influencer_id', $row->id)
            ->where('type', DeliverableType::InstagramStory->value)
            ->value('quantity'));

        $this->post(route('activities.store'), [
            'influencer_id' => $riya->id,
            'campaign_id' => $campaign->id,
            'activity_type' => ActivityType::PriceNegotiation->value,
            'note' => 'Negotiated influencer from ₹15,000 to ₹12,000.',
        ])->assertRedirect();

        $this->assertDatabaseHas('influencer_activities', [
            'influencer_id' => $riya->id,
            'campaign_id' => $campaign->id,
            'activity_type' => ActivityType::PriceNegotiation->value,
        ]);

        $this->post(route('follow-ups.store'), [
            'influencer_id' => $riya->id,
            'campaign_id' => $campaign->id,
            'assigned_to' => $admin->id,
            'follow_up_date' => now()->addDay()->toDateString(),
            'note' => 'Check content progress',
        ])->assertRedirect();

        $statuses = [
            CollaborationStatus::ProductReceived,
            CollaborationStatus::ScriptShared,
            CollaborationStatus::ContentInProgress,
            CollaborationStatus::ContentReceived,
            CollaborationStatus::SentForApproval,
            CollaborationStatus::Approved,
            CollaborationStatus::Scheduled,
            CollaborationStatus::Posted,
        ];

        foreach ($statuses as $status) {
            $this->patch(route('campaign-influencers.status', $row), [
                'status' => $status->value,
            ])->assertRedirect();
        }

        $row->refresh();
        $this->assertSame(CollaborationStatus::Posted, $row->status);

        $this->post(route('payments.store'), [
            'campaign_influencer_id' => $row->id,
            'amount' => 5000,
            'payment_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::Upi->value,
        ])->assertRedirect();

        $partial = app(PaymentService::class)->deriveStatus($row->fresh());
        $this->assertEquals(5000.0, $partial['amount_paid']);
        $this->assertEquals(10000.0, $partial['amount_pending']);
        $this->assertSame(PaymentStatus::PartiallyPaid->value, $partial['payment_status']);

        $this->post(route('payments.store'), [
            'campaign_influencer_id' => $row->id,
            'amount' => 10000,
            'payment_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::BankTransfer->value,
        ])->assertRedirect();

        $paid = app(PaymentService::class)->deriveStatus($row->fresh());
        $this->assertEquals(15000.0, $paid['amount_paid']);
        $this->assertEquals(0.0, $paid['amount_pending']);
        $this->assertSame(PaymentStatus::Paid->value, $paid['payment_status']);

        $this->patch(route('campaign-influencers.status', $row), [
            'status' => CollaborationStatus::Completed->value,
        ])->assertRedirect();

        $this->assertSame(CollaborationStatus::Completed, $row->fresh()->status);

        $this->get(route('influencers.show', $riya))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Influencers/Show')
                ->has('influencer.campaign_influencers', 1)
                ->where('influencer.campaign_influencers.0.campaign_id', $campaign->id)
            );

        $this->get(route('campaigns.show', $campaign))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Campaigns/Show')
                ->has('campaign.campaign_influencers', 1)
                ->where('campaign.campaign_influencers.0.influencer_id', $riya->id)
                ->where('totals.total_final_amount', 15000)
            );

        $totals = app(CampaignService::class)->totals($campaign->fresh('campaignInfluencers'));
        $this->assertSame(15000.0, $totals['total_final_amount']);
        $this->assertDatabaseCount('influencer_activities', InfluencerActivity::query()->count());
    }
}
