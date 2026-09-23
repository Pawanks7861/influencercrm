<?php

namespace App\Http\Controllers;

use App\Models\Influencer;
use App\Services\Influencer\InfluencerLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class InfluencerLoginController extends Controller
{
    public function __construct(protected InfluencerLoginService $loginService)
    {
        $this->middleware('permission:influencers.manage_login|influencers.edit');
    }

    public function store(Request $request, Influencer $influencer): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->loginService->createLogin(
            $influencer,
            $validated['password'],
            $validated['email']
        );

        return back()->with('success', 'Influencer login created successfully.');
    }

    public function resetPassword(Request $request, Influencer $influencer): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->loginService->resetPassword($influencer, $validated['password']);

        return back()->with('success', 'Influencer password reset successfully.');
    }

    public function disable(Influencer $influencer): RedirectResponse
    {
        $this->loginService->disableLogin($influencer);

        return back()->with('success', 'Influencer login disabled.');
    }

    public function enable(Influencer $influencer): RedirectResponse
    {
        $this->loginService->enableLogin($influencer);

        return back()->with('success', 'Influencer login re-enabled.');
    }
}
