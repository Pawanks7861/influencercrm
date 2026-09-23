<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInfluencer
{
    /**
     * Ensure the user can access the influencer portal.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->can('influencer_portal.access')) {
            abort(403, 'Influencer portal access denied.');
        }

        $influencer = $user->influencer;

        if (! $influencer || ! $influencer->login_enabled) {
            abort(403, 'Influencer login is disabled or profile is not linked.');
        }

        return $next($request);
    }
}
