<?php

namespace App\Services\Requirement;

use App\Enums\RequirementStatus;
use App\Enums\RequirementType;
use App\Models\Client;
use App\Models\ClientRequirement;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ClientRequirementService
{
    public function __construct(protected RequirementNumberGenerator $numberGenerator)
    {
    }

    public function createForClient(Client $client, array $data): ClientRequirement
    {
        $data['client_id'] = $client->id;

        return $this->create($data);
    }

    public function createForStaff(array $data): ClientRequirement
    {
        return $this->create($data);
    }

    public function create(array $data): ClientRequirement
    {
        return DB::transaction(function () use ($data) {
            $deliverables = Arr::pull($data, 'deliverables', []);

            $requirement = ClientRequirement::query()->create([
                ...$this->mapAttributes($data),
                'requirement_number' => $this->numberGenerator->next(),
                'status' => $data['status'] ?? RequirementStatus::Submitted,
                'submitted_at' => now(),
            ]);

            $this->syncDeliverables($requirement, $deliverables);

            return $requirement->fresh(['deliverables', 'client']);
        });
    }

    public function update(ClientRequirement $requirement, array $data, bool $isClient = false): ClientRequirement
    {
        return DB::transaction(function () use ($requirement, $data, $isClient) {
            $deliverables = Arr::pull($data, 'deliverables', null);

            if ($isClient) {
                unset($data['client_id'], $data['internal_notes'], $data['assigned_to'], $data['status'], $data['converted_campaign_id']);
            }

            $requirement->fill($this->mapAttributes($data, $isClient));
            $requirement->save();

            if (is_array($deliverables)) {
                $requirement->deliverables()->delete();
                $this->syncDeliverables($requirement, $deliverables);
            }

            return $requirement->fresh(['deliverables', 'client', 'assignee']);
        });
    }

    public function updateStatus(ClientRequirement $requirement, string $status, ?string $internalNotes = null): ClientRequirement
    {
        $requirement->status = RequirementStatus::from($status);

        if ($internalNotes !== null) {
            $requirement->internal_notes = $internalNotes;
        }

        $requirement->save();

        return $requirement->fresh();
    }

    public function assign(ClientRequirement $requirement, ?int $userId): ClientRequirement
    {
        $requirement->assigned_to = $userId;
        $requirement->save();

        return $requirement->fresh(['assignee']);
    }

    protected function mapAttributes(array $data, bool $isClient = false): array
    {
        $keys = [
            'client_id',
            'requirement_type',
            'title',
            'brand_name',
            'description',
            'budget_min',
            'budget_max',
            'preferred_start_date',
            'preferred_end_date',
            'expected_posting_date',
            'preferred_location',
            'preferred_category',
            'preferred_platforms',
            'influencers_required',
            'target_audience',
            'services_required',
            'duration',
            'posting_frequency',
            'goals',
            'additional_instructions',
            'client_notes',
        ];

        if (! $isClient) {
            $keys = array_merge($keys, ['internal_notes', 'assigned_to', 'status']);
        }

        $mapped = Arr::only($data, $keys);

        if (isset($mapped['requirement_type']) && ! $mapped['requirement_type'] instanceof RequirementType) {
            $mapped['requirement_type'] = RequirementType::from($mapped['requirement_type']);
        }

        if (isset($mapped['status']) && ! $mapped['status'] instanceof RequirementStatus) {
            $mapped['status'] = RequirementStatus::from($mapped['status']);
        }

        return $mapped;
    }

    protected function syncDeliverables(ClientRequirement $requirement, array $deliverables): void
    {
        foreach ($deliverables as $item) {
            if (empty($item['deliverable_type'])) {
                continue;
            }

            $requirement->deliverables()->create([
                'platform' => $item['platform'] ?? null,
                'deliverable_type' => $item['deliverable_type'],
                'quantity' => (int) ($item['quantity'] ?? 1),
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }
}
