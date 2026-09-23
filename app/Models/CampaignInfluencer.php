<?php

namespace App\Models;

use App\Enums\CollaborationStatus;
use App\Enums\ContentApprovalStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CampaignInfluencer extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'influencer_id',
        'influencer_cost',
        'additional_cost',
        'grovera_fee',
        'final_amount',
        'final_amount_overridden',
        'status',
        'negotiated_price',
        'content_deadline',
        'posting_date',
        'content_url',
        'content_approval_status',
        'content_approval_notes',
        'content_approved_at',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'influencer_cost' => 'decimal:2',
        'additional_cost' => 'decimal:2',
        'grovera_fee' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'negotiated_price' => 'decimal:2',
        'final_amount_overridden' => 'boolean',
        'status' => CollaborationStatus::class,
        'content_approval_status' => ContentApprovalStatus::class,
        'content_deadline' => 'date',
        'posting_date' => 'date',
        'content_approved_at' => 'datetime',
    ];

    protected $appends = [
        'amount_paid',
        'amount_pending',
        'derived_payment_status',
        'margin',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class);
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function calculateFinalAmount(): string
    {
        return \App\Support\Money::add(
            \App\Support\Money::add($this->influencer_cost, $this->additional_cost ?? 0),
            $this->grovera_fee
        );
    }

    public function recalculateFinalAmountIfNotOverridden(): void
    {
        if (! $this->final_amount_overridden) {
            $this->final_amount = $this->calculateFinalAmount();
        }
    }

    public function getAmountPaidAttribute(): string
    {
        if (array_key_exists('payments_sum_amount', $this->attributes)) {
            return \App\Support\Money::of($this->attributes['payments_sum_amount'] ?? 0);
        }

        return \App\Support\Money::of($this->payments()->sum('amount'));
    }

    public function getAmountPendingAttribute(): string
    {
        return \App\Support\Money::max(
            \App\Support\Money::sub($this->final_amount, $this->amount_paid),
            '0'
        );
    }

    public function getDerivedPaymentStatusAttribute(): string
    {
        $paid = $this->amount_paid;
        $final = \App\Support\Money::of($this->final_amount);

        if (\App\Support\Money::compare($paid, '0') <= 0) {
            return PaymentStatus::NotPaid->value;
        }

        if (\App\Support\Money::compare($paid, $final) >= 0) {
            return PaymentStatus::Paid->value;
        }

        return PaymentStatus::PartiallyPaid->value;
    }

    public function getMarginAttribute(): string
    {
        return \App\Support\Money::sub(
            \App\Support\Money::sub($this->final_amount, $this->influencer_cost),
            $this->additional_cost ?? 0
        );
    }
}
