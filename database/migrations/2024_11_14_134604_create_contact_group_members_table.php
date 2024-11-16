<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contact_group_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_group_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->timestamps();
            
            $table->unique(['contact_group_id', 'contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_group_members');
    }
};
