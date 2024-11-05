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
        Schema::create('t_fcm', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('user_id');
            // as it don't support `length`, it can store upto `65,535 characters for TEXT type in MySQL`
            $table->text('fcm_token');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_fcm');
    }
};
