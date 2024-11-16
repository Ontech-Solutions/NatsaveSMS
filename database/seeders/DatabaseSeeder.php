<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            DepartmentSeeder::class,
            BranchSeeder::class,
            UserSeeder::class,
            SenderIdSeeder::class,
            NotificationSeeder::class,
            SenderStatusSeeder::class,
            ApiLogSeeder::class,
            ReportSeeder::class,
        ]);
    }
}
