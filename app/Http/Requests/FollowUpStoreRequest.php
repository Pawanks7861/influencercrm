<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FollowUpStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('followups.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'influencer_id' => ['nullable', 'exists:influencers,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'follow_up_date' => ['required', 'date'],
            'follow_up_time' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('influencer_id') && ! $this->filled('client_id') && ! $this->filled('campaign_id')) {
                $validator->errors()->add('influencer_id', 'A follow-up must belong to an influencer, client, or campaign.');
            }
        });
    }
}
