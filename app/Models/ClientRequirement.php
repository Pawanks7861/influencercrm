<?php

namespace App\Models;

use App\Enums\RequirementStatus;
use App\Enums\RequirementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientRequirement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'requirement_number',
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
        'internal_notes',
        'status',
        'submitted_at',
        'assigned_to',
        'converted_campaign_id',
    ];

    protected $casts = [
        'requirement_type' => RequirementType::class,
        'status' => RequirementStatus::class,
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'preferred_start_date' => 'date',
        'preferred_end_date' => 'date',
        'expected_posting_date' => 'date',
        'preferred_platforms' => 'array',
        'services_required' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function convertedCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'converted_campaign_id');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(RequirementDeliverable::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(RequirementMessage::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RequirementAttachment::class);
    }

    public function shortlists(): HasMany
    {
        return $this->hasMany(InfluencerShortlist::class);
    }
}
