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
        Schema::create('t_damage_lost', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('food_item_id');
            $table->integer('quantity');
            // as it don't support `length`, it can store upto `65,535 characters for TEXT type in MySQL`
            $table->text('remarks');
            $table->string('log_user');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_damage_lost');
    }
};
