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
        Schema::create('t_food_sale', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->string('name');
            $table->string('menu');
            $table->integer('family_id')->nullable();
            $table->date('date');
            $table->integer('thaal_count');
            $table->integer('total');
            $table->string('log_user');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_food_sale');
    }
};
