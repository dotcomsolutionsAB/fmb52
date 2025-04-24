<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTFeedbacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_feedbacks', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('jamiat_id'); // Foreign key for Jamiat
            $table->unsignedBigInteger('menu_id'); // Foreign key for Menu
            $table->date('date'); // Date of feedback submission
            $table->unsignedBigInteger('user_id'); // Foreign key for User
            $table->unsignedBigInteger('family_id'); // Foreign key for Family
            $table->integer('food_taste')->default(0)->comment('Rating for food taste (1-10)'); // Rating for food taste
            $table->integer('food_quantity')->default(0)->comment('Rating for food quantity (1-10)'); // Rating for food quantity
            $table->integer('food_quality')->default(0)->comment('Rating for food quality (1-10)'); // Rating for food quality
            $table->text('others')->nullable(); // Any other feedback
            $table->text('remarks')->nullable(); // Additional remarks
            $table->string('images')->nullable(); // URL or path to uploaded images (nullable)
            $table->timestamps(); // Created at and updated at timestamps
            
            // You can add foreign key constraints if necessary:
            // $table->foreign('jamiat_id')->references('id')->on('jamiats')->onDelete('cascade');
            // $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('family_id')->references('id')->on('families')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_feedbacks');
    }
}