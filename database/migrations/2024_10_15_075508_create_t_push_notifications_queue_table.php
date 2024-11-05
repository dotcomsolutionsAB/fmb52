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
        Schema::create('t_push_notifications_queue', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('family_id');
            $table->string('title');
            $table->string('message');
            $table->string('icon')->nullable();
            $table->string('callback')->nullable();
            $table->string('status');
            $table->string('response');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_push_notifications_queue');
    }
};
