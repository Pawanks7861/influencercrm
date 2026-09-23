<?php

namespace App\Enums;

enum ShortlistStatus: string
{
    case Draft = 'draft';
    case Shared = 'shared';
    case Viewed = 'viewed';
    case Responded = 'responded';
    case Withdrawn = 'withdrawn';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Shared => 'Shared',
            self::Viewed => 'Viewed',
            self::Responded => 'Responded',
            self::Withdrawn => 'Withdrawn',
            self::Expired => 'Expired',
        };
    }

    public static function clientVisibleValues(): array
    {
        return [
            self::Shared->value,
            self::Viewed->value,
            self::Responded->value,
        ];
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }
}
