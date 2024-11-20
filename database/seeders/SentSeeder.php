<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sent;
use Carbon\Carbon;

class SentSeeder extends Seeder
{
    public function run(): void
    {
        // Single Messages - Various Statuses
        Sent::create([
            'user_id' => 1,
            'message_id' => 'MSG-' . uniqid(),
            'source_addr' => 'NATSAVE',
            'destination_addr' => '+260977123456',
            'message' => 'Your account balance is K5,000. Thank you for banking with us.',
            'sms_type' => Sent::TYPE_SINGLE,
            'status' => 'delivered',
            'sent_at' => now(),
            'submitted_date' => now()->subMinutes(5),
            'done_date' => now()->subMinutes(2),
            'service_type' => 'SMS',
            'data_coding' => 0,
            'registered_delivery' => 1,
            'priority_flag' => 0,
        ]);

        // Failed Message with Error
        Sent::create([
            'user_id' => 1,
            'message_id' => 'MSG-' . uniqid(),
            'source_addr' => 'NATSAVE',
            'destination_addr' => '+260966111222',
            'message' => 'Important: Your loan payment is due tomorrow.',
            'sms_type' => Sent::TYPE_SINGLE,
            'status' => 'failed',
            'sent_at' => now(),
            'submitted_date' => now()->subMinutes(10),
            'error_message' => 'Invalid destination address',
            'service_type' => 'SMS',
            'retry_count' => 2,
            'last_retry_at' => now()->subMinutes(5),
        ]);

        // Bulk Message - Successfully Delivered
        foreach (['+260977111222', '+260966333444', '+260955555666'] as $number) {
            Sent::create([
                'user_id' => 2,
                'message_id' => 'BULK-' . uniqid(),
                'source_addr' => 'NATSAVE',
                'destination_addr' => $number,
                'message' => 'Dear valued customer, our branches will be closed on Monday for a public holiday.',
                'sms_type' => Sent::TYPE_BULK,
                'status' => Sent::STATUS_DELIVERED,
                'sent_at' => now()->subHours(2),
                'submitted_date' => now()->subHours(2),
                'done_date' => now()->subHours(1),
                'service_type' => 'SMS',
            ]);
        }

        // Group Message - Mixed Statuses
        $groupNumbers = [
            ['+260977999888', 'delivered'],
            ['+260966777666', 'failed', 'Network timeout'],
            ['+260955444333', 'submitted'],
        ];

        foreach ($groupNumbers as $data) {
            Sent::create([
                'user_id' => 3,
                'message_id' => 'GRP-' . uniqid(),
                'source_addr' => 'ALERT',
                'destination_addr' => $data[0],
                'message' => 'Join us for our customer appreciation day this Saturday!',
                'sms_type' => Sent::TYPE_BULK,
                'status' => Sent::STATUS_DELIVERED,
                'sent_at' => now()->subDay(),
                'submitted_date' => now()->subDay(),
                'done_date' => $data[1] === 'delivered' ? now()->subDay()->addHours(1) : null,
                'error_message' => $data[1] === 'failed' ? $data[2] : null,
                'service_type' => 'SMS',
            ]);
        }

        // Pending Messages
        Sent::create([
            'user_id' => 1,
            'message_id' => 'MSG-' . uniqid(),
            'source_addr' => 'INFO',
            'destination_addr' => '+260977123456',
            'message' => 'Your ATM card is ready for collection at our main branch.',
            'sms_type' => Sent::TYPE_SINGLE,
            'status' => 'pending',
            'sent_at' => now(),
            'service_type' => 'SMS',
        ]);

        // Scheduled Message for Future
        Sent::create([
            'user_id' => 2,
            'message_id' => 'SCH-' . uniqid(),
            'source_addr' => 'NATSAVE',
            'destination_addr' => '+260977654321',
            'message' => 'Reminder: Your fixed deposit will mature tomorrow.',
            'sms_type' => Sent::TYPE_SINGLE,
            'status' => 'pending',
            'scheduled_at' => now()->addDays(1),
            'sent_at' => null,
            'service_type' => 'SMS',
        ]);
    }
}