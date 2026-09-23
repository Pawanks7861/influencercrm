<?php

namespace App\Enums;

enum RequirementType: string
{
    case InfluencerMarketing = 'influencer_marketing';
    case SocialMediaMarketing = 'social_media_marketing';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::InfluencerMarketing => 'Influencer Marketing',
            self::SocialMediaMarketing => 'Social Media Marketing',
            self::Both => 'Both',
        };
    }

    public function includesInfluencers(): bool
    {
        return in_array($this, [self::InfluencerMarketing, self::Both], true);
    }

    public function includesSocialMedia(): bool
    {
        return in_array($this, [self::SocialMediaMarketing, self::Both], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }
}
