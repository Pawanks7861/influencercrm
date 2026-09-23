<?php

namespace App\Services\Client;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

class ClientDuplicateDetectionService
{
    public function normalizeMobile(?string $mobile): ?string
    {
        if ($mobile === null || trim($mobile) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        if (strlen($digits) > 10 && str_starts_with($digits, '91')) {
            $digits = substr($digits, -10);
        }

        return $digits !== '' ? $digits : null;
    }

    public function normalizeEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return strtolower(trim($email));
    }

    public function normalizeCompany(?string $company): ?string
    {
        if ($company === null || trim($company) === '') {
            return null;
        }

        return strtolower(trim(preg_replace('/\s+/', ' ', $company) ?? $company));
    }

    /**
     * @return Collection<int, Client>
     */
    public function findDuplicates(array $data, ?int $excludeId = null): Collection
    {
        $company = $this->normalizeCompany($data['company_name'] ?? $data['name'] ?? null);
        $mobile = $this->normalizeMobile($data['mobile'] ?? $data['phone'] ?? null);
        $email = $this->normalizeEmail($data['email'] ?? null);

        if (! $company && ! $mobile && ! $email) {
            return new Collection;
        }

        $query = Client::query();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->where(function ($q) use ($company, $mobile, $email) {
            if ($mobile) {
                $q->orWhereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(mobile, ''), ' ', ''), '-', ''), '+', ''), '(', '') LIKE ?",
                    ['%'.$mobile]
                )->orWhereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '-', ''), '+', ''), '(', '') LIKE ?",
                    ['%'.$mobile]
                );
            }

            if ($email) {
                $q->orWhereRaw('LOWER(email) = ?', [$email]);
            }

            if ($company && $mobile) {
                $q->orWhere(function ($inner) use ($company, $mobile) {
                    $inner->whereRaw('LOWER(TRIM(COALESCE(company_name, name, ""))) = ?', [$company])
                        ->where(function ($m) use ($mobile) {
                            $m->whereRaw(
                                "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(mobile, ''), ' ', ''), '-', ''), '+', ''), '(', '') LIKE ?",
                                ['%'.$mobile]
                            )->orWhereRaw(
                                "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '-', ''), '+', ''), '(', '') LIKE ?",
                                ['%'.$mobile]
                            );
                        });
                });
            }
        });

        return $query->orderBy('company_name')->limit(10)->get();
    }
}
