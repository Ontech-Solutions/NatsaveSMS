<?php

namespace App\Models;

use App\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Send extends Model
{
    protected $fillable = [
        'department_id',
        'user_id',
        'message_id',
        'internal_message_id',
        'source_addr',
        'destination_addr',
        'sms_type',
        'message',
        'status',
        'error_message',
        'priority_flag',
        'submitted_date',
        'done_date',
        'scheduled_at',
        'esm_class',
        'protocol_id',
        'data_coding',
        'registered_delivery',
        'service_type',
        'message_type',
        'recipient_count'
    ];

    protected $casts = [
        'submitted_date' => 'datetime',
        'done_date' => 'datetime',
        'scheduled_at' => 'datetime',
        //'status' => MessageStatus::class, // Cast to enum
        'priority_flag' => 'integer',
        'esm_class' => 'integer',
        'protocol_id' => 'integer',
        'data_coding' => 'integer',
        'registered_delivery' => 'integer',
        'recipient_count' => 'integer',
    ];

    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // public function getStatusDescriptionAttribute(): string
    // {
    //     return match($this->status) {
    //         MessageStatus::PENDING => 'Message is pending delivery',
    //         MessageStatus::DELIVERED => 'Message was delivered successfully',
    //         MessageStatus::UNDELIVERABLE => 'Message could not be delivered (general failure)',
    //         MessageStatus::QUEUED => 'Message is queued for later delivery',
    //         MessageStatus::REJECTED => 'Message was rejected (invalid destination)',
    //         MessageStatus::CANCELED => 'Message was canceled',
    //         MessageStatus::EXPIRED => 'Message expired before delivery',
    //         default => 'Unknown status'
    //     };
    // }

    protected static function boot()
    {
        parent::boot();

        // static::creating(function ($model) {
        //     $model->message_id = Str::uuid();
        //     //$model->status = MessageStatus::PENDING;
        // });
    }
}
