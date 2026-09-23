<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case NotPaid = 'not_paid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case PaymentPending = 'payment_pending';

    public function label(): string
    {
        return match ($this) {
            self::NotPaid => 'Not Paid',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid => 'Paid',
            self::PaymentPending => 'Payment Pending',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }
}
