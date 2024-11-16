<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsOutbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'source_addr',
        'destination_addr',
        'message',
        'message_type',
        'recipient_count',
        'status',
        'is_scheduled',
        'scheduled_at',
        'sent_at',
        'service_type',
        'data_coding',
        'registered_delivery',
        'priority_flag',
        'contact_group_id',
        'excel_file',
        'smsc_message_id',
        'error_code',
        'error_message',
        'retry_count',
        'last_retry_at',
        'user_id'
    ];

    protected $casts = [
        'is_scheduled' => 'boolean',
        'scheduled_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'sent_at' => 'datetime',
        'recipient_count' => 'integer',
        'data_coding' => 'integer',
        'registered_delivery' => 'integer',
        'priority_flag' => 'integer',
    ];

    public function contactGroup(): BelongsTo
    {
        return $this->belongsTo(ContactGroup::class);
    }

    public function getUrl(): string
    {
        return route('filament.admin.resources.sms-outboxes.view', ['record' => $this->id]);
    }
}