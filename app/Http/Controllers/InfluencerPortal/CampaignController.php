<?php

namespace App\Http\Controllers\InfluencerPortal;

use App\Http\Controllers\Controller;
use App\Models\CampaignInfluencer;
use App\Models\Influencer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function index(Request $request): Response
    {
        $influencer = $this->influencer();

        $campaigns = CampaignInfluencer::query()
            ->where('influencer_id', $influencer->id)
            ->with([
                'campaign:id,campaign_name,brand_name,status,start_date,deadline,posting_date,campaign_type,platforms,remarks',
                'campaign.client:id,name,company_name',
            ])
            ->withSum('payments as payments_sum_amount', 'amount')
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn (CampaignInfluencer $row) => $this->transformRow($row));

        return Inertia::render('InfluencerPortal/Campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function show(int $campaignInfluencer): Response
    {
        $influencer = $this->influencer();

        $row = CampaignInfluencer::query()
            ->where('influencer_id', $influencer->id)
            ->where('id', $campaignInfluencer)
            ->with([
                'campaign:id,campaign_name,brand_name,status,start_date,deadline,posting_date,campaign_type,platforms,remarks',
                'campaign.client:id,name,company_name',
                'deliverables',
            ])
            ->withSum('payments as payments_sum_amount', 'amount')
            ->firstOrFail();

        return Inertia::render('InfluencerPortal/Campaigns/Show', [
            'campaign' => $this->transformRow($row, true),
        ]);
    }

    protected function transformRow(CampaignInfluencer $row, bool $detailed = false): array
    {
        $paid = (float) ($row->payments_sum_amount ?? 0);
        $cost = (float) $row->influencer_cost;

        $data = [
            'id' => $row->id,
            'campaign_id' => $row->campaign_id,
            'campaign_name' => $row->campaign?->campaign_name,
            'brand_name' => $row->campaign?->brand_name,
            'client_name' => $row->campaign?->client?->company_name ?: $row->campaign?->client?->name,
            'campaign_type' => $row->campaign?->campaign_type?->value ?? $row->campaign?->campaign_type,
            'campaign_status' => $row->campaign?->status?->value ?? $row->campaign?->status,
            'status' => $row->status?->value ?? $row->status,
            'influencer_cost' => $row->influencer_cost,
            'amount_paid' => round($paid, 2),
            'amount_pending' => round(max($cost - $paid, 0), 2),
            'content_deadline' => optional($row->content_deadline)->toDateString(),
            'posting_date' => optional($row->posting_date)->toDateString(),
            'start_date' => optional($row->campaign?->start_date)->toDateString(),
            'deadline' => optional($row->campaign?->deadline)->toDateString(),
            'platforms' => $row->campaign?->platforms,
            'content_url' => $row->content_url,
            'content_approval_status' => $row->content_approval_status?->value ?? $row->content_approval_status,
            'remarks' => $row->remarks,
        ];

        if ($detailed) {
            $data['campaign_remarks'] = $row->campaign?->remarks;
            $data['deliverables'] = $row->deliverables->map(fn ($d) => [
                'id' => $d->id,
                'type' => $d->type?->value ?? $d->type,
                'title' => $d->title,
                'description' => $d->description,
                'deadline' => optional($d->deadline)->toDateString(),
                'status' => $d->status?->value ?? $d->status,
                'content_url' => $d->content_url,
                'quantity' => $d->quantity,
            ]);
        }

        return $data;
    }

    protected function influencer(): Influencer
    {
        return Auth::user()->influencer;
    }
}
