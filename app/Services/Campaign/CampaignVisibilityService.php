<?php

namespace App\Services\Campaign;

use App\Models\Campaign;
use App\Models\ClientCampaignVisibility;
use App\Models\PortalNotification;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CampaignVisibilityService
{
    public function ensure(Campaign $campaign): ClientCampaignVisibility
    {
        return ClientCampaignVisibility::query()->firstOrCreate(
            ['campaign_id' => $campaign->id],
            [
                'visible_to_client' => false,
                'show_budget' => false,
                'show_deliverables' => true,
                'show_influencers' => true,
                'show_posting_dates' => true,
                'show_content_links' => false,
                'show_payment_summary' => false,
                'show_notes' => false,
            ]
        );
    }

    public function share(Campaign $campaign, User $sharedBy, array $flags = []): ClientCampaignVisibility
    {
        return DB::transaction(function () use ($campaign, $sharedBy, $flags) {
            $visibility = $this->ensure($campaign);

            $visibility->fill(Arr::only($flags, [
                'show_budget',
                'show_deliverables',
                'show_influencers',
                'show_posting_dates',
                'show_content_links',
                'show_payment_summary',
                'show_notes',
            ]));

            $visibility->visible_to_client = true;
            $visibility->shared_at = now();
            $visibility->shared_by = $sharedBy->id;
            $visibility->save();

            $client = $campaign->client;
            if ($client?->user_id) {
                PortalNotification::query()->create([
                    'user_id' => $client->user_id,
                    'type' => 'campaign_shared',
                    'title' => 'Campaign shared with you',
                    'body' => $campaign->campaign_name,
                    'link' => route('client.campaigns.show', $campaign),
                    'data' => [
                        'campaign_id' => $campaign->id,
                    ],
                ]);
            }

            return $visibility->fresh();
        });
    }

    public function unshare(Campaign $campaign): ClientCampaignVisibility
    {
        $visibility = $this->ensure($campaign);
        $visibility->visible_to_client = false;
        $visibility->save();

        return $visibility->fresh();
    }

    public function updateFlags(Campaign $campaign, array $flags): ClientCampaignVisibility
    {
        $visibility = $this->ensure($campaign);

        $visibility->fill(Arr::only($flags, [
            'visible_to_client',
            'show_budget',
            'show_deliverables',
            'show_influencers',
            'show_posting_dates',
            'show_content_links',
            'show_payment_summary',
            'show_notes',
        ]));

        if (! empty($flags['visible_to_client']) && ! $visibility->shared_at) {
            $visibility->shared_at = now();
            $visibility->shared_by = auth()->id();
        }

        $visibility->save();

        return $visibility->fresh();
    }
}
