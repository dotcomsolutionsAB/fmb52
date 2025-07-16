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
        Schema::create('t_niyaz', function (Blueprint $table) {
            $table->bigIncrements('id'); // Auto-increment primary key
            $table->string('niyaz_id', 50); // Niyaz ID
            $table->string('family_id', 10); // Family ID
            $table->date('date'); // Date
            $table->string('menu')->nullable(); // Menu
            $table->string('fateha')->nullable(); // Fateha
            $table->text('comments')->nullable(); // Comments
            $table->enum('type', ['Regular', 'Special'])->default('Regular'); // Type (enum)
            $table->double('total_amount', 10, 2)->default(0.00); // Amount due
            $table->double('amount_due', 10, 2)->default(0.00); // Amount due
            $table->double('amount_paid', 10, 2)->default(0.00); // Amount paid
            $table->timestamps(); // created_at and updated_at

            // Foreign key relation
            $table->foreign('family_id')->references('family_id')->on('users')
                ->onDelete('cascade') // Deletes t_niyaz records if a user is deleted
                ->onUpdate('cascade'); // Updates t_niyaz if user family_id changes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_niyaz');
    }
};
