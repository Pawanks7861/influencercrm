<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\Client\ClientLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ClientLoginController extends Controller
{
    public function __construct(protected ClientLoginService $loginService)
    {
        $this->middleware('permission:clients.manage_login|clients.edit');
    }

    public function store(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->loginService->createLogin(
            $client,
            $validated['password'],
            $validated['email']
        );

        return back()->with('success', 'Client login created successfully.');
    }

    public function resetPassword(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->loginService->resetPassword($client, $validated['password']);

        return back()->with('success', 'Client password reset successfully.');
    }

    public function disable(Client $client): RedirectResponse
    {
        $this->loginService->disableLogin($client);

        return back()->with('success', 'Client login disabled.');
    }

    public function enable(Client $client): RedirectResponse
    {
        $this->loginService->enableLogin($client);

        return back()->with('success', 'Client login re-enabled.');
    }
}
