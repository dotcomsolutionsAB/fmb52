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
        Schema::create('t_food_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('purchase_id');
            $table->integer('food_item_id');
            $table->integer('quantity');
            $table->integer('unit');
            $table->float('rate');
            $table->float('discount');
            $table->float('tax');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_food_purchase_items');
    }
};
