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
        Schema::create('t_food_purchase', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('vendor_id');
            $table->string('invoice_no');
            $table->date('date');
            // as it don't support `length`, it can store upto `65,535 characters for TEXT type in MySQL`
            $table->text('remarks');
            $table->string('attachment');
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
        Schema::dropIfExists('t_food_purchase');
    }
};
