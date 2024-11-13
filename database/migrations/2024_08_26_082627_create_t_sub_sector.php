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
        Schema::create('t_sub_sector', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->string('sector',100);
            $table->string('name', 100);
            // as it don't support `length`, it can store upto `65,535 characters for TEXT type in MySQL`
            $table->text('notes')->nullable();
            $table->string('log_user', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_sub_sector');
    }
};
