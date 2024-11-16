<?php

namespace Database\Seeders;

use App\Models\ApiLog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApiLogSeeder extends Seeder
{
    public function run()
    {
        ApiLog::create([
            'user_id' => 1,
            'method' => 'POST',
            'endpoint' => '/api/v1/send-sms',
            'request_data' => ['recipient' => '260977123456', 'message' => 'Test SMS'],
            'response_data' => ['status' => 'success', 'message_id' => 'MSG001'],
            'status_code' => 200,
            'ip_address' => '127.0.0.1',
            'processing_time' => 250,
        ]);
    }
}