<?php

namespace App\Enums;

enum ActivityType: string
{
    case Called = 'called';
    case WhatsappSent = 'whatsapp_sent';
    case EmailSent = 'email_sent';
    case InstagramDm = 'instagram_dm';
    case FollowUp = 'follow_up';
    case PriceNegotiation = 'price_negotiation';
    case ScriptShared = 'script_shared';
    case ProductDispatched = 'product_dispatched';
    case ProductReceived = 'product_received';
    case ContentReceived = 'content_received';
    case ApprovalRequested = 'approval_requested';
    case PaymentDone = 'payment_done';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Called => 'Called',
            self::WhatsappSent => 'WhatsApp Sent',
            self::EmailSent => 'Email Sent',
            self::InstagramDm => 'Instagram DM',
            self::FollowUp => 'Follow-up',
            self::PriceNegotiation => 'Price Negotiation',
            self::ScriptShared => 'Script Shared',
            self::ProductDispatched => 'Product Dispatched',
            self::ProductReceived => 'Product Received',
            self::ContentReceived => 'Content Received',
            self::ApprovalRequested => 'Approval Requested',
            self::PaymentDone => 'Payment Done',
            self::Other => 'Other',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }
}
