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
        Schema::create('sms_inboxes', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->string('sender');
            $table->string('recipient');
            $table->text('message');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_inboxes');
    }
};
