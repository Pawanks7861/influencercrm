<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'campaign_name',
        'client_id',
        'brand_name',
        'campaign_type',
        'platforms',
        'campaign_budget',
        'service_fee',
        'start_date',
        'deadline',
        'posting_date',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => CampaignStatus::class,
        'campaign_type' => CampaignType::class,
        'platforms' => 'array',
        'campaign_budget' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'start_date' => 'date',
        'deadline' => 'date',
        'posting_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function clientVisibility(): HasOne
    {
        return $this->hasOne(ClientCampaignVisibility::class);
    }

    public function campaignInfluencers(): HasMany
    {
        return $this->hasMany(CampaignInfluencer::class);
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class);
    }

    public function influencers(): BelongsToMany
    {
        return $this->belongsToMany(Influencer::class, 'campaign_influencers')
            ->withPivot([
                'id',
                'influencer_cost',
                'additional_cost',
                'grovera_fee',
                'final_amount',
                'final_amount_overridden',
                'status',
                'negotiated_price',
                'content_deadline',
                'posting_date',
                'content_url',
                'content_approval_status',
                'remarks',
            ])
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(InfluencerActivity::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getTotalInfluencerCostAttribute(): string
    {
        return \App\Support\Money::of($this->campaignInfluencers()->sum('influencer_cost'));
    }

    public function getTotalAdditionalCostAttribute(): string
    {
        return \App\Support\Money::of($this->campaignInfluencers()->sum('additional_cost'));
    }

    public function getTotalGroveraFeeAttribute(): string
    {
        return \App\Support\Money::of($this->campaignInfluencers()->sum('grovera_fee'));
    }

    public function getTotalFinalAmountAttribute(): string
    {
        return \App\Support\Money::of($this->campaignInfluencers()->sum('final_amount'));
    }

    public function getMarginAttribute(): string
    {
        return \App\Support\Money::sub(
            \App\Support\Money::sub($this->total_final_amount, $this->total_influencer_cost),
            $this->total_additional_cost
        );
    }
}
