<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder 
{
    public function run()
    {
        $departments = [
            ['name' => 'IT Department', 'description' => 'Information Technology'],
            ['name' => 'Customer Service', 'description' => 'Customer Support'],
            ['name' => 'Marketing', 'description' => 'Marketing and Communications'],
            ['name' => 'Operations', 'description' => 'Bank Operations'],
        ];

        foreach ($departments as $department) {
            \App\Models\Department::create($department);
        }
    }
}
