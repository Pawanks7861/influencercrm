<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClient
{
    /**
     * Ensure the user can access the client portal.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->can('client_portal.access')) {
            abort(403, 'Client portal access denied.');
        }

        $client = $user->client;

        if (! $client || ! $client->login_enabled) {
            abort(403, 'Client login is disabled or profile is not linked.');
        }

        return $next($request);
    }
}
