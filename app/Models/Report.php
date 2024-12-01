<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'name',
        'type',
        'parameters',
        'data',
        'generated_by',
        'file_path',
        'status',
        'include_charts',
        'file_format'
    ];

    protected $casts = [
        'parameters' => 'array',
        'data' => 'array',
        'include_charts' => 'boolean',
    ];

    // Define the relationship to User
    public function user()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}