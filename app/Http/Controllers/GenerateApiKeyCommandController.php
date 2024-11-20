<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiKeyCommand extends Command
{
    protected $signature = 'make:api-key {email}';
    protected $description = 'Generate API credentials for a user';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        $apiKey = 'key_' . Str::random(32);
        $apiSecret = 'secret_' . Str::random(32);

        $user->update([
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
        ]);

        $this->info('API Credentials generated successfully:');
        $this->line('API Key: ' . $apiKey);
        $this->line('API Secret: ' . $apiSecret);
        $this->warn('Please save these credentials securely. The secret will not be shown again!');

        return 0;
    }
}