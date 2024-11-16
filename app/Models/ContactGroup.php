<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactGroup extends Model
{
    protected $fillable = [
        'department_id',
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Default values
    protected $attributes = [
        'is_active' => true
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_group_contact')
            ->withTimestamps();
    }
}
