<?php

namespace App\Enums;

enum ClientActivityType: string
{
    case CalledClient = 'called_client';
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case Meeting = 'meeting';
    case ProposalSent = 'proposal_sent';
    case FollowUp = 'follow_up';
    case CampaignDiscussion = 'campaign_discussion';
    case PaymentDiscussion = 'payment_discussion';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CalledClient => 'Called Client',
            self::Whatsapp => 'WhatsApp',
            self::Email => 'Email',
            self::Meeting => 'Meeting',
            self::ProposalSent => 'Proposal Sent',
            self::FollowUp => 'Follow-up',
            self::CampaignDiscussion => 'Campaign Discussion',
            self::PaymentDiscussion => 'Payment Discussion',
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
