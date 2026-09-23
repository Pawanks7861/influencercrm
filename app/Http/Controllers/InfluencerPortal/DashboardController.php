<?php

namespace App\Http\Controllers\InfluencerPortal;

use App\Enums\CollaborationStatus;
use App\Enums\DeliverableStatus;
use App\Http\Controllers\Controller;
use App\Models\CampaignInfluencer;
use App\Models\Deliverable;
use App\Models\Influencer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $influencer = $this->influencer();

        $activeStatuses = CollaborationStatus::activeValues();

        $campaignRows = CampaignInfluencer::query()
            ->where('influencer_id', $influencer->id)
            ->with(['campaign:id,campaign_name,brand_name,status,deadline,start_date', 'payments'])
            ->withSum('payments as payments_sum_amount', 'amount')
            ->latest()
            ->get();

        $deliverables = Deliverable::query()
            ->whereHas('campaignInfluencer', fn ($q) => $q->where('influencer_id', $influencer->id))
            ->with(['campaign:id,campaign_name', 'campaignInfluencer:id,campaign_id,influencer_id'])
            ->get();

        $pendingDeliverables = $deliverables->filter(fn (Deliverable $d) => in_array(
            $d->status?->value ?? $d->status,
            [DeliverableStatus::Pending->value, DeliverableStatus::InProgress->value],
            true
        ));

        $completedDeliverables = $deliverables->filter(fn (Deliverable $d) => in_array(
            $d->status?->value ?? $d->status,
            [DeliverableStatus::Approved->value, DeliverableStatus::Posted->value],
            true
        ));

        $pendingPaymentAmount = $campaignRows->sum(function (CampaignInfluencer $row) {
            $paid = (float) ($row->payments_sum_amount ?? 0);
            $cost = (float) $row->influencer_cost;

            return max($cost - $paid, 0);
        });

        $unpaidCount = $campaignRows->filter(function (CampaignInfluencer $row) {
            $paid = (float) ($row->payments_sum_amount ?? 0);

            return $paid < (float) $row->influencer_cost;
        })->count();

        $upcomingDeadlines = $deliverables
            ->filter(fn (Deliverable $d) => $d->deadline && $d->deadline->isFuture())
            ->sortBy('deadline')
            ->take(5)
            ->values()
            ->map(fn (Deliverable $d) => [
                'id' => $d->id,
                'title' => $d->title ?: ($d->type?->value ?? 'Deliverable'),
                'deadline' => optional($d->deadline)->toDateString(),
                'status' => $d->status?->value ?? $d->status,
                'campaign_name' => $d->campaign?->campaign_name,
            ]);

        $statusValue = fn (CampaignInfluencer $row) => $row->status?->value ?? $row->status;

        return Inertia::render('InfluencerPortal/Dashboard', [
            'stats' => [
                'active_campaigns' => $campaignRows->filter(fn ($row) => in_array($statusValue($row), $activeStatuses, true))->count(),
                'pending_deliverables' => $pendingDeliverables->count(),
                'completed_deliverables' => $completedDeliverables->count(),
                'pending_payments_amount' => round($pendingPaymentAmount, 2),
                'pending_payments_count' => $unpaidCount,
                'completed_campaigns' => $campaignRows->filter(fn ($row) => $statusValue($row) === CollaborationStatus::Completed->value)->count(),
            ],
            'upcoming_deadlines' => $upcomingDeadlines,
            'recent_campaigns' => $campaignRows->take(5)->map(fn (CampaignInfluencer $row) => [
                'id' => $row->id,
                'campaign_id' => $row->campaign_id,
                'campaign_name' => $row->campaign?->campaign_name,
                'brand_name' => $row->campaign?->brand_name,
                'status' => $row->status?->value ?? $row->status,
                'influencer_cost' => $row->influencer_cost,
                'content_deadline' => optional($row->content_deadline)->toDateString(),
                'deadline' => optional($row->campaign?->deadline)->toDateString(),
            ]),
            'influencer' => [
                'id' => $influencer->id,
                'name' => $influencer->name,
            ],
        ]);
    }

    protected function influencer(): Influencer
    {
        return Auth::user()->influencer;
    }
}
