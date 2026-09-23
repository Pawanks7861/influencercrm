<?php

namespace App\Services\Influencer;

use App\Models\Influencer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InfluencerService
{
    public function __construct(
        protected DuplicateDetectionService $duplicateDetection
    ) {}

    public function create(array $data, bool $force = false): Influencer
    {
        $data = $this->normalizePayload($data);

        if (! $force) {
            $duplicates = $this->duplicateDetection->findDuplicates($data);
            if ($duplicates->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'duplicates' => 'Potential duplicate influencers found.',
                    'duplicate_ids' => $duplicates->pluck('id')->all(),
                ]);
            }
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return Influencer::create($data);
    }

    public function update(Influencer $influencer, array $data, bool $force = false): Influencer
    {
        $data = $this->normalizePayload($data);

        if (! $force) {
            $duplicates = $this->duplicateDetection->findDuplicates($data, $influencer->id);
            if ($duplicates->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'duplicates' => 'Potential duplicate influencers found.',
                    'duplicate_ids' => $duplicates->pluck('id')->all(),
                ]);
            }
        }

        $data['updated_by'] = Auth::id();
        $influencer->update($data);

        return $influencer->fresh();
    }

    public function delete(Influencer $influencer): void
    {
        if ($influencer->hasCampaignHistory()) {
            throw ValidationException::withMessages([
                'influencer' => 'Cannot permanently delete an influencer with campaign history. Archive instead.',
            ]);
        }

        $influencer->delete();
    }

    public function archive(Influencer $influencer): Influencer
    {
        $influencer->update([
            'status' => 'archived',
            'updated_by' => Auth::id(),
        ]);

        return $influencer;
    }

    protected function normalizePayload(array $data): array
    {
        if (isset($data['instagram_username']) || isset($data['instagram_url'])) {
            $username = $this->duplicateDetection->normalizeInstagram(
                $data['instagram_username'] ?? $data['instagram_url'] ?? null
            );
            $data['instagram_username'] = $username;

            if (empty($data['instagram_url']) && $username) {
                $data['instagram_url'] = 'https://instagram.com/'.$username;
            }
        }

        foreach (['youtube_url', 'facebook_url', 'linkedin_url', 'twitter_url', 'other_social_url'] as $socialField) {
            if (array_key_exists($socialField, $data)) {
                $value = is_string($data[$socialField]) ? trim($data[$socialField]) : $data[$socialField];
                $data[$socialField] = $value === '' ? null : $value;
            }
        }

        if (array_key_exists('email', $data)) {
            $data['email'] = $this->duplicateDetection->normalizeEmail($data['email']);
        }

        if (array_key_exists('mobile', $data)) {
            $normalized = $this->duplicateDetection->normalizeMobile($data['mobile']);
            $data['mobile'] = $normalized;
        }

        return $data;
    }
}
