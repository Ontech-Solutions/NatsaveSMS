<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'description',
        'daily_limit',
        'monthly_limit',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function senderIds(): HasMany
    {
        return $this->hasMany(DepartmentSenderId::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
