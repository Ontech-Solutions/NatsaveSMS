<?php

// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'branch_id',
        'phone',
        'api_key',
        'api_secret',
        'daily_limit',
        'monthly_limit',
        'is_active',
        'last_login_at',
        'last_login_ip'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'daily_limit' => 'integer',
        'monthly_limit' => 'integer',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && in_array($this->role, ['Admin', 'Department Head', 'Branch User']);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function generateApiCredentials(): void
    {
        $this->api_key = Str::random(32);
        $this->api_secret = Str::random(64);
        $this->save();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function apiLogs()
    {
        return $this->hasMany(ApiLog::class);
    }
}
