<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_groups', function (Blueprint $table) {
            // Add is_active column if it doesn't exist
            if (!Schema::hasColumn('contact_groups', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('contact_groups', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};