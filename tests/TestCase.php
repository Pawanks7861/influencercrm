<?php

namespace Tests;

use App\Models\User;
use App\Support\PermissionRegistrar;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar as SpatiePermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        app(SpatiePermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function createAdminUser(array $attributes = []): User
    {
        app(PermissionRegistrar::class)->register();

        $user = User::factory()->create(array_merge([
            'name' => 'Admin',
            'email' => 'admin@grovera.studio',
            'username' => 'admin',
            'email_verified_at' => now(),
        ], $attributes));

        $user->assignRole('admin');

        return $user;
    }

    protected function actingAsAdmin(array $attributes = []): User
    {
        $user = $this->createAdminUser($attributes);
        $this->actingAs($user);

        return $user;
    }
}
