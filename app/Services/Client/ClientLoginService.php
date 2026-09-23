<?php

namespace App\Services\Client;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClientLoginService
{
    public function createLogin(Client $client, string $password, ?string $email = null): User
    {
        if ($client->user_id) {
            throw ValidationException::withMessages([
                'email' => 'This client already has a login account.',
            ]);
        }

        $email = strtolower(trim($email ?: (string) $client->email));

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'An email address is required to create client login.',
            ]);
        }

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email is already used by another user account.',
            ]);
        }

        return DB::transaction(function () use ($client, $password, $email) {
            if ($client->email !== $email) {
                $client->email = $email;
            }

            $user = User::query()->create([
                'name' => $client->displayName(),
                'username' => $this->uniqueUsername($client, $email),
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            $user->assignRole('client');

            $client->user_id = $user->id;
            $client->login_enabled = true;
            $client->save();

            return $user;
        });
    }

    public function resetPassword(Client $client, string $password): User
    {
        $user = $this->requireUser($client);

        $user->password = Hash::make($password);
        $user->save();

        return $user;
    }

    public function disableLogin(Client $client): Client
    {
        $this->requireUser($client);

        $client->login_enabled = false;
        $client->save();

        return $client;
    }

    public function enableLogin(Client $client): Client
    {
        $this->requireUser($client);

        $client->login_enabled = true;
        $client->save();

        return $client;
    }

    protected function requireUser(Client $client): User
    {
        $user = $client->user;

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'This client does not have a login account yet.',
            ]);
        }

        return $user;
    }

    protected function uniqueUsername(Client $client, string $email): string
    {
        $base = Str::slug(Str::before($email, '@')) ?: Str::slug($client->displayName()) ?: 'client';
        $base = Str::limit($base, 40, '');
        $username = $base;
        $suffix = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.$suffix;
            $suffix++;
        }

        return $username;
    }
}
