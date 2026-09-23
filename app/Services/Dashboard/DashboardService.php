<?php

namespace App\Services\Dashboard;

use App\Enums\CollaborationStatus;
use App\Enums\FollowUpStatus;
use App\Enums\InfluencerType;
use App\Enums\RequirementStatus;
use App\Enums\ShortlistItemStatus;
use App\Enums\ShortlistStatus;
use App\Models\CampaignInfluencer;
use App\Models\ClientRequirement;
use App\Models\FollowUp;
use App\Models\Influencer;
use App\Models\InfluencerActivity;
use App\Models\InfluencerShortlist;
use App\Models\InfluencerShortlistItem;
use App\Models\Payment;
use Carbon\Carbon;

class DashboardService
{
    public function getMetrics(?string $range = 'this_month', ?string $from = null, ?string $to = null): array
    {
        [$start, $end] = $this->resolveDateRange($range, $from, $to);

        $influencerCounts = Influencer::query()
            ->where('status', 'active')
            ->selectRaw('influencer_type, COUNT(*) as aggregate')
            ->groupBy('influencer_type')
            ->pluck('aggregate', 'influencer_type');

        $activeStatuses = CollaborationStatus::activeValues();

        $collaborationCounts = CampaignInfluencer::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $activeCollaborations = collect($activeStatuses)->sum(fn ($s) => (int) ($collaborationCounts[$s] ?? 0));
        $pendingCollaborations = (int) (
            ($collaborationCounts[CollaborationStatus::NewLead->value] ?? 0)
            + ($collaborationCounts[CollaborationStatus::Contacted->value] ?? 0)
            + ($collaborationCounts[CollaborationStatus::Negotiating->value] ?? 0)
        );

        $datedQuery = CampaignInfluencer::query()
            ->whereBetween('created_at', [$start, $end]);

        $totalInfluencerCost = (float) (clone $datedQuery)->sum('influencer_cost');
        $totalAdditionalCost = (float) (clone $datedQuery)->sum('additional_cost');
        $totalGroveraFee = (float) (clone $datedQuery)->sum('grovera_fee');
        $totalFinalAmount = (float) (clone $datedQuery)->sum('final_amount');
        $totalMargin = $totalFinalAmount - $totalInfluencerCost - $totalAdditionalCost;

        $paymentsInRange = Payment::query()->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()]);
        $completedPayments = (float) (clone $paymentsInRange)->sum('amount');

        $pendingPayments = (float) CampaignInfluencer::query()
            ->selectRaw('COALESCE(SUM(final_amount - COALESCE(paid.total_paid, 0)), 0) as pending')
            ->leftJoinSub(
                Payment::query()
                    ->selectRaw('campaign_influencer_id, SUM(amount) as total_paid')
                    ->groupBy('campaign_influencer_id'),
                'paid',
                'paid.campaign_influencer_id',
                '=',
                'campaign_influencers.id'
            )
            ->whereRaw('final_amount > COALESCE(paid.total_paid, 0)')
            ->value('pending');

        return [
            'kpis' => [
                'total_influencers' => (int) $influencerCounts->sum(),
                'premium_influencers' => (int) ($influencerCounts[InfluencerType::Premium->value] ?? 0),
                'medium_influencers' => (int) ($influencerCounts[InfluencerType::Medium->value] ?? 0),
                'low_influencers' => (int) ($influencerCounts[InfluencerType::Low->value] ?? 0),
                'active_collaborations' => $activeCollaborations,
                'pending_collaborations' => $pendingCollaborations,
                'completed_collaborations' => (int) ($collaborationCounts[CollaborationStatus::Completed->value] ?? 0),
                'cancelled_collaborations' => (int) ($collaborationCounts[CollaborationStatus::Cancelled->value] ?? 0),
                'total_influencer_cost' => round($totalInfluencerCost, 2),
                'total_additional_cost' => round($totalAdditionalCost, 2),
                'total_grovera_fee' => round($totalGroveraFee, 2),
                'total_final_amount' => round($totalFinalAmount, 2),
                'total_margin' => round($totalMargin, 2),
                'pending_payments' => round(max($pendingPayments, 0), 2),
                'completed_payments' => round($completedPayments, 2),
                'today_follow_ups' => FollowUp::query()
                    ->where('status', FollowUpStatus::Pending)
                    ->whereDate('follow_up_date', Carbon::today())
                    ->count(),
                'overdue_follow_ups' => FollowUp::query()
                    ->where('status', FollowUpStatus::Pending)
                    ->whereDate('follow_up_date', '<', Carbon::today())
                    ->count(),
                'new_client_requirements' => ClientRequirement::query()
                    ->where('status', RequirementStatus::Submitted)
                    ->count(),
                'requirements_under_review' => ClientRequirement::query()
                    ->where('status', RequirementStatus::UnderReview)
                    ->count(),
                'shortlists_awaiting_client_response' => InfluencerShortlist::query()
                    ->whereIn('status', [ShortlistStatus::Shared->value, ShortlistStatus::Viewed->value])
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString());
                    })
                    ->count(),
                'client_selections_awaiting_campaign' => InfluencerShortlistItem::query()
                    ->whereIn('status', [ShortlistItemStatus::Selected->value, ShortlistItemStatus::Interested->value])
                    ->whereHas('shortlist.requirement', function ($q) {
                        $q->whereNotIn('status', [
                            RequirementStatus::ConvertedToCampaign->value,
                            RequirementStatus::Cancelled->value,
                            RequirementStatus::Closed->value,
                            RequirementStatus::Rejected->value,
                        ])
                            ->whereNull('converted_campaign_id');
                    })
                    ->count(),
            ],
            'charts' => [
                'influencers_by_location' => Influencer::query()
                    ->where('status', 'active')
                    ->whereNotNull('location')
                    ->where('location', '!=', '')
                    ->selectRaw('location, COUNT(*) as count')
                    ->groupBy('location')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->get(),
                'influencers_by_type' => collect(InfluencerType::cases())->map(fn (InfluencerType $type) => [
                    'type' => $type->value,
                    'label' => $type->label(),
                    'count' => (int) ($influencerCounts[$type->value] ?? 0),
                ])->values(),
                'collaboration_status' => collect(CollaborationStatus::cases())->map(fn (CollaborationStatus $status) => [
                    'status' => $status->value,
                    'label' => $status->label(),
                    'count' => (int) ($collaborationCounts[$status->value] ?? 0),
                ])->values(),
                'monthly_collaboration_value' => CampaignInfluencer::query()
                    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(final_amount) as total_value, SUM(influencer_cost) as influencer_spend, SUM(additional_cost) as additional_cost, SUM(grovera_fee) as grovera_revenue")
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get(),
            ],
            'recent_activity' => InfluencerActivity::query()
                ->with(['influencer:id,name', 'creator:id,name', 'campaign:id,campaign_name'])
                ->latest('activity_at')
                ->limit(15)
                ->get(),
            'date_range' => [
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                'range' => $range,
            ],
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveDateRange(?string $range, ?string $from, ?string $to): array
    {
        $now = Carbon::now();

        return match ($range) {
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_quarter' => [$now->copy()->firstOfQuarter()->startOfDay(), $now->copy()->lastOfQuarter()->endOfDay()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => [
                $from ? Carbon::parse($from)->startOfDay() : $now->copy()->startOfMonth(),
                $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}
