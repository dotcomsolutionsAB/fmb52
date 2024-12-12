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
        Schema::create('t_hub', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->string('family_id', 10);
            $table->string('year', 10);
            $table->float('hub_amount');
            $table->float('paid_amount')->nullable();
            $table->float('due_amount')->nullable();
            $table->string('log_user', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_hub');
    }
};
