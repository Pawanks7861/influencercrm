<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\CampaignResource;
use App\Models\Campaign;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function index(): Response
    {
        $client = $this->client();

        $campaigns = Campaign::query()
            ->where('client_id', $client->id)
            ->whereHas('clientVisibility', fn ($q) => $q->where('visible_to_client', true))
            ->with('clientVisibility')
            ->latest()
            ->get();

        return Inertia::render('ClientPortal/Campaigns/Index', [
            'campaigns' => CampaignResource::collection($campaigns)->resolve(),
        ]);
    }

    public function show(Campaign $campaign): Response
    {
        $this->authorizeCampaign($campaign);

        $campaign->load([
            'clientVisibility',
            'campaignInfluencers.influencer:id,name,instagram_username,instagram_url',
            'deliverables',
        ]);

        return Inertia::render('ClientPortal/Campaigns/Show', [
            'campaign' => (new CampaignResource($campaign))->resolve(),
        ]);
    }

    protected function authorizeCampaign(Campaign $campaign): void
    {
        $campaign->loadMissing('clientVisibility');

        abort_unless($campaign->client_id === $this->client()->id, 404);
        abort_unless((bool) $campaign->clientVisibility?->visible_to_client, 404);
    }

    protected function client(): Client
    {
        return Auth::user()->client;
    }
}
