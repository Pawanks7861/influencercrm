<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequirementAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_requirement_id',
        'original_filename',
        'stored_path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ClientRequirement::class, 'client_requirement_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
