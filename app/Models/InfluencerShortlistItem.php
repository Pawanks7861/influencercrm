<?php

namespace App\Models;

use App\Enums\ShortlistItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerShortlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'influencer_shortlist_id',
        'influencer_id',
        'display_name',
        'instagram_url',
        'instagram_username',
        'location',
        'influencer_type',
        'client_price',
        'previous_client_price',
        'description',
        'display_order',
        'status',
        'client_remark',
        'show_name',
        'show_instagram',
        'show_price',
        'show_location',
        'show_type',
        'show_note',
        'price_updated_by',
        'price_updated_at',
        'responded_at',
    ];

    protected $casts = [
        'status' => ShortlistItemStatus::class,
        'client_price' => 'decimal:2',
        'previous_client_price' => 'decimal:2',
        'show_name' => 'boolean',
        'show_instagram' => 'boolean',
        'show_price' => 'boolean',
        'show_location' => 'boolean',
        'show_type' => 'boolean',
        'show_note' => 'boolean',
        'price_updated_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function shortlist(): BelongsTo
    {
        return $this->belongsTo(InfluencerShortlist::class, 'influencer_shortlist_id');
    }

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class);
    }
}
