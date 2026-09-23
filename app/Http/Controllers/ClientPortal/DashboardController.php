<?php

namespace App\Http\Controllers\ClientPortal;

use App\Enums\CampaignStatus;
use App\Enums\RequirementStatus;
use App\Enums\RequirementType;
use App\Enums\ShortlistItemStatus;
use App\Enums\ShortlistStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientRequirement;
use App\Models\InfluencerShortlist;
use App\Models\InfluencerShortlistItem;
use App\Models\PortalNotification;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $client = $this->client();

        $openStatuses = [
            RequirementStatus::Submitted->value,
            RequirementStatus::UnderReview->value,
            RequirementStatus::NeedMoreInformation->value,
            RequirementStatus::Shortlisting->value,
            RequirementStatus::ProposalShared->value,
            RequirementStatus::ClientReviewing->value,
            RequirementStatus::Approved->value,
        ];

        $requirementsQuery = ClientRequirement::query()->where('client_id', $client->id);

        $sharedCampaignsQuery = Campaign::query()
            ->where('client_id', $client->id)
            ->whereHas('clientVisibility', fn ($q) => $q->where('visible_to_client', true));

        $shortlistsAwaiting = InfluencerShortlist::query()
            ->whereHas('requirement', fn ($q) => $q->where('client_id', $client->id))
            ->whereIn('status', [ShortlistStatus::Shared->value, ShortlistStatus::Viewed->value])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString());
            });

        $pendingApprovals = InfluencerShortlistItem::query()
            ->where('status', ShortlistItemStatus::Pending)
            ->whereHas('shortlist', function ($q) use ($client) {
                $q->whereIn('status', ShortlistStatus::clientVisibleValues())
                    ->where(function ($inner) {
                        $inner->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString());
                    })
                    ->whereHas('requirement', fn ($rq) => $rq->where('client_id', $client->id));
            });

        $stats = [
            'open_requirements' => (clone $requirementsQuery)->whereIn('status', $openStatuses)->count(),
            'influencer_requirements' => (clone $requirementsQuery)->whereIn('requirement_type', [
                RequirementType::InfluencerMarketing->value,
                RequirementType::Both->value,
            ])->whereIn('status', $openStatuses)->count(),
            'social_media_requirements' => (clone $requirementsQuery)->whereIn('requirement_type', [
                RequirementType::SocialMediaMarketing->value,
                RequirementType::Both->value,
            ])->whereIn('status', $openStatuses)->count(),
            'shortlists_awaiting_response' => (clone $shortlistsAwaiting)->count(),
            'shared_shortlists' => (clone $shortlistsAwaiting)->count(),
            'active_campaigns' => (clone $sharedCampaignsQuery)->where('status', CampaignStatus::Active)->count(),
            'visible_campaigns' => (clone $sharedCampaignsQuery)->count(),
            'pending_approvals' => (clone $pendingApprovals)->count(),
            'completed_campaigns' => (clone $sharedCampaignsQuery)->where('status', CampaignStatus::Completed)->count(),
        ];

        $recentRequirements = ClientRequirement::query()
            ->where('client_id', $client->id)
            ->latest()
            ->limit(5)
            ->get(['id', 'requirement_number', 'title', 'status', 'requirement_type', 'submitted_at']);

        $recentShortlists = InfluencerShortlist::query()
            ->whereHas('requirement', fn ($q) => $q->where('client_id', $client->id))
            ->whereIn('status', ShortlistStatus::clientVisibleValues())
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString());
            })
            ->with(['requirement:id,requirement_number,title'])
            ->latest('shared_at')
            ->limit(5)
            ->get(['id', 'title', 'status', 'shared_at', 'client_requirement_id']);

        $currentCampaigns = (clone $sharedCampaignsQuery)
            ->whereIn('status', [CampaignStatus::Active->value, CampaignStatus::Draft->value])
            ->latest()
            ->limit(5)
            ->get(['id', 'campaign_name', 'brand_name', 'status', 'campaign_type']);

        $notifications = PortalNotification::query()
            ->where('user_id', Auth::id())
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('ClientPortal/Dashboard', [
            'stats' => $stats,
            'recent_requirements' => $recentRequirements,
            'recent_shortlists' => $recentShortlists,
            'current_campaigns' => $currentCampaigns,
            'notifications' => $notifications,
            'client' => [
                'id' => $client->id,
                'name' => $client->displayName(),
            ],
        ]);
    }

    protected function client(): Client
    {
        return Auth::user()->client;
    }
}
