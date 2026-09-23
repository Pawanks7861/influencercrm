<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function influencer(): HasOne
    {
        return $this->hasOne(Influencer::class);
    }

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class, 'assigned_to');
    }

    public function createdFollowUps(): HasMany
    {
        return $this->hasMany(FollowUp::class, 'created_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'created_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(InfluencerActivity::class, 'created_by');
    }

    public function tablePreferences(): HasMany
    {
        return $this->hasMany(UserTablePreference::class);
    }

    public function importJobs(): HasMany
    {
        return $this->hasMany(ImportJob::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'created_by');
    }
}
