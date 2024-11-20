<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class ApiUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'api_key',
        'api_secret',
        'is_active',
        'daily_limit',
        'description',
        'last_used_at',
        'usage_count',
        'rate_limit',
        'access_level',
        'allowed_ips',
        'last_key_generated_at',
        'last_key_revoked_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'daily_limit' => 'integer',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'last_key_generated_at' => 'datetime',
        'last_key_revoked_at' => 'datetime',
        'allowed_ips' => 'array',
        'rate_limit' => 'integer'
    ];

    protected $attributes = [
        'rate_limit' => 60,          // Default 60 requests per minute
        'access_level' => 'read',    // Default to read-only access
        'daily_limit' => 1000,       // Default daily limit
        'usage_count' => 0,          // Start with 0 usage
        'is_active' => true,         // Default to active
        'allowed_ips' => '[]'        // Default to empty array
    ];

    // Constants for access levels
    const ACCESS_LEVEL_READ = 'read';
    const ACCESS_LEVEL_WRITE = 'write';
    const ACCESS_LEVEL_ADMIN = 'admin';

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHighUsage($query, $threshold = 5000)
    {
        return $query->where('usage_count', '>', $threshold);
    }

    public function scopeRecentlyActive($query, $days = 7)
    {
        return $query->where('last_used_at', '>=', now()->subDays($days));
    }

    // Relationships
    public function apiLogs()
    {
        return $this->hasMany(ApiLog::class);
    }

    // Attribute accessors
    public function getTodayUsageAttribute()
    {
        return $this->apiLogs()
            ->whereDate('created_at', today())
            ->count();
    }

    public function getRemainingDailyLimitAttribute()
    {
        return max(0, $this->daily_limit - $this->today_usage);
    }

    public function getIsOverLimitAttribute()
    {
        return $this->today_usage >= $this->daily_limit;
    }

    public function getKeyAgeInDaysAttribute()
    {
        return $this->last_key_generated_at
            ? $this->last_key_generated_at->diffInDays(now())
            : null;
    }

    // Helper methods
    public function hasAccessLevel(string $level): bool
    {
        $accessHierarchy = [
            self::ACCESS_LEVEL_READ => 1,
            self::ACCESS_LEVEL_WRITE => 2,
            self::ACCESS_LEVEL_ADMIN => 3,
        ];

        return $accessHierarchy[$this->access_level] >= $accessHierarchy[$level];
    }

    public function isIpAllowed(?string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true; // If no IPs are specified, allow all
        }

        return in_array($ip, $this->allowed_ips);
    }

    public function incrementUsage(): void
    {
        $this->update([
            'usage_count' => $this->usage_count + 1,
            'last_used_at' => now(),
        ]);
    }

    public function checkRateLimit(): bool
    {
        $requestsInLastMinute = $this->apiLogs()
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        return $requestsInLastMinute < $this->rate_limit;
    }

    public function revokeAccess(): void
    {
        $this->update([
            'api_key' => null,
            'api_secret' => null,
            'is_active' => false,
            'last_key_revoked_at' => now(),
        ]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($apiUser) {
            if (empty($apiUser->allowed_ips)) {
                $apiUser->allowed_ips = [];
            }
        });
    }
}

// class ApiUser extends Model
// {
//     use HasFactory;

//     protected $fillable = [
//         'name',
//         'email',
//         'api_key',
//         'api_secret',
//         'is_active',
//         'daily_limit',
//         'description',
//         'last_used_at',
//         'usage_count'
//     ];

//     protected $casts = [
//         'is_active' => 'boolean',
//         'daily_limit' => 'integer',
//         'usage_count' => 'integer',
//         'last_used_at' => 'datetime',
//     ];

//     // Scope for active API users
//     public function scopeActive($query)
//     {
//         return $query->where('is_active', true);
//     }

//     // Relationship with API logs
//     public function apiLogs()
//     {
//         return $this->hasMany(ApiLog::class);
//     }

//     // Get today's usage
//     public function getTodayUsageAttribute()
//     {
//         return $this->apiLogs()
//             ->whereDate('created_at', today())
//             ->count();
//     }
// }