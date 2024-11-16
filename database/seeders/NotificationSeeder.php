<?php

namespace Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        Notification::create([
            'user_id' => 1,
            'title' => 'Welcome to SMS Portal',
            'message' => 'Welcome to the SMS Portal. Get started by exploring the dashboard.',
            'type' => 'system',
        ]);
    }
}
