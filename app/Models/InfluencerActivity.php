<?php

namespace App\Models;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'influencer_id',
        'campaign_id',
        'activity_type',
        'activity_at',
        'note',
        'created_by',
    ];

    protected $casts = [
        'activity_type' => ActivityType::class,
        'activity_at' => 'datetime',
    ];

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
