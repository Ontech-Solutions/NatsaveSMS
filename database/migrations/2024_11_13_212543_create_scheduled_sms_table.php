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
        Schema::create('scheduled_sms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('message_id')->unique();
            $table->string('source_addr', 20);
            $table->text('destination_addr');
            $table->text('message');
            $table->string('status')->default('scheduled');
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('priority_flag')->default(0);
            $table->string('schedule_type'); // once, daily, weekly, monthly
            $table->json('schedule_data'); // frequency details
            $table->timestamp('next_run_at');
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedTinyInteger('esm_class')->nullable();
            $table->unsignedTinyInteger('protocol_id')->nullable();
            $table->unsignedTinyInteger('data_coding')->nullable();
            $table->unsignedTinyInteger('registered_delivery')->nullable();
            $table->string('service_type', 6)->nullable();
            $table->string('message_type')->default('scheduled');
            $table->integer('recipient_count')->default(1);
            $table->timestamps();

            $table->index(['status', 'next_run_at']);
            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_sms');
    }
};
