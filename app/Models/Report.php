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
    ];

    protected $casts = [
        'parameters' => 'array',
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}