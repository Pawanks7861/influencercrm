<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar as SpatiePermissionRegistrar;

class PermissionRegistrar
{
    public const PERMISSIONS = [
        'dashboard.view',
        'influencers.view',
        'influencers.create',
        'influencers.edit',
        'influencers.delete',
        'influencers.import',
        'influencers.export',
        'influencers.manage_login',
        'influencer_portal.access',
        'client_portal.access',
        'clients.view',
        'clients.create',
        'clients.edit',
        'clients.delete',
        'clients.manage_login',
        'requirements.view',
        'requirements.create',
        'requirements.edit',
        'requirements.assign',
        'requirements.manage',
        'shortlists.create',
        'shortlists.edit',
        'shortlists.share',
        'shortlists.withdraw',
        'campaigns.view',
        'campaigns.create',
        'campaigns.edit',
        'campaigns.delete',
        'deliverables.manage',
        'followups.view',
        'followups.create',
        'followups.edit',
        'followups.manage',
        'payments.view',
        'payments.manage',
        'notes.manage',
        'activities.manage',
        'reports.view',
        'exports.manage',
        'imports.manage',
        'settings.manage',
    ];

    public const INFLUENCER_PERMISSIONS = [
        'influencer_portal.access',
    ];

    public const CLIENT_PERMISSIONS = [
        'client_portal.access',
    ];

    public const USER_PERMISSIONS = [
        'dashboard.view',
        'influencers.view',
        'influencers.create',
        'influencers.edit',
        'clients.view',
        'clients.create',
        'clients.edit',
        'requirements.view',
        'requirements.create',
        'requirements.edit',
        'shortlists.create',
        'shortlists.edit',
        'shortlists.share',
        'campaigns.view',
        'campaigns.create',
        'campaigns.edit',
        'deliverables.manage',
        'followups.view',
        'followups.create',
        'followups.edit',
        'followups.manage',
        'payments.view',
        'payments.manage',
        'notes.manage',
        'activities.manage',
        'reports.view',
        'exports.manage',
        'imports.manage',
    ];

    public function register(): void
    {
        app()[SpatiePermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions(self::PERMISSIONS);

        $user = Role::findOrCreate('user');
        $user->syncPermissions(self::USER_PERMISSIONS);

        $influencer = Role::findOrCreate('influencer');
        $influencer->syncPermissions(self::INFLUENCER_PERMISSIONS);

        $client = Role::findOrCreate('client');
        $client->syncPermissions(self::CLIENT_PERMISSIONS);
    }
}
