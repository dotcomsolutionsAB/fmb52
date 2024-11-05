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
        Schema::create('t_expense', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('voucher_no');
            $table->string('year', 10);
            $table->string('name');
            $table->date('date');
            $table->string('cheque_no')->nullable();
            $table->string('description')->nullable();
            $table->string('log_user');
            $table->string('attachment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_expense');
    }
};
