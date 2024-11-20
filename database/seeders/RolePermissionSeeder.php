<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            
            // SMS management
            'view_messages',
            'send_messages',
            'schedule_messages',
            'delete_messages',
            
            // Contact management
            'view_contacts',
            'create_contacts',
            'edit_contacts',
            'delete_contacts',
            
            // Group management
            'view_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',
            
            // Report management
            'view_reports',
            'generate_reports',
            
            // Settings management
            'view_settings',
            'edit_settings',
            
            // Statistics
            'view_stats',
            
            // Department management
            'manage_departments',
            
            // Branch management
            'manage_branches',
            
            // API management
            'manage_api',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Super Admin
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        // Admin
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view_users', 'create_users', 'edit_users',
            'view_messages', 'send_messages', 'schedule_messages',
            'view_contacts', 'create_contacts', 'edit_contacts',
            'view_groups', 'create_groups', 'edit_groups',
            'view_reports', 'generate_reports',
            'view_settings', 'view_stats',
            'manage_departments', 'manage_branches'
        ]);

        // Department Head
        $departmentHeadRole = Role::create(['name' => 'department_head']);
        $departmentHeadRole->givePermissionTo([
            'view_messages', 'send_messages', 'schedule_messages',
            'view_contacts', 'create_contacts', 'edit_contacts',
            'view_groups', 'create_groups', 'edit_groups',
            'view_reports', 'generate_reports',
            'view_stats'
        ]);

        // Branch User
        $branchUserRole = Role::create(['name' => 'branch_user']);
        $branchUserRole->givePermissionTo([
            'view_messages', 'send_messages',
            'view_contacts', 'create_contacts',
            'view_groups',
            'view_reports',
            'view_stats'
        ]);

        // API User
        $apiUserRole = Role::create(['name' => 'api_user']);
        $apiUserRole->givePermissionTo([
            'send_messages',
            'view_messages',
            'manage_api'
        ]);

        // Assign roles to initial users
        $users = [
            'admin@natsave.co.zm' => 'super_admin',
            'admin@ontech.co.zm' => 'admin',
            'blessmore@ontech.co.zm' => 'department_head',
            'dennis@ontech.co.zm' => 'branch_user',
        ];

        foreach ($users as $email => $role) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->assignRole($role);
            }
        }
    }
}