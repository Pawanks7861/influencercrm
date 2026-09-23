<?php

namespace App\Models;

use App\Enums\ClientStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'company_name',
        'contact_person',
        'mobile',
        'alternate_mobile',
        'email',
        'phone',
        'website',
        'instagram_url',
        'facebook_url',
        'linkedin_url',
        'youtube_url',
        'twitter_url',
        'address',
        'notes',
        'status',
        'login_enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => ClientStatus::class,
        'login_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ClientRequirement::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function noteEntries(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClientActivity::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function activeCampaignsCount(): int
    {
        return $this->campaigns()->where('status', 'active')->count();
    }

    public function displayName(): string
    {
        return $this->company_name ?: $this->name;
    }
}
