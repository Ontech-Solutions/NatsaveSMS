<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsInbox extends Model
{
    protected $fillable = [
        'message_id',
        'sender',
        'recipient',
        'message',
        'received_at',
        'processed_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
