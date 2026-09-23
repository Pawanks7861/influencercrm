<?php

namespace App\Services\Influencer;

use App\Models\Influencer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InfluencerLoginService
{
    public function createLogin(Influencer $influencer, string $password, ?string $email = null): User
    {
        if ($influencer->user_id) {
            throw ValidationException::withMessages([
                'email' => 'This influencer already has a login account.',
            ]);
        }

        $email = strtolower(trim($email ?: (string) $influencer->email));

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'An email address is required to create influencer login.',
            ]);
        }

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email is already used by another user account.',
            ]);
        }

        return DB::transaction(function () use ($influencer, $password, $email) {
            if ($influencer->email !== $email) {
                $influencer->email = $email;
            }

            $user = User::query()->create([
                'name' => $influencer->name,
                'username' => $this->uniqueUsername($influencer, $email),
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            $user->assignRole('influencer');

            $influencer->user_id = $user->id;
            $influencer->login_enabled = true;
            $influencer->save();

            return $user;
        });
    }

    public function resetPassword(Influencer $influencer, string $password): User
    {
        $user = $this->requireUser($influencer);

        $user->password = Hash::make($password);
        $user->save();

        return $user;
    }

    public function disableLogin(Influencer $influencer): Influencer
    {
        $this->requireUser($influencer);

        $influencer->login_enabled = false;
        $influencer->save();

        return $influencer;
    }

    public function enableLogin(Influencer $influencer): Influencer
    {
        $this->requireUser($influencer);

        $influencer->login_enabled = true;
        $influencer->save();

        return $influencer;
    }

    protected function requireUser(Influencer $influencer): User
    {
        $user = $influencer->user;

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'This influencer does not have a login account yet.',
            ]);
        }

        return $user;
    }

    protected function uniqueUsername(Influencer $influencer, string $email): string
    {
        $base = Str::slug(Str::before($email, '@')) ?: Str::slug($influencer->name) ?: 'influencer';
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
