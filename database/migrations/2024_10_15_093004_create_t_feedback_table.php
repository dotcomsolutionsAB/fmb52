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
        Schema::create('t_feedback', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('family_id');
            $table->date('date');
            $table->string('subject');
            $table->string('message');
            $table->integer('ratings');
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_feedback');
    }
};
