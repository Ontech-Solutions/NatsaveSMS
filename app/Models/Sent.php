<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sent extends Model
{
    protected $table = 'sents';

    protected $fillable = [
        'user_id',
        'message_id',
        'internal_message_id',
        'source_addr',
        'destination_addr',
        'message',
        'sms_type',
        'message_type',
        'recipient_count',
        'status',
        'scheduled_at',
        'sent_at',
        'submitted_date',
        'done_date',
        'service_type',
        'data_coding',
        'registered_delivery',
        'priority_flag',
        'esm_class',
        'protocol_id',
        'contact_group_id',
        'excel_file',
        'smsc_message_id',
        'error_code',
        'error_message',
        'retry_count',
        'last_retry_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'submitted_date' => 'datetime',
        'done_date' => 'datetime',
        'last_retry_at' => 'datetime',
        'status' => 'string',
        'sms_type' => 'string',
        'recipient_count' => 'integer',
        'retry_count' => 'integer',
        'priority_flag' => 'integer',
        'data_coding' => 'integer',
        'registered_delivery' => 'integer',
        'esm_class' => 'integer',
        'protocol_id' => 'integer',
    ];

    // Define the enum values if you want to use them in your code
    const STATUS_PENDING = 'pending';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';

    const TYPE_SINGLE = 'single';
    const TYPE_BULK = 'bulk';
    const TYPE_GROUP = 'group';

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contactGroup()
    {
        return $this->belongsTo(ContactGroup::class);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function canBeResent(): bool
    {
        return $this->isFailed() && $this->retry_count < 3; // Max 3 retries
    }
}