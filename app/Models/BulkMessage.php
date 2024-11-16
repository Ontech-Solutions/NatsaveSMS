<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkMessage extends Model
{
    protected $fillable = [
        'department_id',
        'user_id',
        'sender_id',
        'message_template',
        'total_recipients',
        'processed_count',
        'success_count',
        'failed_count',
        'status',
        'scheduled_at',
        'completed_at',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
