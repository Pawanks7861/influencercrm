<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        $client = $this->client();

        return Inertia::render('ClientPortal/Profile/Edit', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'company_name' => $client->company_name,
                'contact_person' => $client->contact_person,
                'email' => $client->email,
                'mobile' => $client->mobile,
                'website' => $client->website,
            ],
        ]);
    }

    protected function client(): Client
    {
        return Auth::user()->client;
    }
}
