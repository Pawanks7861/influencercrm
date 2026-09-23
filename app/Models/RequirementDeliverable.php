<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequirementDeliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_requirement_id',
        'platform',
        'deliverable_type',
        'quantity',
        'notes',
    ];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ClientRequirement::class, 'client_requirement_id');
    }
}
