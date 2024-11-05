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
        Schema::create('t_menu', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('family_id')->nullable();
            $table->date('date');
            $table->string('menu');
            $table->string('addons');
            $table->string('niaz_by');
            $table->string('year');
            $table->string('slip_names');
            $table->enum('category', ['chicken', 'mutton', 'veg', 'dal', 'zabihat']);
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_menu');
    }
};
