<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Support\PermissionRegistrar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->register();
        app(SettingsService::class)->seedDefaults();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@grovera.studio'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        $user = User::query()->updateOrCreate(
            ['email' => 'user@grovera.studio'],
            [
                'name' => 'Studio User',
                'username' => 'user',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole('user');

        $this->call(DemoSeeder::class);
    }
}
