<?php

namespace App\Http\Controllers\ClientPortal;

use App\Enums\ShortlistItemStatus;
use App\Enums\ShortlistStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ShortlistResource;
use App\Models\Client;
use App\Models\InfluencerShortlist;
use App\Models\InfluencerShortlistItem;
use App\Services\Shortlist\ShortlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ShortlistController extends Controller
{
    public function __construct(protected ShortlistService $shortlistService)
    {
    }

    public function index(): Response
    {
        $client = $this->client();

        $shortlists = InfluencerShortlist::query()
            ->whereHas('requirement', fn ($q) => $q->where('client_id', $client->id))
            ->whereIn('status', ShortlistStatus::clientVisibleValues())
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString());
            })
            ->with(['requirement:id,requirement_number,title', 'items'])
            ->withCount('items')
            ->latest('shared_at')
            ->get();

        return Inertia::render('ClientPortal/Shortlists/Index', [
            'shortlists' => ShortlistResource::collection($shortlists)->resolve(),
        ]);
    }

    public function show(InfluencerShortlist $shortlist): Response
    {
        $this->authorizeShortlist($shortlist, allowExpired: true);

        $shortlist->load(['requirement:id,requirement_number,title', 'items']);

        if (! $shortlist->isExpired()) {
            $this->shortlistService->markViewed($shortlist);
            $shortlist->refresh()->load(['requirement:id,requirement_number,title', 'items']);
        }

        $payload = (new ShortlistResource($shortlist))->resolve();
        $payload['is_expired'] = $shortlist->isExpired();
        $payload['can_respond'] = ! $shortlist->isExpired();

        return Inertia::render('ClientPortal/Shortlists/Show', [
            'shortlist' => $payload,
        ]);
    }

    public function respond(Request $request, InfluencerShortlistItem $item): RedirectResponse
    {
        $shortlist = $item->shortlist;
        $this->authorizeShortlist($shortlist, allowExpired: false);

        if ($shortlist->isExpired()) {
            return back()->with('error', 'This shortlist has expired and can no longer be responded to.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(ShortlistItemStatus::clientResponseValues())],
            'client_remark' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->shortlistService->respond(
            $item,
            $validated['status'],
            $validated['client_remark'] ?? null
        );

        return back()->with('success', 'Response saved.');
    }

    protected function authorizeShortlist(InfluencerShortlist $shortlist, bool $allowExpired = false): void
    {
        $shortlist->loadMissing('requirement');

        abort_unless($shortlist->requirement?->client_id === $this->client()->id, 404);
        abort_unless($shortlist->hasClientVisibleStatus(), 404);

        if (! $allowExpired) {
            abort_unless(! $shortlist->isExpired(), 404);
        }
    }

    protected function client(): Client
    {
        return Auth::user()->client;
    }
}
