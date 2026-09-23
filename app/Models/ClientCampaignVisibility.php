<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCampaignVisibility extends Model
{
    use HasFactory;

    protected $table = 'client_campaign_visibility';

    protected $fillable = [
        'campaign_id',
        'visible_to_client',
        'show_budget',
        'show_deliverables',
        'show_influencers',
        'show_posting_dates',
        'show_content_links',
        'show_payment_summary',
        'show_notes',
        'shared_at',
        'shared_by',
    ];

    protected $casts = [
        'visible_to_client' => 'boolean',
        'show_budget' => 'boolean',
        'show_deliverables' => 'boolean',
        'show_influencers' => 'boolean',
        'show_posting_dates' => 'boolean',
        'show_content_links' => 'boolean',
        'show_payment_summary' => 'boolean',
        'show_notes' => 'boolean',
        'shared_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function sharedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }
}
