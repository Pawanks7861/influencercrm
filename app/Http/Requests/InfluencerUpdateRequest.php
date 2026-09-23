<?php

namespace App\Http\Requests;

use App\Enums\InfluencerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InfluencerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('influencers.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('instagram_username') && $this->filled('instagram_url')) {
            $this->merge([
                'instagram_username' => $this->input('instagram_url'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'instagram_url' => ['nullable', 'string', 'max:500'],
            'instagram_username' => ['nullable', 'string', 'max:255'],
            'youtube_url' => ['nullable', 'string', 'max:500'],
            'facebook_url' => ['nullable', 'string', 'max:500'],
            'linkedin_url' => ['nullable', 'string', 'max:500'],
            'twitter_url' => ['nullable', 'string', 'max:500'],
            'other_social_url' => ['nullable', 'string', 'max:500'],
            'mobile' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'influencer_type' => ['required', Rule::enum(InfluencerType::class)],
            'default_price' => ['required', 'numeric', 'min:0'],
            'notes_summary' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,inactive,archived'],
            'force' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('instagram_username') && ! $this->filled('instagram_url')) {
                $validator->errors()->add('instagram_username', 'Instagram profile is required.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Influencer name is required.',
            'mobile.required' => 'Mobile number is required.',
            'location.required' => 'Location is required.',
            'influencer_type.required' => 'Please select an influencer type.',
            'default_price.required' => 'Influencer price is required.',
            'default_price.numeric' => 'Influencer price must be a number.',
            'default_price.min' => 'Influencer price cannot be negative.',
        ];
    }
}
