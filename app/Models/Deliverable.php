<?php

namespace App\Models;

use App\Enums\DeliverableStatus;
use App\Enums\DeliverableType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'campaign_influencer_id',
        'type',
        'platform',
        'quantity',
        'title',
        'description',
        'deadline',
        'submitted_at',
        'approved_at',
        'posted_at',
        'content_url',
        'status',
        'remarks',
    ];

    protected $casts = [
        'type' => DeliverableType::class,
        'status' => DeliverableStatus::class,
        'quantity' => 'integer',
        'deadline' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaignInfluencer(): BelongsTo
    {
        return $this->belongsTo(CampaignInfluencer::class);
    }
}
