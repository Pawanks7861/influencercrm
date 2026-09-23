<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequirementMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_requirement_id',
        'user_id',
        'sender_type',
        'message',
    ];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ClientRequirement::class, 'client_requirement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
