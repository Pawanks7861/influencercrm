<?php

namespace App\Http\Controllers\InfluencerPortal;

use App\Http\Controllers\Controller;
use App\Models\Influencer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        $influencer = $this->influencer();

        return Inertia::render('InfluencerPortal/Profile/Edit', [
            'influencer' => $influencer->only([
                'id',
                'name',
                'email',
                'mobile',
                'location',
                'instagram_url',
                'instagram_username',
                'youtube_url',
                'facebook_url',
                'linkedin_url',
                'twitter_url',
                'other_social_url',
                'influencer_type',
                'default_price',
                'status',
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $influencer = $this->influencer();
        $user = Auth::user();

        $validated = $request->validate([
            'mobile' => ['nullable', 'string', 'max:30'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('influencers', 'email')->ignore($influencer->id),
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'instagram_url' => ['nullable', 'string', 'max:500'],
            'youtube_url' => ['nullable', 'string', 'max:500'],
            'facebook_url' => ['nullable', 'string', 'max:500'],
            'linkedin_url' => ['nullable', 'string', 'max:500'],
            'twitter_url' => ['nullable', 'string', 'max:500'],
            'other_social_url' => ['nullable', 'string', 'max:500'],
        ]);

        // Block type/price and other sensitive fields server-side (not accepted).
        $influencer->fill($validated);
        $influencer->save();

        if ($user instanceof User && $user->email !== $validated['email']) {
            $user->email = $validated['email'];
            $user->save();
        }

        return back()->with('success', 'Profile updated.');
    }

    protected function influencer(): Influencer
    {
        return Auth::user()->influencer;
    }
}
