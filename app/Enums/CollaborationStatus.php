<?php

namespace App\Enums;

enum CollaborationStatus: string
{
    case NewLead = 'new_lead';
    case Contacted = 'contacted';
    case Negotiating = 'negotiating';
    case PriceConfirmed = 'price_confirmed';
    case WaitingForProduct = 'waiting_for_product';
    case ProductReceived = 'product_received';
    case ScriptShared = 'script_shared';
    case ContentInProgress = 'content_in_progress';
    case ContentReceived = 'content_received';
    case SentForApproval = 'sent_for_approval';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Posted = 'posted';
    case PaymentPending = 'payment_pending';
    case PaymentCompleted = 'payment_completed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NewLead => 'New Lead',
            self::Contacted => 'Contacted',
            self::Negotiating => 'Negotiating',
            self::PriceConfirmed => 'Price Confirmed',
            self::WaitingForProduct => 'Waiting for Product',
            self::ProductReceived => 'Product Received',
            self::ScriptShared => 'Script Shared',
            self::ContentInProgress => 'Content in Progress',
            self::ContentReceived => 'Content Received',
            self::SentForApproval => 'Sent for Approval',
            self::Approved => 'Approved',
            self::Scheduled => 'Scheduled',
            self::Posted => 'Posted',
            self::PaymentPending => 'Payment Pending',
            self::PaymentCompleted => 'Payment Completed',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Active = from price_confirmed through payment_completed (not completed/cancelled).
     */
    public function isActive(): bool
    {
        return in_array($this, self::activeCases(), true);
    }

    public function isPending(): bool
    {
        return in_array($this, [
            self::NewLead,
            self::Contacted,
            self::Negotiating,
        ], true);
    }

    /**
     * @return list<self>
     */
    public static function activeCases(): array
    {
        return [
            self::PriceConfirmed,
            self::WaitingForProduct,
            self::ProductReceived,
            self::ScriptShared,
            self::ContentInProgress,
            self::ContentReceived,
            self::SentForApproval,
            self::Approved,
            self::Scheduled,
            self::Posted,
            self::PaymentPending,
            self::PaymentCompleted,
        ];
    }

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return array_map(fn (self $case) => $case->value, self::activeCases());
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }
}
