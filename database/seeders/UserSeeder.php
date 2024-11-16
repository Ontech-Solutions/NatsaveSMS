<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create IT Admin
        User::create([
            'name' => 'IT Admin',
            'email' => 'admin@natsave.co.zm',
            'password' => Hash::make('Admin.1234!!!!'),
            'role' => 'Admin',
            'phone' => '260977000001',
            'department_id' => Department::where('name', 'IT Department')->first()->id,
            'is_active' => true,
            'daily_limit' => 1000,
            'monthly_limit' => 30000,
        ]);

        // Create Department Head
        User::create([
            'name' => 'Marketing Head',
            'email' => 'marketing.head@natsave.co.zm',
            'password' => Hash::make('Marketing.1234!!!!'),
            'role' => 'Department Head',
            'phone' => '260977000002',
            'department_id' => Department::where('name', 'Marketing')->first()->id,
            'is_active' => true,
            'daily_limit' => 500,
            'monthly_limit' => 15000,
        ]);

        // Create Branch User
        User::create([
            'name' => 'Branch Manager',
            'email' => 'cairo.branch@natsave.co.zm',
            'password' => Hash::make('Branch.1234!!!!'),
            'role' => 'Branch User',
            'phone' => '260977000003',
            'department_id' => Department::where('name', 'Customer Service')->first()->id,
            'branch_id' => Branch::where('code', 'BR001')->first()->id,
            'is_active' => true,
            'daily_limit' => 200,
            'monthly_limit' => 6000,
        ]);

        // Create API User
        User::create([
            'name' => 'API Integration',
            'email' => 'api@natsave.co.zm',
            'password' => Hash::make('Api.1234!!!!'),
            'role' => 'API User',
            'phone' => '260977000004',
            'department_id' => Department::where('name', 'IT Department')->first()->id,
            'is_active' => true,
            'daily_limit' => 2000,
            'monthly_limit' => 60000,
        ])->generateApiCredentials();
    }
}
