<?php

namespace App\Http\Requests;

use App\Enums\DeliverableStatus;
use App\Enums\DeliverableType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliverableStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('deliverables.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'campaign_influencer_id' => ['required', 'exists:campaign_influencers,id'],
            'type' => ['required', Rule::enum(DeliverableType::class)],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'content_url' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::enum(DeliverableStatus::class)],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
