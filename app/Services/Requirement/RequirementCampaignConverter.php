<?php

namespace App\Services\Requirement;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\DeliverableStatus;
use App\Enums\RequirementStatus;
use App\Enums\RequirementType;
use App\Models\ClientCampaignVisibility;
use App\Models\ClientRequirement;
use App\Models\Deliverable;
use App\Models\Influencer;
use App\Models\InfluencerShortlist;
use App\Models\InfluencerShortlistItem;
use App\Services\Campaign\CampaignService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequirementCampaignConverter
{
    public function __construct(protected CampaignService $campaignService)
    {
    }

    /**
     * Convert selected shortlist influencers into a campaign for the requirement's client.
     *
     * @param  array<int>  $itemIds
     * @param  array<string>  $allowedStatuses
     */
    public function convert(
        ClientRequirement $requirement,
        int $shortlistId,
        array $itemIds,
        array $allowedStatuses = ['selected', 'interested']
    ): \App\Models\Campaign {
        if ($requirement->converted_campaign_id) {
            throw ValidationException::withMessages([
                'requirement' => 'This requirement has already been converted to a campaign.',
            ]);
        }

        $shortlist = InfluencerShortlist::query()
            ->whereKey($shortlistId)
            ->where('client_requirement_id', $requirement->id)
            ->first();

        if (! $shortlist) {
            throw ValidationException::withMessages([
                'shortlist_id' => 'Shortlist does not belong to this requirement.',
            ]);
        }

        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));

        if ($itemIds === []) {
            throw ValidationException::withMessages([
                'item_ids' => 'Select at least one shortlist item to convert.',
            ]);
        }

        $items = InfluencerShortlistItem::query()
            ->where('influencer_shortlist_id', $shortlist->id)
            ->whereIn('id', $itemIds)
            ->with('influencer')
            ->get();

        if ($items->count() !== count($itemIds)) {
            throw ValidationException::withMessages([
                'item_ids' => 'One or more selected items are invalid for this shortlist.',
            ]);
        }

        $allowed = collect($allowedStatuses)->map(fn ($s) => (string) $s)->all();

        foreach ($items as $item) {
            $status = $item->status?->value ?? $item->status;
            if (! in_array($status, $allowed, true)) {
                throw ValidationException::withMessages([
                    'item_ids' => "Item \"{$item->display_name}\" must be selected or interested before converting.",
                ]);
            }
        }

        $requirementType = $requirement->requirement_type instanceof RequirementType
            ? $requirement->requirement_type
            : RequirementType::from($requirement->requirement_type);

        $campaignType = CampaignType::from($requirementType->value);

        return DB::transaction(function () use ($requirement, $items, $campaignType, $requirementType) {
            $influencersPayload = [];

            if ($campaignType->includesInfluencers()) {
                foreach ($items as $item) {
                    /** @var Influencer $influencer */
                    $influencer = $item->influencer;
                    $influencersPayload[] = [
                        'influencer_id' => $influencer->id,
                        'influencer_cost' => (float) $influencer->default_price,
                        'grovera_fee' => 0,
                        'additional_cost' => 0,
                    ];
                }
            }

            $campaignDeliverables = [];
            if ($campaignType->includesSocialMedia()) {
                $requirement->loadMissing('deliverables');
                foreach ($requirement->deliverables as $deliverable) {
                    $campaignDeliverables[] = [
                        'type' => $deliverable->deliverable_type,
                        'platform' => $deliverable->platform,
                        'quantity' => $deliverable->quantity ?? 1,
                        'description' => $deliverable->notes,
                        'status' => DeliverableStatus::Pending->value,
                    ];
                }
            }

            $campaign = $this->campaignService->create(
                [
                    'client_id' => $requirement->client_id,
                    'brand_name' => $requirement->brand_name,
                    'campaign_type' => $campaignType->value,
                    'campaign_name' => $requirement->title,
                    'platforms' => $requirement->preferred_platforms,
                    'campaign_budget' => $requirement->budget_max ?? $requirement->budget_min,
                    'start_date' => optional($requirement->preferred_start_date)?->toDateString(),
                    'deadline' => optional($requirement->preferred_end_date)?->toDateString(),
                    'posting_date' => optional($requirement->expected_posting_date)?->toDateString(),
                    'status' => CampaignStatus::Draft->value,
                    'remarks' => 'Converted from requirement '.$requirement->requirement_number,
                ],
                $influencersPayload,
                $campaignDeliverables
            );

            if ($campaignType->includesInfluencers() && $requirementType->includesInfluencers()) {
                $this->attachInfluencerDeliverables($campaign, $requirement);
            }

            ClientCampaignVisibility::query()->create([
                'campaign_id' => $campaign->id,
                'visible_to_client' => false,
            ]);

            $requirement->status = RequirementStatus::ConvertedToCampaign;
            $requirement->converted_campaign_id = $campaign->id;
            $requirement->save();

            return $campaign->fresh(['client', 'campaignInfluencers.influencer', 'deliverables', 'clientVisibility']);
        });
    }

    protected function attachInfluencerDeliverables(\App\Models\Campaign $campaign, ClientRequirement $requirement): void
    {
        $requirement->loadMissing('deliverables');
        $deliverables = $requirement->deliverables;

        if ($deliverables->isEmpty()) {
            return;
        }

        $campaign->loadMissing('campaignInfluencers');

        foreach ($campaign->campaignInfluencers as $ci) {
            foreach ($deliverables as $deliverable) {
                Deliverable::query()->create([
                    'campaign_id' => $campaign->id,
                    'campaign_influencer_id' => $ci->id,
                    'type' => $deliverable->deliverable_type,
                    'platform' => $deliverable->platform,
                    'quantity' => $deliverable->quantity ?? 1,
                    'description' => $deliverable->notes,
                    'status' => DeliverableStatus::Pending->value,
                ]);
            }
        }
    }
}
