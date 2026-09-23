<?php

namespace App\Models;

use App\Enums\ShortlistStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InfluencerShortlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_requirement_id',
        'title',
        'status',
        'shared_at',
        'shared_by',
        'withdrawn_at',
        'expires_at',
        'message',
        'created_by',
    ];

    protected $casts = [
        'status' => ShortlistStatus::class,
        'shared_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'expires_at' => 'date',
    ];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ClientRequirement::class, 'client_requirement_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InfluencerShortlistItem::class)->orderBy('display_order');
    }

    public function sharedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->toDateString() < now()->toDateString();
    }

    public function hasClientVisibleStatus(): bool
    {
        $status = $this->status?->value ?? $this->status;

        return in_array($status, ShortlistStatus::clientVisibleValues(), true);
    }

    public function isClientAccessible(): bool
    {
        return $this->hasClientVisibleStatus() && ! $this->isExpired();
    }
}
