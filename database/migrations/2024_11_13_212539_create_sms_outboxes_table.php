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
        Schema::create('sms_outboxes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('message_id')->unique();
            $table->string('source_addr', 20);
            $table->text('destination_addr');
            $table->text('message');
            $table->enum('message_type', ['single', 'bulk', 'group']);
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
            $table->index('submitted_date');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_outboxes');
    }
};
