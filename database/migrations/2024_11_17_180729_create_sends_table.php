<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sends', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('message_id')->unique()->nullable();
            $table->string('internal_message_id')->unique()->nullable();
            $table->string('source_addr', 20)->nullable();
            $table->text('destination_addr')->nullable();
            $table->text('message')->nullable();
            $table->enum('sms_type', ['single', 'bulk', 'group']);
            $table->string('message_type')->nullable();
            $table->integer('recipient_count')->default(1);
            $table->enum('status', ['pending', 'submitted', 'delivered', 'failed'])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('submitted_date')->nullable();
            $table->timestamp('done_date')->nullable();

            //SMPP FIELDS
            $table->string('service_type', 6)->nullable();
            $table->unsignedTinyInteger('data_coding')->nullable();
            $table->unsignedTinyInteger('registered_delivery')->nullable();
            $table->unsignedTinyInteger('priority_flag')->default(0);
            $table->unsignedTinyInteger('esm_class')->nullable();
            $table->unsignedTinyInteger('protocol_id')->nullable();

            // Optional relations and files
            $table->unsignedBigInteger('contact_group_id')->nullable();
            $table->string('excel_file')->nullable();

            // Store SMSC and error details
            $table->string('smsc_message_id')->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();

            // Add any additional tracking fields
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamps();

            $table->index(['message_id', 'status']);
            $table->index(['internal_message_id', 'status']);
            $table->index('submitted_date');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sends');
    }
};