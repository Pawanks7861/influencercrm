<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $visibility = $this->clientVisibility;

        $data = [
            'id' => $this->id,
            'campaign_name' => $this->campaign_name,
            'brand_name' => $this->brand_name,
            'campaign_type' => $this->campaign_type?->value ?? $this->campaign_type,
            'status' => $this->status?->value ?? $this->status,
            'platforms' => $this->platforms,
            'visibility' => $visibility ? [
                'show_budget' => (bool) $visibility->show_budget,
                'show_deliverables' => (bool) $visibility->show_deliverables,
                'show_influencers' => (bool) $visibility->show_influencers,
                'show_posting_dates' => (bool) $visibility->show_posting_dates,
                'show_content_links' => (bool) $visibility->show_content_links,
                'show_payment_summary' => (bool) $visibility->show_payment_summary,
                'show_notes' => (bool) $visibility->show_notes,
            ] : null,
        ];

        if ($visibility?->show_budget) {
            $data['campaign_budget'] = $this->campaign_budget;
        }

        if ($visibility?->show_posting_dates) {
            $data['start_date'] = optional($this->start_date)->toDateString();
            $data['deadline'] = optional($this->deadline)->toDateString();
            $data['posting_date'] = optional($this->posting_date)->toDateString();
        }

        if ($visibility?->show_notes) {
            $data['remarks'] = $this->remarks;
        }

        if ($visibility?->show_influencers) {
            $rows = $this->relationLoaded('campaignInfluencers')
                ? $this->campaignInfluencers
                : collect();

            $data['influencers'] = $rows->map(function ($ci) use ($visibility) {
                $influencer = $ci->influencer;
                $row = [
                    'id' => $ci->id,
                    'display_name' => $influencer?->name,
                    'instagram_username' => $influencer?->instagram_username,
                    'instagram_url' => $influencer?->instagram_url,
                    'status' => $ci->status?->value ?? $ci->status,
                ];

                if ($visibility->show_posting_dates) {
                    $row['posting_date'] = optional($ci->posting_date)->toDateString();
                    $row['content_deadline'] = optional($ci->content_deadline)->toDateString();
                }

                if ($visibility->show_content_links) {
                    $row['content_url'] = $ci->content_url;
                }

                return $row;
            })->values()->all();
        }

        if ($visibility?->show_deliverables) {
            $deliverables = $this->relationLoaded('deliverables')
                ? $this->deliverables
                : collect();

            $data['deliverables'] = $deliverables
                ->whereNull('campaign_influencer_id')
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'type' => $d->type?->value ?? $d->type,
                    'platform' => $d->platform,
                    'quantity' => $d->quantity,
                    'title' => $d->title,
                    'status' => $d->status?->value ?? $d->status,
                    'deadline' => $visibility->show_posting_dates ? optional($d->deadline)->toDateString() : null,
                    'content_url' => $visibility->show_content_links ? $d->content_url : null,
                ])
                ->values()
                ->all();
        }

        if ($visibility?->show_payment_summary) {
            $rows = $this->relationLoaded('campaignInfluencers')
                ? $this->campaignInfluencers
                : collect();

            $totalFinal = (float) $rows->sum('final_amount');
            $totalPaid = (float) $rows->sum(fn ($ci) => (float) $ci->amount_paid);

            $data['payment_summary'] = [
                'total_campaign_value' => round($totalFinal + (float) ($this->service_fee ?? 0), 2),
                'amount_paid' => round($totalPaid, 2),
                'amount_pending' => round(max($totalFinal - $totalPaid, 0), 2),
            ];
        }

        return $data;
    }
}
