<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_group_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Prevent duplicate assignments
            $table->unique(['contact_group_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_group_contact');
    }
};