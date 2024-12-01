<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->json('parameters')->nullable();
            $table->json('data')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('pending')->nullable();
            $table->boolean('include_charts')->default(true)->nullable();
            $table->string('file_format')->default('pdf')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
};