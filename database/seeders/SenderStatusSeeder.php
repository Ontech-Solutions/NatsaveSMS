<?php

namespace Database\Seeders;

use App\Models\SenderStatus;
use Illuminate\Database\Seeder;

class SenderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'pending',
            'active',
            'inactive',
            'rejected'
        ];

        foreach ($statuses as $status) {
            SenderStatus::create(['name' => $status]);
        }
    }
}