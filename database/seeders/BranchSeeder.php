<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $branches = [
            ['name' => 'Main Branch', 'code' => 'HQ001', 'department_id' => 1],
            ['name' => 'Cairo Road', 'code' => 'BR001', 'department_id' => 2],
            ['name' => 'Kitwe', 'code' => 'BR002', 'department_id' => 2],
            ['name' => 'Ndola', 'code' => 'BR003', 'department_id' => 2],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}
