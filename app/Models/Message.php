<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'department_id',
        'user_id',
        'sender_id',
        'recipient',
        'message',
        'message_type', // single, bulk, api
        'status',
        'scheduled_at',
        'sent_at',
        'delivered_at',
        'error_message',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(SenderId::class);
    }
}
