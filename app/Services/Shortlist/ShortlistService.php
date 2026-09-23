<?php

namespace App\Services\Shortlist;

use App\Enums\ShortlistItemStatus;
use App\Enums\ShortlistStatus;
use App\Models\ClientRequirement;
use App\Models\Influencer;
use App\Models\InfluencerShortlist;
use App\Models\InfluencerShortlistItem;
use App\Models\PortalNotification;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShortlistService
{
    public function create(ClientRequirement $requirement, string $title, array $items, ?User $creator = null, ?string $message = null): InfluencerShortlist
    {
        return DB::transaction(function () use ($requirement, $title, $items, $creator, $message) {
            $shortlist = InfluencerShortlist::query()->create([
                'client_requirement_id' => $requirement->id,
                'title' => $title,
                'status' => ShortlistStatus::Draft,
                'message' => $message,
                'created_by' => $creator?->id,
            ]);

            $this->addItems($shortlist, $items);

            return $shortlist->fresh(['items.influencer', 'requirement']);
        });
    }

    public function updateItems(InfluencerShortlist $shortlist, array $items): InfluencerShortlist
    {
        return DB::transaction(function () use ($shortlist, $items) {
            foreach ($items as $itemData) {
                if (empty($itemData['id'])) {
                    continue;
                }

                $item = $shortlist->items()->whereKey($itemData['id'])->first();
                if (! $item) {
                    continue;
                }

                $updates = Arr::only($itemData, [
                    'client_price',
                    'description',
                    'display_order',
                    'show_name',
                    'show_instagram',
                    'show_price',
                    'show_location',
                    'show_type',
                    'show_note',
                ]);

                if (array_key_exists('client_price', $updates) && (float) $updates['client_price'] !== (float) $item->client_price) {
                    $updates['previous_client_price'] = $item->client_price;
                    $updates['price_updated_at'] = now();
                    $updates['price_updated_by'] = auth()->id();
                }

                $item->fill($updates);
                $item->save();
            }

            return $shortlist->fresh(['items.influencer']);
        });
    }

    public function addItems(InfluencerShortlist $shortlist, array $items): InfluencerShortlist
    {
        $order = (int) $shortlist->items()->max('display_order');

        foreach ($items as $itemData) {
            $influencerId = (int) ($itemData['influencer_id'] ?? 0);
            $influencer = Influencer::query()->find($influencerId);

            if (! $influencer) {
                throw ValidationException::withMessages([
                    'items' => "Influencer #{$influencerId} was not found.",
                ]);
            }

            if ($shortlist->items()->where('influencer_id', $influencer->id)->exists()) {
                continue;
            }

            $order++;

            $shortlist->items()->create([
                'influencer_id' => $influencer->id,
                'display_name' => $influencer->name,
                'instagram_url' => $influencer->instagram_url,
                'instagram_username' => $influencer->instagram_username,
                'location' => $influencer->location,
                'influencer_type' => $influencer->influencer_type?->value ?? $influencer->influencer_type,
                'client_price' => $itemData['client_price'],
                'description' => $itemData['description'] ?? null,
                'display_order' => $itemData['display_order'] ?? $order,
                'status' => ShortlistItemStatus::Pending,
                'show_name' => $itemData['show_name'] ?? true,
                'show_instagram' => $itemData['show_instagram'] ?? true,
                'show_price' => $itemData['show_price'] ?? true,
                'show_location' => $itemData['show_location'] ?? false,
                'show_type' => $itemData['show_type'] ?? false,
                'show_note' => $itemData['show_note'] ?? true,
            ]);
        }

        return $shortlist->fresh(['items.influencer']);
    }

    public function removeItem(InfluencerShortlist $shortlist, InfluencerShortlistItem $item): void
    {
        if ($item->influencer_shortlist_id !== $shortlist->id) {
            abort(404);
        }

        $item->delete();
    }

    public function reorder(InfluencerShortlist $shortlist, array $orderedIds): InfluencerShortlist
    {
        foreach ($orderedIds as $index => $id) {
            $shortlist->items()->whereKey($id)->update(['display_order' => $index + 1]);
        }

        return $shortlist->fresh(['items']);
    }

    public function share(InfluencerShortlist $shortlist, User $sharedBy, ?string $expiresAt = null): InfluencerShortlist
    {
        if ($shortlist->items()->count() === 0) {
            throw ValidationException::withMessages([
                'shortlist' => 'Add at least one influencer before sharing.',
            ]);
        }

        $shortlist->status = ShortlistStatus::Shared;
        $shortlist->shared_at = now();
        $shortlist->shared_by = $sharedBy->id;
        $shortlist->withdrawn_at = null;
        if ($expiresAt) {
            $shortlist->expires_at = $expiresAt;
        }
        $shortlist->save();

        $client = $shortlist->requirement?->client;
        if ($client?->user_id) {
            PortalNotification::query()->create([
                'user_id' => $client->user_id,
                'type' => 'shortlist_shared',
                'title' => 'New influencer shortlist shared',
                'body' => $shortlist->title,
                'link' => route('client.shortlists.show', $shortlist),
                'data' => [
                    'shortlist_id' => $shortlist->id,
                    'requirement_id' => $shortlist->client_requirement_id,
                ],
            ]);
        }

        return $shortlist->fresh(['items', 'requirement.client']);
    }

    public function withdraw(InfluencerShortlist $shortlist): InfluencerShortlist
    {
        $shortlist->status = ShortlistStatus::Withdrawn;
        $shortlist->withdrawn_at = now();
        $shortlist->save();

        return $shortlist->fresh();
    }

    public function markViewed(InfluencerShortlist $shortlist): InfluencerShortlist
    {
        $status = $shortlist->status?->value ?? $shortlist->status;

        if ($status === ShortlistStatus::Shared->value) {
            $shortlist->status = ShortlistStatus::Viewed;
            $shortlist->save();
        }

        return $shortlist;
    }

    public function respond(InfluencerShortlistItem $item, string $status, ?string $remark = null): InfluencerShortlistItem
    {
        if (! in_array($status, ShortlistItemStatus::clientResponseValues(), true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid response status.',
            ]);
        }

        $item->status = ShortlistItemStatus::from($status);
        $item->client_remark = $remark;
        $item->responded_at = now();
        $item->save();

        $shortlist = $item->shortlist;
        $shortlistStatus = $shortlist->status?->value ?? $shortlist->status;
        if (in_array($shortlistStatus, [ShortlistStatus::Shared->value, ShortlistStatus::Viewed->value], true)) {
            $shortlist->status = ShortlistStatus::Responded;
            $shortlist->save();
        }

        return $item->fresh(['shortlist']);
    }
}
