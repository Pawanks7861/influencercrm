<?php

namespace App\Services\Requirement;

use App\Models\ClientRequirement;
use Illuminate\Support\Facades\DB;

class RequirementNumberGenerator
{
    public function next(): string
    {
        $year = now()->format('Y');
        $prefix = "REQ-{$year}-";

        return DB::transaction(function () use ($prefix) {
            $latest = ClientRequirement::query()
                ->withTrashed()
                ->where('requirement_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('requirement_number')
                ->value('requirement_number');

            $sequence = 1;
            if ($latest && preg_match('/REQ-\d{4}-(\d+)$/', $latest, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }
}
