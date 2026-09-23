<?php

namespace App\Http\Controllers;

use App\Enums\InfluencerType;
use App\Models\Campaign;
use App\Models\CampaignInfluencer;
use App\Models\Client;
use App\Models\Influencer;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:reports.view');
    }

    public function index(Request $request): Response
    {
        $from = $request->get('from', now()->startOfYear()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $campaignQuery = Campaign::query()->whereBetween('created_at', [$from, $to.' 23:59:59']);
        $ciQuery = CampaignInfluencer::query()->whereBetween('created_at', [$from, $to.' 23:59:59']);

        if ($request->filled('client_id')) {
            $campaignQuery->where('client_id', $request->get('client_id'));
            $ciQuery->whereHas('campaign', fn ($q) => $q->where('client_id', $request->get('client_id')));
        }

        if ($request->filled('campaign_id')) {
            $ciQuery->where('campaign_id', $request->get('campaign_id'));
        }

        if ($request->filled('influencer_id')) {
            $ciQuery->where('influencer_id', $request->get('influencer_id'));
        }

        if ($request->filled('influencer_type')) {
            $ciQuery->whereHas('influencer', fn ($q) => $q->where('influencer_type', $request->get('influencer_type')));
        }

        if ($request->filled('location')) {
            $ciQuery->whereHas('influencer', fn ($q) => $q->where('location', $request->get('location')));
        }

        if ($request->filled('status')) {
            $campaignQuery->where('status', $request->get('status'));
        }

        $influencerSpend = (float) (clone $ciQuery)->sum('influencer_cost');
        $additionalCost = (float) (clone $ciQuery)->sum('additional_cost');
        $groveraRevenue = (float) (clone $ciQuery)->sum('grovera_fee');
        $campaignValue = (float) (clone $ciQuery)->sum('final_amount');
        $margin = $campaignValue - $influencerSpend - $additionalCost;

        $completedPayments = (float) Payment::query()
            ->whereBetween('payment_date', [$from, $to])
            ->sum('amount');

        $pendingPayments = max($campaignValue - $completedPayments, 0);

        $performance = CampaignInfluencer::query()
            ->select('influencer_id')
            ->selectRaw('COUNT(*) as campaigns_count')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count")
            ->selectRaw('SUM(influencer_cost) as total_spend')
            ->selectRaw('SUM(additional_cost) as total_additional_cost')
            ->selectRaw('AVG(influencer_cost) as average_cost')
            ->with('influencer:id,name,instagram_username,influencer_type,location')
            ->groupBy('influencer_id')
            ->orderByDesc('campaigns_count')
            ->limit(50)
            ->get();

        return Inertia::render('Reports/Index', [
            'summary' => [
                'total_campaigns' => (clone $campaignQuery)->count(),
                'total_influencers_used' => (clone $ciQuery)->distinct('influencer_id')->count('influencer_id'),
                'total_influencer_spend' => round($influencerSpend, 2),
                'total_additional_cost' => round($additionalCost, 2),
                'total_grovera_revenue' => round($groveraRevenue, 2),
                'total_campaign_value' => round($campaignValue, 2),
                'total_margin' => round($margin, 2),
                'pending_payments' => round($pendingPayments, 2),
                'completed_payments' => round($completedPayments, 2),
                'completed_campaigns' => (clone $campaignQuery)->where('status', 'completed')->count(),
            ],
            'location_wise' => Influencer::query()
                ->whereNotNull('location')
                ->selectRaw('location, COUNT(*) as count')
                ->groupBy('location')
                ->orderByDesc('count')
                ->get(),
            'type_wise' => collect(InfluencerType::cases())->map(fn (InfluencerType $type) => [
                'type' => $type->value,
                'label' => $type->label(),
                'count' => Influencer::query()->where('influencer_type', $type)->count(),
            ]),
            'monthly_revenue' => CampaignInfluencer::query()
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(final_amount) as campaign_value, SUM(grovera_fee) as grovera_revenue, SUM(influencer_cost) as influencer_spend, SUM(additional_cost) as additional_cost")
                ->whereBetween('created_at', [$from, $to.' 23:59:59'])
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
            'performance' => $performance,
            'filters' => $request->only(['from', 'to', 'client_id', 'campaign_id', 'influencer_id', 'influencer_type', 'location', 'status']),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'campaigns' => Campaign::query()->orderBy('campaign_name')->get(['id', 'campaign_name']),
        ]);
    }
}
