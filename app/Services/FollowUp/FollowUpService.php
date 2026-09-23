<?php

namespace App\Services\FollowUp;

use App\Enums\FollowUpStatus;
use App\Models\FollowUp;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FollowUpService
{
    public function create(array $data): FollowUp
    {
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? FollowUpStatus::Pending->value;
        $data['assigned_to'] = $data['assigned_to'] ?? Auth::id();

        return FollowUp::create($data)->load(['influencer', 'client', 'campaign', 'assignee']);
    }

    public function complete(FollowUp $followUp): FollowUp
    {
        $followUp->update([
            'status' => FollowUpStatus::Completed,
            'completed_at' => now(),
        ]);

        return $followUp->fresh(['influencer', 'client', 'campaign', 'assignee']);
    }

    public function cancel(FollowUp $followUp): FollowUp
    {
        $followUp->update([
            'status' => FollowUpStatus::Cancelled,
        ]);

        return $followUp->fresh(['influencer', 'client', 'campaign', 'assignee']);
    }

    public function reschedule(FollowUp $followUp, string $date, ?string $time = null): FollowUp
    {
        $followUp->update([
            'follow_up_date' => $date,
            'follow_up_time' => $time,
            'status' => FollowUpStatus::Pending,
            'completed_at' => null,
        ]);

        return $followUp->fresh(['influencer', 'client', 'campaign', 'assignee']);
    }

    public function overdueQuery(?int $userId = null): Builder
    {
        $query = FollowUp::query()
            ->where('status', FollowUpStatus::Pending)
            ->whereDate('follow_up_date', '<', Carbon::today());

        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        return $query;
    }

    public function todayQuery(?int $userId = null): Builder
    {
        $query = FollowUp::query()
            ->where('status', FollowUpStatus::Pending)
            ->whereDate('follow_up_date', Carbon::today());

        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        return $query;
    }

    public function counts(?int $userId = null): array
    {
        return [
            'today' => $this->todayQuery($userId)->count(),
            'overdue' => $this->overdueQuery($userId)->count(),
        ];
    }
}
