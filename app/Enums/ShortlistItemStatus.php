<?php

namespace App\Enums;

enum ShortlistItemStatus: string
{
    case Pending = 'pending';
    case Interested = 'interested';
    case Rejected = 'rejected';
    case NeedMoreDetails = 'need_more_details';
    case Selected = 'selected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Interested => 'Interested',
            self::Rejected => 'Rejected',
            self::NeedMoreDetails => 'Need More Details',
            self::Selected => 'Selected',
        };
    }

    public static function clientResponseValues(): array
    {
        return [
            self::Interested->value,
            self::Rejected->value,
            self::NeedMoreDetails->value,
            self::Selected->value,
        ];
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }
}
