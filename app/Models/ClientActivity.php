<?php

namespace App\Models;

use App\Enums\ClientActivityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'campaign_id',
        'activity_type',
        'activity_at',
        'note',
        'created_by',
    ];

    protected $casts = [
        'activity_type' => ClientActivityType::class,
        'activity_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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
