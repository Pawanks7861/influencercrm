<?php

namespace App\Services\Influencer;

use App\Models\Influencer;
use Illuminate\Support\Collection;

class DuplicateDetectionService
{
    public function normalizeInstagram(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        $value = preg_replace('#^https?://(www\.)?instagram\.com/#i', '', $value);
        $value = preg_replace('#^instagram\.com/#i', '', $value);
        $value = explode('?', $value)[0];
        $value = explode('/', $value)[0];
        $value = ltrim($value, '@');
        $value = strtolower(trim($value));

        return $value !== '' ? $value : null;
    }

    public function normalizeEmail(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return strtolower(trim($value));
    }

    public function normalizeMobile(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === '') {
            return null;
        }

        // Strip common Indian country / trunk prefixes so +91 / 0 variants match local 10-digit numbers.
        if (strlen($digits) > 10) {
            if (str_starts_with($digits, '91') && strlen($digits) >= 12) {
                $digits = substr($digits, -10);
            } elseif (str_starts_with($digits, '0') && strlen($digits) === 11) {
                $digits = substr($digits, 1);
            }
        }

        return $digits !== '' ? $digits : null;
    }

    /**
     * @return Collection<int, Influencer>
     */
    public function findDuplicates(array $data, ?int $excludeId = null): Collection
    {
        $username = $this->normalizeInstagram($data['instagram_username'] ?? $data['instagram_url'] ?? null);
        $email = $this->normalizeEmail($data['email'] ?? null);
        $mobile = $this->normalizeMobile($data['mobile'] ?? null);

        if (! $username && ! $email && ! $mobile) {
            return collect();
        }

        $query = Influencer::query();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->where(function ($q) use ($username, $email, $mobile) {
            if ($username) {
                $q->orWhereRaw('LOWER(instagram_username) = ?', [$username]);
            }
            if ($email) {
                $q->orWhereRaw('LOWER(email) = ?', [$email]);
            }
            if ($mobile) {
                $q->orWhere(function ($mobileQuery) use ($mobile) {
                    $mobileQuery->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(mobile, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') = ?",
                        [$mobile]
                    )->orWhereRaw(
                        "RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(mobile, ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), 10) = ?",
                        [substr($mobile, -10)]
                    );
                });
            }
        });

        return $query->limit(20)->get();
    }
}
