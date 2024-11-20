<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_users', function (Blueprint $table) {
            $table->id();
            // Basic Information
            $table->string('name');
            $table->string('email')->unique();
            $table->text('description')->nullable();

            // API Authentication
            $table->string('api_key')->unique()->nullable();
            $table->string('api_secret')->nullable();
            
            // Access Control
            $table->boolean('is_active')->default(true);
            $table->enum('access_level', ['read', 'write', 'admin'])->default('read');
            $table->json('allowed_ips')->nullable();
            
            // Usage Limits & Monitoring
            $table->integer('daily_limit')->default(1000);
            $table->integer('rate_limit')->default(60); // Requests per minute
            $table->integer('usage_count')->default(0);
            
            // Timestamps
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_key_generated_at')->nullable();
            $table->timestamp('last_key_revoked_at')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['api_key', 'is_active']);
            $table->index('email');
            $table->index('access_level');
            $table->index('usage_count');
            $table->index('last_used_at');
            $table->index(['daily_limit', 'usage_count']); // For limit checking
            $table->index(['is_active', 'last_used_at']); // For active user queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_users');
    }

    /**
     * Add custom SQL commands after migration
     */
    public function afterUp(): void
    {
        // Add comment to the table for better documentation
        DB::statement('ALTER TABLE api_users COMMENT = "Stores API users and their authentication credentials"');
        
        // Add check constraints where supported
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE api_users ADD CONSTRAINT chk_daily_limit CHECK (daily_limit >= 0)');
            DB::statement('ALTER TABLE api_users ADD CONSTRAINT chk_rate_limit CHECK (rate_limit >= 0)');
            DB::statement('ALTER TABLE api_users ADD CONSTRAINT chk_usage_count CHECK (usage_count >= 0)');
        }
    }
};