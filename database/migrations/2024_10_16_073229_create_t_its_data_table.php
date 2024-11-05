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
        Schema::create('t_its_data', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('hof_its');
            $table->integer('its_family_id');
            $table->string('name');
            $table->string('email');
            $table->string('mobile');
            $table->string('title');
            $table->enum('mumeneen_type', ['HOF', 'FM']);
            $table->enum('gender', ['male', 'female']);
            $table->integer('age');
            $table->string('sector');
            $table->string('sub_sector');
            // as it don't support `length`, it can store upto `65,535 characters for TEXT type in MySQL`
            $table->text('name_arabic');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_its_data');
    }
};
