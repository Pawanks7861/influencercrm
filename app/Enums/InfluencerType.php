<?php

namespace App\Enums;

enum InfluencerType: string
{
    case Premium = 'premium';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::Premium => 'Premium',
            self::Medium => 'Medium',
            self::Low => 'Low',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }
}
