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
        Schema::create('t_feedback_response', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('family_id');
            $table->integer('feedback_id');
            $table->string('name');
            $table->date('date');
            $table->string('message');
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_feedback_response');
    }
};
