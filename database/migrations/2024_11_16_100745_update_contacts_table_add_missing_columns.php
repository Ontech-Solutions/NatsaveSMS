<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Add if status column doesn't exist
            if (!Schema::hasColumn('contacts', 'status')) {
                $table->string('status')->default('active');
            }

            // Add if job_title column doesn't exist
            if (!Schema::hasColumn('contacts', 'job_title')) {
                $table->string('job_title')->nullable();
            }

            // Add if notes column doesn't exist
            if (!Schema::hasColumn('contacts', 'notes')) {
                $table->text('notes')->nullable();
            }

            // Add if metadata column doesn't exist
            if (!Schema::hasColumn('contacts', 'metadata')) {
                $table->json('metadata')->nullable();
            }

            // Ensure name field exists and is properly configured
            if (!Schema::hasColumn('contacts', 'name')) {
                $table->string('name');
            }

            // Ensure department_id exists
            if (!Schema::hasColumn('contacts', 'department_id')) {
                $table->foreignId('department_id')->constrained();
            }

            // Ensure email is unique
            if (Schema::hasColumn('contacts', 'email')) {
                $table->string('email')->unique()->change();
            } else {
                $table->string('email')->unique()->nullable();
            }

            // Ensure phone field exists
            if (!Schema::hasColumn('contacts', 'phone')) {
                $table->string('phone', 20);
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Only drop the newly added columns
            $table->dropColumn([
                'status',
                'job_title',
                'notes',
                'metadata'
            ]);
        });
    }
};