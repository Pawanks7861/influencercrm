<?php

namespace Database\Seeders;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\ClientStatus;
use App\Enums\CollaborationStatus;
use App\Enums\DeliverableStatus;
use App\Enums\DeliverableType;
use App\Enums\FollowUpStatus;
use App\Enums\InfluencerType;
use App\Enums\RequirementStatus;
use App\Enums\RequirementType;
use App\Enums\ShortlistItemStatus;
use App\Enums\ShortlistStatus;
use App\Models\Campaign;
use App\Models\CampaignInfluencer;
use App\Models\Client;
use App\Models\ClientCampaignVisibility;
use App\Models\ClientRequirement;
use App\Models\Deliverable;
use App\Models\FollowUp;
use App\Models\Influencer;
use App\Models\InfluencerActivity;
use App\Models\InfluencerShortlist;
use App\Models\InfluencerShortlistItem;
use App\Models\Payment;
use App\Models\RequirementDeliverable;
use App\Models\User;
use App\Services\Client\ClientLoginService;
use App\Services\Influencer\InfluencerLoginService;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for Grovera Studio walkthroughs.
 * Safe to re-run: keyed on known emails / requirement numbers.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@grovera.studio')->first()
            ?? User::query()->role('admin')->first();

        if (! $admin) {
            $this->command?->error('Admin user missing. Run DatabaseSeeder first.');

            return;
        }

        DB::transaction(function () use ($admin) {
            $riya = $this->seedInfluencers($admin);
            $neha = Influencer::query()->where('instagram_username', 'nehashah')->first();
            $priya = Influencer::query()->where('instagram_username', 'priyamehta')->first();

            $satvam = $this->seedClient($admin);
            $this->seedInfluencerLogins($riya);
            $this->seedClientLogin($satvam);

            $campaign = $this->seedSatvamCampaign($admin, $satvam, $riya);
            $this->seedFollowUps($admin, $riya, $satvam, $campaign);
            $this->seedRequirementAndShortlist($admin, $satvam, $riya, $neha, $priya, $campaign);

            $this->command?->info('Demo data ready.');
            $this->command?->table(
                ['Account', 'Login', 'Password'],
                [
                    ['Staff Admin', 'admin@grovera.studio / admin', 'password'],
                    ['Staff User', 'user@grovera.studio / user', 'password'],
                    ['Influencer (Riya)', 'riya@example.com', 'password'],
                    ['Client (Satvam)', 'satvam@example.com', 'password'],
                ]
            );
        });
    }

    protected function seedInfluencers(User $admin): Influencer
    {
        $riya = Influencer::query()->updateOrCreate(
            ['instagram_username' => 'riyapatel'],
            [
                'name' => 'Riya Patel',
                'instagram_url' => 'https://instagram.com/riyapatel',
                'mobile' => '9876543210',
                'email' => 'riya@example.com',
                'location' => 'Ahmedabad',
                'influencer_type' => InfluencerType::Premium,
                'default_price' => '15000.00',
                'youtube_url' => 'https://youtube.com/@riyapatel',
                'status' => 'active',
                'notes_summary' => 'Premium food & lifestyle creator — Ahmedabad.',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        Influencer::query()->updateOrCreate(
            ['instagram_username' => 'nehashah'],
            [
                'name' => 'Neha Shah',
                'instagram_url' => 'https://instagram.com/nehashah',
                'mobile' => '9876543211',
                'email' => 'neha@example.com',
                'location' => 'Ahmedabad',
                'influencer_type' => InfluencerType::Medium,
                'default_price' => '15000.00',
                'status' => 'active',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        Influencer::query()->updateOrCreate(
            ['instagram_username' => 'priyamehta'],
            [
                'name' => 'Priya Mehta',
                'instagram_url' => 'https://instagram.com/priyamehta',
                'mobile' => '9876543212',
                'email' => 'priya@example.com',
                'location' => 'Ahmedabad',
                'influencer_type' => InfluencerType::Medium,
                'default_price' => '11000.00',
                'status' => 'active',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        Influencer::query()->updateOrCreate(
            ['instagram_username' => 'aaravdesai'],
            [
                'name' => 'Aarav Desai',
                'instagram_url' => 'https://instagram.com/aaravdesai',
                'mobile' => '9876543213',
                'email' => 'aarav@example.com',
                'location' => 'Surat',
                'influencer_type' => InfluencerType::Low,
                'default_price' => '5000.00',
                'status' => 'active',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        return $riya->fresh();
    }

    protected function seedClient(User $admin): Client
    {
        return Client::query()->updateOrCreate(
            ['email' => 'satvam@example.com'],
            [
                'name' => 'Satvam Foods',
                'company_name' => 'Satvam Foods',
                'contact_person' => 'Karan Joshi',
                'mobile' => '9988776655',
                'phone' => '9988776655',
                'website' => 'https://satvam.example.com',
                'instagram_url' => 'https://instagram.com/satvamfoods',
                'address' => 'Ahmedabad, Gujarat',
                'status' => ClientStatus::Active,
                'notes' => 'Key FMCG client — influencer + social campaigns.',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );
    }

    protected function seedInfluencerLogins(Influencer $riya): void
    {
        if ($riya->user_id && $riya->login_enabled) {
            return;
        }

        app(InfluencerLoginService::class)->createLogin($riya, 'password', 'riya@example.com');
    }

    protected function seedClientLogin(Client $satvam): void
    {
        if ($satvam->user_id && $satvam->login_enabled) {
            return;
        }

        app(ClientLoginService::class)->createLogin($satvam, 'password', 'satvam@example.com');
    }

    protected function seedSatvamCampaign(User $admin, Client $satvam, Influencer $riya): Campaign
    {
        $campaign = Campaign::query()->updateOrCreate(
            [
                'client_id' => $satvam->id,
                'campaign_name' => 'Satvam Diwali Campaign',
            ],
            [
                'brand_name' => 'Satvam',
                'campaign_type' => CampaignType::InfluencerMarketing,
                'campaign_budget' => '60000.00',
                'start_date' => now()->subDays(20)->toDateString(),
                'deadline' => now()->addDays(10)->toDateString(),
                'posting_date' => now()->addDays(5)->toDateString(),
                'status' => CampaignStatus::Active,
                'remarks' => 'Demo campaign — Riya Patel collaboration.',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $influencerCost = '12000.00';
        $additionalCost = '2000.00';
        $groveraFee = '3000.00';
        $finalAmount = Money::add(Money::add($influencerCost, $additionalCost), $groveraFee);

        $ci = CampaignInfluencer::query()->updateOrCreate(
            [
                'campaign_id' => $campaign->id,
                'influencer_id' => $riya->id,
            ],
            [
                'influencer_cost' => $influencerCost,
                'additional_cost' => $additionalCost,
                'grovera_fee' => $groveraFee,
                'final_amount' => $finalAmount,
                'final_amount_overridden' => false,
                'status' => CollaborationStatus::Posted,
                'content_deadline' => now()->addDays(3)->toDateString(),
                'posting_date' => now()->addDays(5)->toDateString(),
                'content_url' => 'https://instagram.com/p/demo-riya-satvam',
                'remarks' => 'Negotiated from ₹15,000 to ₹12,000 + ₹2,000 production.',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        Deliverable::query()->updateOrCreate(
            [
                'campaign_influencer_id' => $ci->id,
                'type' => DeliverableType::InstagramReel,
            ],
            [
                'quantity' => 1,
                'title' => 'Diwali Reel',
                'deadline' => now()->addDays(3)->toDateString(),
                'status' => DeliverableStatus::Posted,
                'content_url' => 'https://instagram.com/reel/demo-riya',
            ]
        );

        Deliverable::query()->updateOrCreate(
            [
                'campaign_influencer_id' => $ci->id,
                'type' => DeliverableType::InstagramStory,
            ],
            [
                'quantity' => 3,
                'title' => 'Diwali Stories',
                'deadline' => now()->addDays(4)->toDateString(),
                'status' => DeliverableStatus::Submitted,
            ]
        );

        InfluencerActivity::query()->firstOrCreate(
            [
                'influencer_id' => $riya->id,
                'campaign_id' => $campaign->id,
                'activity_type' => 'price_negotiation',
                'note' => 'Negotiated influencer from ₹15,000 to ₹12,000.',
            ],
            [
                'activity_at' => now()->subDays(15),
                'created_by' => $admin->id,
            ]
        );

        if (! Payment::query()->where('campaign_influencer_id', $ci->id)->exists()) {
            Payment::query()->create([
                'campaign_id' => $campaign->id,
                'campaign_influencer_id' => $ci->id,
                'influencer_id' => $riya->id,
                'amount' => '5000.00',
                'payment_status' => 'paid',
                'payment_date' => now()->subDays(2)->toDateString(),
                'payment_method' => 'upi',
                'transaction_reference' => 'DEMO-UPI-001',
                'remarks' => 'Partial payment 1/2',
                'created_by' => $admin->id,
            ]);
        }

        ClientCampaignVisibility::query()->updateOrCreate(
            ['campaign_id' => $campaign->id],
            [
                'visible_to_client' => true,
                'show_budget' => false,
                'show_deliverables' => true,
                'show_influencers' => true,
                'show_posting_dates' => true,
                'show_content_links' => true,
                'show_payment_summary' => false,
                'show_notes' => false,
                'shared_at' => now()->subDays(5),
                'shared_by' => $admin->id,
            ]
        );

        return $campaign->fresh();
    }

    protected function seedFollowUps(User $admin, Influencer $riya, Client $satvam, Campaign $campaign): void
    {
        FollowUp::query()->updateOrCreate(
            [
                'influencer_id' => $riya->id,
                'campaign_id' => $campaign->id,
                'note' => 'Confirm Reel posting date with Riya.',
            ],
            [
                'assigned_to' => $admin->id,
                'follow_up_date' => now()->toDateString(),
                'follow_up_time' => '16:00:00',
                'status' => FollowUpStatus::Pending,
                'created_by' => $admin->id,
            ]
        );

        FollowUp::query()->updateOrCreate(
            [
                'client_id' => $satvam->id,
                'note' => 'Follow up regarding Navratri influencer proposal.',
            ],
            [
                'influencer_id' => null,
                'campaign_id' => null,
                'assigned_to' => $admin->id,
                'follow_up_date' => now()->addDay()->toDateString(),
                'follow_up_time' => '11:00:00',
                'status' => FollowUpStatus::Pending,
                'created_by' => $admin->id,
            ]
        );

        FollowUp::query()->updateOrCreate(
            [
                'influencer_id' => $riya->id,
                'note' => 'Overdue: product dispatch confirmation.',
            ],
            [
                'campaign_id' => $campaign->id,
                'assigned_to' => $admin->id,
                'follow_up_date' => now()->subDays(2)->toDateString(),
                'follow_up_time' => '10:00:00',
                'status' => FollowUpStatus::Pending,
                'created_by' => $admin->id,
            ]
        );
    }

    protected function seedRequirementAndShortlist(
        User $admin,
        Client $satvam,
        Influencer $riya,
        ?Influencer $neha,
        ?Influencer $priya,
        Campaign $existingCampaign
    ): void {
        $requirement = ClientRequirement::query()->updateOrCreate(
            ['requirement_number' => 'REQ-2026-DEMO'],
            [
                'client_id' => $satvam->id,
                'requirement_type' => RequirementType::InfluencerMarketing,
                'title' => 'Navratri Influencer Campaign',
                'brand_name' => 'Satvam',
                'description' => 'Need 3 Ahmedabad food influencers for Navratri. Focus on festive recipes and product unboxing.',
                'budget_min' => '50000.00',
                'budget_max' => '60000.00',
                'preferred_start_date' => now()->addDays(7)->toDateString(),
                'preferred_end_date' => now()->addDays(30)->toDateString(),
                'expected_posting_date' => now()->addDays(20)->toDateString(),
                'preferred_location' => 'Ahmedabad',
                'preferred_category' => 'premium',
                'preferred_platforms' => ['instagram', 'youtube'],
                'influencers_required' => 3,
                'target_audience' => 'Gujarati households, food lovers 25-45',
                'additional_instructions' => 'Prefer creators who have posted festive content before.',
                'status' => RequirementStatus::Shortlisting,
                'submitted_at' => now()->subDays(3),
                'assigned_to' => $admin->id,
                'internal_notes' => 'Staff only: margin target ~20%. Do not share internal costs.',
            ]
        );

        RequirementDeliverable::query()->updateOrCreate(
            [
                'client_requirement_id' => $requirement->id,
                'deliverable_type' => 'instagram_reel',
            ],
            [
                'platform' => 'instagram',
                'quantity' => 1,
                'notes' => 'Main hero reel',
            ]
        );

        RequirementDeliverable::query()->updateOrCreate(
            [
                'client_requirement_id' => $requirement->id,
                'deliverable_type' => 'instagram_story',
            ],
            [
                'platform' => 'instagram',
                'quantity' => 3,
            ]
        );

        $shortlist = InfluencerShortlist::query()->updateOrCreate(
            [
                'client_requirement_id' => $requirement->id,
                'title' => 'Shortlist #1 — Ahmedabad creators',
            ],
            [
                'status' => ShortlistStatus::Shared,
                'shared_at' => now()->subDay(),
                'shared_by' => $admin->id,
                'expires_at' => now()->addDays(14)->toDateString(),
                'message' => 'Here are our recommended influencers for your Navratri campaign.',
                'created_by' => $admin->id,
            ]
        );

        $items = [
            [$riya, '18000.00', ShortlistItemStatus::Selected, 'Can you check whether Riya can do ₹16,000?'],
            [$neha, '20000.00', ShortlistItemStatus::Rejected, null],
            [$priya, '17000.00', ShortlistItemStatus::Interested, null],
        ];

        $order = 1;
        foreach ($items as [$inf, $clientPrice, $status, $remark]) {
            if (! $inf) {
                continue;
            }

            InfluencerShortlistItem::query()->updateOrCreate(
                [
                    'influencer_shortlist_id' => $shortlist->id,
                    'influencer_id' => $inf->id,
                ],
                [
                    'display_name' => $inf->name,
                    'instagram_url' => $inf->instagram_url,
                    'instagram_username' => $inf->instagram_username,
                    'location' => $inf->location,
                    'influencer_type' => $inf->influencer_type?->value ?? $inf->influencer_type,
                    'client_price' => $clientPrice,
                    'description' => null,
                    'display_order' => $order++,
                    'status' => $status,
                    'client_remark' => $remark,
                    'show_name' => true,
                    'show_instagram' => true,
                    'show_price' => true,
                    'show_location' => false,
                    'show_type' => false,
                    'show_note' => true,
                    'responded_at' => $status === ShortlistItemStatus::Pending ? null : now()->subHours(6),
                ]
            );
        }

        // Keep converted_campaign_id null so staff can still demo "Convert Selected"
        // The separate Diwali campaign already shows a live collaboration.
        unset($existingCampaign);
    }
}
