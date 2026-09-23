<?php

namespace App\Services\Client;

use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ClientService
{
    public function __construct(
        protected ClientDuplicateDetectionService $duplicateDetection
    ) {}

    public function create(array $data, bool $force = false): Client
    {
        $data = $this->normalizePayload($data);

        if (! $force) {
            $duplicates = $this->duplicateDetection->findDuplicates($data);
            if ($duplicates->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'duplicates' => 'This client may already exist.',
                    'duplicate_ids' => $duplicates->pluck('id')->all(),
                ]);
            }
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return Client::create($data);
    }

    public function update(Client $client, array $data, bool $force = false): Client
    {
        $data = $this->normalizePayload($data);

        if (! $force) {
            $duplicates = $this->duplicateDetection->findDuplicates($data, $client->id);
            if ($duplicates->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'duplicates' => 'This client may already exist.',
                    'duplicate_ids' => $duplicates->pluck('id')->all(),
                ]);
            }
        }

        $data['updated_by'] = Auth::id();
        $client->update($data);

        return $client->fresh();
    }

    public function delete(Client $client): void
    {
        $client->delete();
    }

    protected function normalizePayload(array $data): array
    {
        if (! empty($data['company_name'])) {
            $data['name'] = $data['company_name'];
        } elseif (! empty($data['name']) && empty($data['company_name'])) {
            $data['company_name'] = $data['name'];
        }

        if (array_key_exists('mobile', $data)) {
            $data['mobile'] = $this->duplicateDetection->normalizeMobile($data['mobile']);
            if (! empty($data['mobile']) && empty($data['phone'])) {
                $data['phone'] = $data['mobile'];
            }
        } elseif (array_key_exists('phone', $data) && empty($data['mobile'])) {
            $data['mobile'] = $this->duplicateDetection->normalizeMobile($data['phone']);
        }

        if (array_key_exists('email', $data)) {
            $data['email'] = $this->duplicateDetection->normalizeEmail($data['email']);
        }

        if (array_key_exists('alternate_mobile', $data)) {
            $data['alternate_mobile'] = $this->duplicateDetection->normalizeMobile($data['alternate_mobile']);
        }

        foreach ([
            'website', 'instagram_url', 'facebook_url', 'linkedin_url',
            'youtube_url', 'twitter_url', 'address', 'notes',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $value = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
                $data[$field] = $value === '' ? null : $value;
            }
        }

        $data['status'] = $data['status'] ?? ClientStatus::Active->value;

        return $data;
    }
}
