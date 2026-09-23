<?php

namespace App\Enums;

enum DeliverableType: string
{
    case InstagramReel = 'instagram_reel';
    case InstagramStory = 'instagram_story';
    case InstagramPost = 'instagram_post';
    case StaticPost = 'static_post';
    case CollaborationPost = 'collaboration_post';
    case FacebookPost = 'facebook_post';
    case LinkedinPost = 'linkedin_post';
    case YoutubeShort = 'youtube_short';
    case YoutubeVideo = 'youtube_video';
    case ProductReview = 'product_review';
    case SocialMediaManagement = 'social_media_management';
    case ContentCalendar = 'content_calendar';
    case GraphicDesign = 'graphic_design';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::InstagramReel => 'Instagram Reel',
            self::InstagramStory => 'Instagram Story',
            self::InstagramPost => 'Instagram Post',
            self::StaticPost => 'Static Post',
            self::CollaborationPost => 'Collaboration Post',
            self::FacebookPost => 'Facebook Post',
            self::LinkedinPost => 'LinkedIn Post',
            self::YoutubeShort => 'YouTube Short',
            self::YoutubeVideo => 'YouTube Video',
            self::ProductReview => 'Product Review',
            self::SocialMediaManagement => 'Social Media Management',
            self::ContentCalendar => 'Content Calendar',
            self::GraphicDesign => 'Graphic Design',
            self::Custom => 'Custom',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }
}
