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
        Schema::create('t_advance_receipt', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->string('family_id', 10);
            $table->string('name', 100);
            $table->float('amount');
            $table->integer('sector');
            $table->integer('sub_sector');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_advance_receipt');
    }
};
