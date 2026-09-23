<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignInfluencer;
use App\Models\Client;
use App\Models\Influencer;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:notes.manage');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notable_type' => ['required', 'string', 'in:influencer,campaign,campaign_influencer,client'],
            'notable_id' => ['required', 'integer'],
            'note' => ['required', 'string'],
        ]);

        $map = [
            'influencer' => Influencer::class,
            'campaign' => Campaign::class,
            'campaign_influencer' => CampaignInfluencer::class,
            'client' => Client::class,
        ];

        $type = $map[$validated['notable_type']];
        $model = $type::findOrFail($validated['notable_id']);

        $relation = $model instanceof Client ? 'noteEntries' : 'notes';

        $model->{$relation}()->create([
            'note' => $validated['note'],
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Note added.');
    }

    public function destroy(Note $note): RedirectResponse
    {
        $note->delete();

        return back()->with('success', 'Note deleted.');
    }
}
