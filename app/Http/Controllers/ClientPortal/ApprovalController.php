<?php

namespace App\Http\Controllers\ClientPortal;

use App\Enums\ShortlistItemStatus;
use App\Enums\ShortlistStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ShortlistItemResource;
use App\Models\Client;
use App\Models\InfluencerShortlistItem;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function index(): Response
    {
        $client = $this->client();

        $items = InfluencerShortlistItem::query()
            ->where('status', ShortlistItemStatus::Pending)
            ->whereHas('shortlist', function ($q) use ($client) {
                $q->whereIn('status', ShortlistStatus::clientVisibleValues())
                    ->where(function ($inner) {
                        $inner->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString());
                    })
                    ->whereHas('requirement', fn ($rq) => $rq->where('client_id', $client->id));
            })
            ->with(['shortlist.requirement:id,requirement_number,title'])
            ->orderBy('display_order')
            ->get();

        return Inertia::render('ClientPortal/Approvals/Index', [
            'items' => ShortlistItemResource::collection($items)->resolve(),
        ]);
    }

    protected function client(): Client
    {
        return Auth::user()->client;
    }
}
