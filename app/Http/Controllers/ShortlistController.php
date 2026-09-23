<?php

namespace App\Http\Controllers;

use App\Models\ClientRequirement;
use App\Models\InfluencerShortlist;
use App\Models\InfluencerShortlistItem;
use App\Services\Shortlist\ShortlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShortlistController extends Controller
{
    public function __construct(protected ShortlistService $shortlistService)
    {
        $this->middleware('permission:shortlists.create')->only(['store', 'addItem']);
        $this->middleware('permission:shortlists.edit')->only(['update', 'removeItem']);
        $this->middleware('permission:shortlists.share')->only(['share']);
        $this->middleware('permission:shortlists.withdraw')->only(['withdraw']);
    }

    public function store(Request $request, ClientRequirement $requirement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.influencer_id' => ['required', 'exists:influencers,id'],
            'items.*.client_price' => ['required', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.show_name' => ['boolean'],
            'items.*.show_instagram' => ['boolean'],
            'items.*.show_price' => ['boolean'],
            'items.*.show_location' => ['boolean'],
            'items.*.show_type' => ['boolean'],
            'items.*.show_note' => ['boolean'],
        ]);

        $this->shortlistService->create(
            $requirement,
            $validated['title'],
            $validated['items'],
            $request->user(),
            $validated['message'] ?? null
        );

        return back()->with('success', 'Shortlist created.');
    }

    public function update(Request $request, InfluencerShortlist $shortlist): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required', 'exists:influencer_shortlist_items,id'],
            'items.*.client_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.display_order' => ['nullable', 'integer', 'min:0'],
            'items.*.show_name' => ['boolean'],
            'items.*.show_instagram' => ['boolean'],
            'items.*.show_price' => ['boolean'],
            'items.*.show_location' => ['boolean'],
            'items.*.show_type' => ['boolean'],
            'items.*.show_note' => ['boolean'],
        ]);

        if (isset($validated['title']) || array_key_exists('message', $validated)) {
            $shortlist->fill(collect($validated)->only(['title', 'message'])->all());
            $shortlist->save();
        }

        if (! empty($validated['items'])) {
            $this->shortlistService->updateItems($shortlist, $validated['items']);
        }

        return back()->with('success', 'Shortlist updated.');
    }

    public function addItem(Request $request, InfluencerShortlist $shortlist): RedirectResponse
    {
        $validated = $request->validate([
            'influencer_id' => ['required', 'exists:influencers,id', Rule::unique('influencer_shortlist_items', 'influencer_id')->where('influencer_shortlist_id', $shortlist->id)],
            'client_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'show_name' => ['boolean'],
            'show_instagram' => ['boolean'],
            'show_price' => ['boolean'],
            'show_location' => ['boolean'],
            'show_type' => ['boolean'],
            'show_note' => ['boolean'],
        ]);

        $this->shortlistService->addItems($shortlist, [$validated]);

        return back()->with('success', 'Influencer added to shortlist.');
    }

    public function removeItem(InfluencerShortlist $shortlist, InfluencerShortlistItem $item): RedirectResponse
    {
        $this->shortlistService->removeItem($shortlist, $item);

        return back()->with('success', 'Influencer removed from shortlist.');
    }

    public function share(Request $request, InfluencerShortlist $shortlist): RedirectResponse
    {
        $validated = $request->validate([
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $this->shortlistService->share($shortlist, $request->user(), $validated['expires_at'] ?? null);

        return back()->with('success', 'Shortlist shared with client.');
    }

    public function withdraw(InfluencerShortlist $shortlist): RedirectResponse
    {
        $this->shortlistService->withdraw($shortlist);

        return back()->with('success', 'Shortlist withdrawn.');
    }
}
