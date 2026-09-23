<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public const DEFAULTS = [
        'studio_name' => 'Grovera Studio',
        'currency' => 'INR',
        'currency_symbol' => '₹',
        'date_format' => 'd M Y',
        'timezone' => 'Asia/Kolkata',
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        $defaults = self::DEFAULTS;
        $fallback = array_key_exists($key, $defaults) ? $defaults[$key] : $default;

        return Cache::remember("settings.{$key}", 3600, function () use ($key, $fallback) {
            $setting = Setting::query()->where('key', $key)->first();

            return $setting?->value ?? $fallback;
        });
    }

    public function set(string $key, mixed $value, string $group = 'general'): Setting
    {
        $setting = Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("settings.{$key}");
        Cache::forget('settings.all');

        return $setting;
    }

    public function all(): array
    {
        return Cache::remember('settings.all', 3600, function () {
            $stored = Setting::query()->pluck('value', 'key')->all();

            return array_merge(self::DEFAULTS, $stored);
        });
    }

    public function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $group);
        }
    }

    public function seedDefaults(): void
    {
        foreach (self::DEFAULTS as $key => $value) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'general']
            );
        }

        Cache::forget('settings.all');
    }
}
