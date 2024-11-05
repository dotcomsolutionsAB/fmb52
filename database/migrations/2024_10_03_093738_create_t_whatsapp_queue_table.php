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
        Schema::create('t_whatsapp_queue', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('family_id');
            $table->string('group_id', 50)->nullable();
            $table->string('callback_url', 255)->nullable();
            $table->string('to', 20);
            $table->string('template_name', 100);
            // as it don't support `length`, it can store upto `65,535 characters for TEXT type in MySQL`
            $table->text('content')->nullable();
            $table->longText('json')->nullable();
            $table->text('response')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed']);
            $table->string('log_user', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_whatsapp_queue');
    }
};
