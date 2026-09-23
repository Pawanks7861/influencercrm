<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Models\InfluencerActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:activities.manage');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'influencer_id' => ['required', 'exists:influencers,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'activity_type' => ['required', Rule::enum(ActivityType::class)],
            'activity_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        InfluencerActivity::create([
            ...$validated,
            'activity_at' => $validated['activity_at'] ?? now(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Activity logged.');
    }

    public function destroy(InfluencerActivity $activity): RedirectResponse
    {
        $activity->delete();

        return back()->with('success', 'Activity deleted.');
    }
}
