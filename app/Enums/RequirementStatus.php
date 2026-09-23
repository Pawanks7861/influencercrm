<?php

namespace App\Enums;

enum RequirementStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case NeedMoreInformation = 'need_more_information';
    case Shortlisting = 'shortlisting';
    case ProposalShared = 'proposal_shared';
    case ClientReviewing = 'client_reviewing';
    case Approved = 'approved';
    case ConvertedToCampaign = 'converted_to_campaign';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::NeedMoreInformation => 'Need More Information',
            self::Shortlisting => 'Shortlisting',
            self::ProposalShared => 'Proposal Shared',
            self::ClientReviewing => 'Client Reviewing',
            self::Approved => 'Approved',
            self::ConvertedToCampaign => 'Converted to Campaign',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Closed => 'Closed',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }
}
