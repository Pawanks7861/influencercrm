<?php

namespace App\Exports;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CampaignsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected Request $request) {}

    public function query()
    {
        $query = Campaign::query()->with(['client', 'campaignInfluencers']);

        if ($status = $this->request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $this->request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($campaignType = $this->request->get('campaign_type')) {
            $query->where('campaign_type', $campaignType);
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'Campaign',
            'Client',
            'Brand',
            'Campaign Type',
            'Budget',
            'Start Date',
            'Deadline',
            'Status',
            'Influencer Count',
            'Total Influencer Cost',
            'Total Additional Cost',
            'Total Grovera Fee',
            'Total Final Amount',
        ];
    }

    public function map($campaign): array
    {
        $rows = $campaign->campaignInfluencers;

        return [
            $campaign->campaign_name,
            $campaign->client?->company_name ?: $campaign->client?->name,
            $campaign->brand_name,
            $campaign->campaign_type?->value ?? $campaign->campaign_type,
            $campaign->campaign_budget,
            optional($campaign->start_date)->format('Y-m-d'),
            optional($campaign->deadline)->format('Y-m-d'),
            $campaign->status?->value ?? $campaign->status,
            $rows->count(),
            $rows->sum('influencer_cost'),
            $rows->sum('additional_cost'),
            $rows->sum('grovera_fee'),
            $rows->sum('final_amount'),
        ];
    }
}
