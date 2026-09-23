<?php

namespace App\Services\Campaign;

use App\Enums\CampaignType;
use App\Enums\CollaborationStatus;
use App\Enums\DeliverableStatus;
use App\Models\Campaign;
use App\Models\CampaignInfluencer;
use App\Models\Deliverable;
use App\Models\Influencer;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignService
{
    public function __construct(
        protected PricingService $pricingService
    ) {}

    public function create(array $data, array $influencers = [], array $deliverables = []): Campaign
    {
        return DB::transaction(function () use ($data, $influencers, $deliverables) {
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['campaign_type'] = $data['campaign_type'] ?? CampaignType::InfluencerMarketing->value;

            $campaign = Campaign::create($data);
            $type = CampaignType::tryFrom($campaign->campaign_type?->value ?? $campaign->campaign_type)
                ?? CampaignType::InfluencerMarketing;

            if ($type->includesInfluencers()) {
                $this->syncInfluencers($campaign, $influencers);
            }

            if ($type->includesSocialMedia()) {
                $this->syncCampaignDeliverables($campaign, $deliverables);
            }

            return $campaign->load(['client', 'campaignInfluencers.influencer', 'deliverables']);
        });
    }

    public function update(Campaign $campaign, array $data, ?array $influencers = null, ?array $deliverables = null): Campaign
    {
        return DB::transaction(function () use ($campaign, $data, $influencers, $deliverables) {
            $data['updated_by'] = Auth::id();
            $campaign->update($data);

            $type = CampaignType::tryFrom($campaign->fresh()->campaign_type?->value ?? $campaign->campaign_type)
                ?? CampaignType::InfluencerMarketing;

            if ($influencers !== null && $type->includesInfluencers()) {
                $this->syncInfluencers($campaign, $influencers);
            }

            if ($deliverables !== null && $type->includesSocialMedia()) {
                $this->syncCampaignDeliverables($campaign, $deliverables);
            }

            return $campaign->fresh(['client', 'campaignInfluencers.influencer', 'deliverables']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $influencers
     */
    public function syncInfluencers(Campaign $campaign, array $influencers): void
    {
        $seen = [];

        foreach ($influencers as $row) {
            $influencerId = (int) ($row['influencer_id'] ?? 0);

            if (! $influencerId) {
                continue;
            }

            if (isset($seen[$influencerId])) {
                throw ValidationException::withMessages([
                    'influencers' => 'Duplicate influencer in campaign is not allowed.',
                ]);
            }

            $seen[$influencerId] = true;

            $influencer = Influencer::findOrFail($influencerId);

            $influencerCost = array_key_exists('influencer_cost', $row)
                ? (float) $row['influencer_cost']
                : (float) $influencer->default_price;

            $additionalCost = (float) ($row['additional_cost'] ?? 0);
            $groveraFee = (float) ($row['grovera_fee'] ?? 0);
            $overridden = (bool) ($row['final_amount_overridden'] ?? false);
            $finalAmount = $overridden && isset($row['final_amount'])
                ? (float) $row['final_amount']
                : (float) $this->pricingService->calculateFinalAmount($influencerCost, $additionalCost, $groveraFee);

            $payload = [
                'influencer_cost' => Money::of($influencerCost),
                'additional_cost' => Money::of($additionalCost),
                'grovera_fee' => Money::of($groveraFee),
                'final_amount' => Money::of($finalAmount),
                'final_amount_overridden' => $overridden,
                'status' => $row['status'] ?? CollaborationStatus::NewLead->value,
                'negotiated_price' => $row['negotiated_price'] ?? null,
                'content_deadline' => $row['content_deadline'] ?? null,
                'posting_date' => $row['posting_date'] ?? null,
                'content_url' => $row['content_url'] ?? null,
                'content_approval_status' => $row['content_approval_status'] ?? 'not_submitted',
                'remarks' => $row['remarks'] ?? null,
                'updated_by' => Auth::id(),
            ];

            $existing = CampaignInfluencer::where('campaign_id', $campaign->id)
                ->where('influencer_id', $influencerId)
                ->first();

            if ($existing) {
                $existing->update($payload);
            } else {
                $payload['campaign_id'] = $campaign->id;
                $payload['influencer_id'] = $influencerId;
                $payload['created_by'] = Auth::id();
                CampaignInfluencer::create($payload);
            }
        }

        if (! empty($seen)) {
            $removable = CampaignInfluencer::query()
                ->where('campaign_id', $campaign->id)
                ->whereNotIn('influencer_id', array_keys($seen))
                ->withCount('payments')
                ->get();

            if ($removable->contains(fn (CampaignInfluencer $row) => $row->payments_count > 0)) {
                throw ValidationException::withMessages([
                    'influencers' => 'Cannot remove campaign influencers that have recorded payments.',
                ]);
            }

            CampaignInfluencer::where('campaign_id', $campaign->id)
                ->whereNotIn('influencer_id', array_keys($seen))
                ->delete();
        } elseif ($influencers === []) {
            // Explicit empty list clears influencers without payments.
            $removable = CampaignInfluencer::query()
                ->where('campaign_id', $campaign->id)
                ->withCount('payments')
                ->get();

            if ($removable->contains(fn (CampaignInfluencer $row) => $row->payments_count > 0)) {
                throw ValidationException::withMessages([
                    'influencers' => 'Cannot remove campaign influencers that have recorded payments.',
                ]);
            }

            CampaignInfluencer::where('campaign_id', $campaign->id)->delete();
        }
    }

    /**
     * Sync campaign-level social media deliverables (no campaign_influencer_id).
     *
     * @param  array<int, array<string, mixed>>  $deliverables
     */
    public function syncCampaignDeliverables(Campaign $campaign, array $deliverables): void
    {
        $keepIds = [];

        foreach ($deliverables as $row) {
            if (empty($row['type'])) {
                continue;
            }

            $payload = [
                'campaign_id' => $campaign->id,
                'campaign_influencer_id' => null,
                'type' => $row['type'],
                'platform' => $row['platform'] ?? null,
                'quantity' => (int) ($row['quantity'] ?? 1),
                'title' => $row['title'] ?? null,
                'description' => $row['description'] ?? null,
                'deadline' => $row['deadline'] ?? null,
                'status' => $row['status'] ?? DeliverableStatus::Pending->value,
                'remarks' => $row['remarks'] ?? null,
                'content_url' => $row['content_url'] ?? null,
            ];

            if (! empty($row['id'])) {
                $existing = Deliverable::query()
                    ->where('id', $row['id'])
                    ->where('campaign_id', $campaign->id)
                    ->whereNull('campaign_influencer_id')
                    ->first();

                if ($existing) {
                    $existing->update($payload);
                    $keepIds[] = $existing->id;

                    continue;
                }
            }

            $created = Deliverable::create($payload);
            $keepIds[] = $created->id;
        }

        Deliverable::query()
            ->where('campaign_id', $campaign->id)
            ->whereNull('campaign_influencer_id')
            ->when(! empty($keepIds), fn ($q) => $q->whereNotIn('id', $keepIds))
            ->when(empty($keepIds), fn ($q) => $q)
            ->delete();
    }

    public function totals(Campaign $campaign): array
    {
        $rows = $campaign->relationLoaded('campaignInfluencers')
            ? $campaign->campaignInfluencers
            : $campaign->campaignInfluencers()->get();

        $totalCost = Money::of($rows->sum('influencer_cost'));
        $totalAdditional = Money::of($rows->sum('additional_cost'));
        $totalFee = Money::of($rows->sum('grovera_fee'));
        $totalFinal = Money::of($rows->sum('final_amount'));
        $serviceFee = Money::of($campaign->service_fee ?? 0);
        $margin = Money::sub(Money::sub($totalFinal, $totalCost), $totalAdditional);

        return [
            'total_influencer_cost' => (float) $totalCost,
            'total_additional_cost' => (float) $totalAdditional,
            'total_grovera_fee' => (float) $totalFee,
            'total_service_fee' => (float) $serviceFee,
            'total_final_amount' => (float) $totalFinal,
            'total_campaign_value' => (float) Money::add($totalFinal, $serviceFee),
            'margin' => (float) $margin,
            'influencer_count' => $rows->count(),
        ];
    }

    public function delete(Campaign $campaign): void
    {
        if ($campaign->payments()->exists()) {
            throw ValidationException::withMessages([
                'campaign' => 'Cannot archive a campaign that has recorded payments.',
            ]);
        }

        $campaign->delete();
    }
}
