<?php

namespace App\Models;

use App\Enums\InfluencerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Influencer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'instagram_url',
        'instagram_username',
        'youtube_url',
        'facebook_url',
        'linkedin_url',
        'twitter_url',
        'other_social_url',
        'mobile',
        'email',
        'location',
        'influencer_type',
        'default_price',
        'notes_summary',
        'status',
        'login_enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'influencer_type' => InfluencerType::class,
        'default_price' => 'decimal:2',
        'login_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaignInfluencers(): HasMany
    {
        return $this->hasMany(CampaignInfluencer::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(InfluencerActivity::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function hasCampaignHistory(): bool
    {
        return $this->campaignInfluencers()->exists();
    }
}
