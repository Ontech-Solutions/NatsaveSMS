<?php

namespace Database\Seeders;

use App\Models\ApiUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create an admin API user with full access
        ApiUser::create([
            'name' => 'Admin API User',
            'email' => 'admin@natsave.co.zm',
            'api_key' => 'key_' . Str::random(32),
            'api_secret' => 'secret_' . Str::random(32),
            'is_active' => true,
            'access_level' => 'admin',
            'daily_limit' => 10000,
            'rate_limit' => 120,
            'description' => 'Administrative API user with full access',
            'allowed_ips' => ['*'], // Allow all IPs
            'last_key_generated_at' => now(),
        ]);

        // Create a write-access API user for mobile app
        ApiUser::create([
            'name' => 'Mobile App API',
            'email' => 'mobile@natsave.co.zm',
            'api_key' => 'key_' . Str::random(32),
            'api_secret' => 'secret_' . Str::random(32),
            'is_active' => true,
            'access_level' => 'write',
            'daily_limit' => 5000,
            'rate_limit' => 60,
            'description' => 'API user for mobile app integration',
           'allowed_ips' => ['*'], // Allow all IPs
            'last_key_generated_at' => now(),
        ]);

        // Create a read-only API user for monitoring
        ApiUser::create([
            'name' => 'Monitoring API',
            'email' => 'monitoring@natsave.co.zm',
            'api_key' => 'key_' . Str::random(32),
            'api_secret' => 'secret_' . Str::random(32),
            'is_active' => true,
            'access_level' => 'read',
            'daily_limit' => 1000,
            'rate_limit' => 30,
            'description' => 'Read-only API user for monitoring purposes',
            'allowed_ips' => ['*'], // Allow all IPs
            'last_key_generated_at' => now(),
        ]);

        // Create a test API user
        ApiUser::create([
            'name' => 'Test API User',
            'email' => 'test@natsave.co.zm',
            'api_key' => 'test_key_' . Str::random(32),
            'api_secret' => 'test_secret_' . Str::random(32),
            'is_active' => true,
            'access_level' => 'write',
            'daily_limit' => 100,
            'rate_limit' => 10,
            'description' => 'Test API user for development',
            'allowed_ips' => ['*'], // Allow all IPs
            'last_key_generated_at' => now(),
        ]);

        // Create an inactive API user
        ApiUser::create([
            'name' => 'Inactive API User',
            'email' => 'inactive@natsave.co.zm',
            'api_key' => 'key_' . Str::random(32),
            'api_secret' => 'secret_' . Str::random(32),
            'is_active' => false,
            'access_level' => 'read',
            'daily_limit' => 1000,
            'rate_limit' => 60,
            'description' => 'Inactive API user for testing',
           'allowed_ips' => ['*'], // Allow all IPs
            'last_key_generated_at' => now(),
            'last_key_revoked_at' => now(),
        ]);

        // Log the credentials for the test user (in development only)
        if (app()->environment('local', 'development')) {
            $testUser = ApiUser::where('email', 'test@natsave.co.zm')->first();
            Log::info('Test API User Credentials:', [
                'api_key' => $testUser->api_key,
                'api_secret' => $testUser->api_secret
            ]);
        }
    }
}