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
        Schema::create('t_building', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->string('name');
            $table->string('address_lime_1')->nullable();
            $table->string('address_lime_2')->nullable();
            $table->string('city')->nullable();
            $table->string('pincode')->nullable();
            $table->string('state')->nullable();
            $table->string('lattitude')->nullable();
            $table->string('longtitude')->nullable();
            $table->string('landmark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_building');
    }
};
