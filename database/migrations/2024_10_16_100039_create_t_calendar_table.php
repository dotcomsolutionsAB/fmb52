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
        Schema::create('t_calendar', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->string('family_id');
            $table->string('year');
            $table->date('date');
            $table->string('type');
            // as it don't support `length`, it can store upto `65,535 characters for TEXT type in MySQL`
            $table->text('description');
            $table->string('log_user', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_calendar');
    }
};
