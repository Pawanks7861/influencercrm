<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientRequirementResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requirement_number' => $this->requirement_number,
            'requirement_type' => $this->requirement_type?->value ?? $this->requirement_type,
            'title' => $this->title,
            'brand_name' => $this->brand_name,
            'description' => $this->description,
            'budget_min' => $this->budget_min,
            'budget_max' => $this->budget_max,
            'preferred_start_date' => optional($this->preferred_start_date)->toDateString(),
            'preferred_end_date' => optional($this->preferred_end_date)->toDateString(),
            'expected_posting_date' => optional($this->expected_posting_date)->toDateString(),
            'preferred_location' => $this->preferred_location,
            'preferred_category' => $this->preferred_category,
            'preferred_platforms' => $this->preferred_platforms,
            'influencers_required' => $this->influencers_required,
            'target_audience' => $this->target_audience,
            'services_required' => $this->services_required,
            'duration' => $this->duration,
            'posting_frequency' => $this->posting_frequency,
            'goals' => $this->goals,
            'additional_instructions' => $this->additional_instructions,
            'client_notes' => $this->client_notes,
            'status' => $this->status?->value ?? $this->status,
            'submitted_at' => optional($this->submitted_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'deliverables' => $this->whenLoaded('deliverables', fn () => $this->deliverables->map(fn ($d) => [
                'id' => $d->id,
                'platform' => $d->platform,
                'deliverable_type' => $d->deliverable_type,
                'quantity' => $d->quantity,
                'notes' => $d->notes,
            ])),
            'messages' => $this->whenLoaded('messages', fn () => $this->messages->map(fn ($m) => [
                'id' => $m->id,
                'sender_type' => $m->sender_type,
                'message' => $m->message,
                'user_name' => $m->user?->name,
                'created_at' => optional($m->created_at)->toIso8601String(),
            ])),
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($a) => [
                'id' => $a->id,
                'original_filename' => $a->original_filename,
                'mime_type' => $a->mime_type,
                'size' => $a->size,
                'created_at' => optional($a->created_at)->toIso8601String(),
            ])),
            'shortlists_count' => $this->whenCounted('shortlists'),
        ];
    }
}
