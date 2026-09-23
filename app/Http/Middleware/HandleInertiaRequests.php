<?php

namespace App\Http\Middleware;

use App\Services\FollowUp\FollowUpService;
use App\Services\Settings\SettingsService;
use App\Models\PortalNotification;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $settings = app(SettingsService::class);
        $followUpCounts = ['today' => 0, 'overdue' => 0];
        $portalNotifications = [];
        $unreadNotificationCount = 0;

        if ($user) {
            $user->loadMissing('roles', 'permissions', 'influencer', 'client');
            if (! $user->hasAnyRole(['influencer', 'client'])) {
                $followUpCounts = app(FollowUpService::class)->counts($user->id);
            }

            if ($user->hasRole('client')) {
                $portalNotifications = PortalNotification::query()
                    ->where('user_id', $user->id)
                    ->latest()
                    ->limit(8)
                    ->get(['id', 'type', 'title', 'body', 'link', 'read_at', 'created_at']);
                $unreadNotificationCount = PortalNotification::query()
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count();
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'influencer_id' => $user->influencer?->id,
                    'client_id' => $user->client?->id,
                ] : null,
            ],
            'settings' => [
                'studio_name' => $settings->get('studio_name'),
                'currency' => $settings->get('currency'),
                'currency_symbol' => $settings->get('currency_symbol'),
                'date_format' => $settings->get('date_format'),
                'timezone' => $settings->get('timezone'),
            ],
            'crm' => [
                'influencer_types' => config('crm.influencer_types'),
                'collaboration_statuses' => config('crm.collaboration_statuses'),
                'campaign_statuses' => config('crm.campaign_statuses'),
                'campaign_types' => config('crm.campaign_types'),
                'platforms' => config('crm.platforms'),
                'client_statuses' => config('crm.client_statuses'),
                'client_activity_types' => config('crm.client_activity_types'),
                'deliverable_types' => config('crm.deliverable_types'),
                'deliverable_statuses' => config('crm.deliverable_statuses'),
                'payment_methods' => config('crm.payment_methods'),
                'activity_types' => config('crm.activity_types'),
                'requirement_types' => config('crm.requirement_types'),
                'requirement_statuses' => config('crm.requirement_statuses'),
                'shortlist_statuses' => config('crm.shortlist_statuses'),
                'shortlist_item_statuses' => config('crm.shortlist_item_statuses'),
                'preferred_categories' => config('crm.preferred_categories'),
                'services' => config('crm.services'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'followUps' => $followUpCounts,
            'portalNotifications' => $portalNotifications,
            'unreadNotificationCount' => $unreadNotificationCount,
        ];
    }
}
