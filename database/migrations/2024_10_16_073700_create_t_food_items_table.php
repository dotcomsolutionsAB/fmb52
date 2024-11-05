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
        Schema::create('t_food_items', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('food_item_id')->unique();//it'll take 10digis random unique digits
            $table->string('name');
            $table->string('category')->nullable();
            $table->enum('unit', ['kg', 'ltr', 'gm', 'pckt', 'box', 'bottles', 'nos', 'pcs', 'bags']);
            $table->integer('rate')->default(0);
            $table->string('hsn')->nullable();
            $table->float('tax')->nullable();
            $table->string('log_user');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_food_items');
    }
};
