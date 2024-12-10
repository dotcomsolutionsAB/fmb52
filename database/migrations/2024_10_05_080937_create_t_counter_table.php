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
        Schema::create('t_counter', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('sector')->nullable();
            $table->string('type', 50);
            $table->string('year', 10);
            $table->string('prefix', 10);
            $table->string('postfix', 10);
            $table->integer('value');
            $table->timestamps();  
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_counter');
    }
};
