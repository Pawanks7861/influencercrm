<?php

namespace App\Enums;

enum ContentApprovalStatus: string
{
    case NotSubmitted = 'not_submitted';
    case Submitted = 'submitted';
    case RevisionRequested = 'revision_requested';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::NotSubmitted => 'Not Submitted',
            self::Submitted => 'Submitted',
            self::RevisionRequested => 'Revision Requested',
            self::Approved => 'Approved',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }
}
