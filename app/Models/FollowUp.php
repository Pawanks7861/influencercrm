<?php

namespace App\Models;

use App\Enums\FollowUpStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'influencer_id',
        'client_id',
        'campaign_id',
        'assigned_to',
        'follow_up_date',
        'follow_up_time',
        'note',
        'status',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'status' => FollowUpStatus::class,
        'follow_up_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        if ($this->status !== FollowUpStatus::Pending) {
            return false;
        }

        return $this->follow_up_date->lt(now()->startOfDay());
    }

    public function relatedLabel(): string
    {
        if ($this->influencer) {
            return 'Influencer: '.$this->influencer->name;
        }

        if ($this->client) {
            return 'Client: '.($this->client->company_name ?: $this->client->name);
        }

        if ($this->campaign) {
            return 'Campaign: '.$this->campaign->campaign_name;
        }

        return '—';
    }
}
