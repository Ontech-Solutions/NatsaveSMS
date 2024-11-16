<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledSms extends Model
{
    protected $fillable = [
        'user_id',
        'message_id',
        'source_addr',
        'destination_addr',
        'message',
        'status',
        'error_message',
        'priority_flag',
        'schedule_type',
        'schedule_data',
        'next_run_at',
        'last_run_at',
        'esm_class',
        'protocol_id',
        'data_coding',
        'registered_delivery',
        'service_type',
        'message_type',
        'recipient_count'
    ];

    protected $casts = [
        'schedule_data' => 'array',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'priority_flag' => 'integer',
        'esm_class' => 'integer',
        'protocol_id' => 'integer',
        'data_coding' => 'integer',
        'registered_delivery' => 'integer',
        'recipient_count' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
