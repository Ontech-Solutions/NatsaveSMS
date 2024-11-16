<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SenderId;
use App\Models\SenderStatus;
use App\Models\Department;
use App\Models\User;

class SenderIdSeeder extends Seeder
{
    public function run(): void
    {
        // First ensure we have statuses
        $statuses = [
            ['name' => 'pending'],
            ['name' => 'active'],
            ['name' => 'inactive'],
            ['name' => 'rejected'],
        ];

        foreach ($statuses as $status) {
            SenderStatus::firstOrCreate($status);
        }

        // Get the IDs we'll need
        $pendingStatus = SenderStatus::where('name', 'pending')->first();
        $activeStatus = SenderStatus::where('name', 'active')->first();
        $inactiveStatus = SenderStatus::where('name', 'inactive')->first();
        
        // Get a default department (create if doesn't exist)
        $department = Department::firstOrCreate(
            ['name' => 'Marketing'],
            ['description' => 'Marketing Department']
        );

        // Get an admin user (create if doesn't exist)
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'password' => bcrypt('password'),
                'department_id' => $department->id,
            ]
        );

        // Sample Sender IDs
        $senderIds = [
            [
                'sender_name' => 'COMPANY',
                'department_id' => $department->id,
                'purpose' => 'General company communications',
                'sender_status_id' => $activeStatus->id,
                'approved_at' => now(),
                'approved_by' => $admin->id,
            ],
            [
                'sender_name' => 'MARKETING',
                'department_id' => $department->id,
                'purpose' => 'Marketing campaigns and promotions',
                'sender_status_id' => $activeStatus->id,
                'approved_at' => now(),
                'approved_by' => $admin->id,
            ],
            [
                'sender_name' => 'SUPPORT',
                'department_id' => $department->id,
                'purpose' => 'Customer support messages',
                'sender_status_id' => $activeStatus->id,
                'approved_at' => now(),
                'approved_by' => $admin->id,
            ],
            [
                'sender_name' => 'ALERTS',
                'department_id' => $department->id,
                'purpose' => 'System alerts and notifications',
                'sender_status_id' => $pendingStatus->id,
            ],
            [
                'sender_name' => 'EVENTS',
                'department_id' => $department->id,
                'purpose' => 'Event notifications and reminders',
                'sender_status_id' => $inactiveStatus->id,
                'approved_at' => now()->subDays(30),
                'approved_by' => $admin->id,
            ],
        ];

        foreach ($senderIds as $senderId) {
            SenderId::firstOrCreate(
                ['sender_name' => $senderId['sender_name']],
                $senderId
            );
        }
    }
}