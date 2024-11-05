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
        Schema::create('t_transfers', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('family_id');
            $table->date('date');
            $table->integer('sector_from');
            $table->integer('sector_to');
            $table->string('log_user');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_transfers');
    }
};
