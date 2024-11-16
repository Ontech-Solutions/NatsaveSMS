<?php

namespace Database\Seeders;

use App\Models\Report;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run()
    {
        Report::create([
            'name' => 'Monthly SMS Usage',
            'type' => 'usage_report',
            'parameters' => [
                'month' => date('m'),
                'year' => date('Y'),
            ],
            'generated_by' => 1,
            'status' => 'completed',
        ]);
    }
}
