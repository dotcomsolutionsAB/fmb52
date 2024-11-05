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
        Schema::create('t_dish_items', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('dish_id');
            $table->integer('food_item_id');
            $table->integer('quantity');
            $table->float('unit');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_dish_items');
    }
};
