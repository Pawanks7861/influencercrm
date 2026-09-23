<?php

namespace App\Support;

use App\Models\User;
use App\Providers\RouteServiceProvider;

class AuthRedirect
{
    public static function home(?User $user = null): string
    {
        if ($user && $user->hasRole('influencer')) {
            return route('influencer.portal.dashboard');
        }

        if ($user && $user->hasRole('client')) {
            return route('client.portal.dashboard');
        }

        return RouteServiceProvider::HOME;
    }
}
