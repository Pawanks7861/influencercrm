<?php

namespace App\Http\Requests;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\CollaborationStatus;
use App\Enums\DeliverableStatus;
use App\Enums\DeliverableType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CampaignUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('campaigns.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'campaign_name' => ['sometimes', 'required', 'string', 'max:255'],
            'client_id' => ['sometimes', 'required', 'exists:clients,id'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'campaign_type' => ['sometimes', 'required', Rule::enum(CampaignType::class)],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['string', Rule::in(array_keys(config('crm.platforms', [])))],
            'campaign_budget' => ['nullable', 'numeric', 'min:0'],
            'service_fee' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:start_date'],
            'posting_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(CampaignStatus::class)],
            'remarks' => ['nullable', 'string'],
            'influencers' => ['nullable', 'array'],
            'influencers.*.influencer_id' => ['required_with:influencers', 'exists:influencers,id'],
            'influencers.*.influencer_cost' => ['nullable', 'numeric', 'min:0'],
            'influencers.*.additional_cost' => ['nullable', 'numeric', 'min:0'],
            'influencers.*.grovera_fee' => ['nullable', 'numeric', 'min:0'],
            'influencers.*.final_amount' => ['nullable', 'numeric', 'min:0'],
            'influencers.*.final_amount_overridden' => ['sometimes', 'boolean'],
            'influencers.*.status' => ['nullable', Rule::enum(CollaborationStatus::class)],
            'influencers.*.negotiated_price' => ['nullable', 'numeric', 'min:0'],
            'influencers.*.content_deadline' => ['nullable', 'date'],
            'influencers.*.posting_date' => ['nullable', 'date'],
            'influencers.*.remarks' => ['nullable', 'string'],
            'deliverables' => ['nullable', 'array'],
            'deliverables.*.id' => ['nullable', 'integer'],
            'deliverables.*.type' => ['required_with:deliverables', Rule::enum(DeliverableType::class)],
            'deliverables.*.platform' => ['nullable', 'string', 'max:50'],
            'deliverables.*.quantity' => ['nullable', 'integer', 'min:1'],
            'deliverables.*.deadline' => ['nullable', 'date'],
            'deliverables.*.status' => ['nullable', Rule::enum(DeliverableStatus::class)],
            'deliverables.*.remarks' => ['nullable', 'string'],
            'deliverables.*.title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->has('campaign_type') && ! $this->has('influencers') && ! $this->has('deliverables')) {
                return;
            }

            $typeValue = $this->input('campaign_type');
            if (! $typeValue && $this->route('campaign')) {
                $typeValue = $this->route('campaign')->campaign_type?->value
                    ?? $this->route('campaign')->campaign_type;
            }

            $type = CampaignType::tryFrom((string) $typeValue);

            if ($this->has('influencers') && $type?->includesInfluencers() && empty($this->input('influencers'))) {
                $validator->errors()->add('influencers', 'Add at least one influencer for this campaign type.');
            }

            if ($this->has('deliverables') && $type?->includesSocialMedia() && empty($this->input('deliverables'))) {
                $validator->errors()->add('deliverables', 'Add at least one social media deliverable for this campaign type.');
            }
        });
    }
}
