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
        Schema::create('t_events', function (Blueprint $table) {
            $table->bigIncrements('id'); // Auto-increment primary key
            $table->enum('type', ['Fateha', 'Salawat', 'Niyaz'])->default('Fateha'); // Event Type
            $table->string('family_id', 50); // Family ID
            $table->unsignedBigInteger('type_id')->nullable(); // Foreign key to other tables (conditionally used)
            $table->string('remarks')->nullable(); // Remarks
            $table->double('amount', 10, 2)->default(0.00); // Total Amount
            $table->string('menu')->nullable(); // Menu
            $table->double('total_amount', 10, 2)->default(0.00); // Amount due
            $table->double('amount_paid', 10, 2)->default(0.00); // Amount Paid
            $table->double('amount_due', 10, 2)->default(0.00); // Amount due
            $table->date('date'); // Event Date
            $table->string('logged_user', 100); // Logged User
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('family_id')->references('family_id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('type_id')->references('id')->on('t_niyaz')
                ->onDelete('set null') // If Niyaz is deleted, set null
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_events');
    }
};