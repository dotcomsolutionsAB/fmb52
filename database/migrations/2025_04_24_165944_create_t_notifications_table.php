<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_notifications', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('jamiat_id'); // Foreign key for Jamiat
            $table->string('title'); // Title of the notification
            $table->text('msg'); // Message body
            $table->string('image')->nullable(); // Image URL or path (nullable)
            $table->enum('type', ['image', 'text', 'mixed']); // Type of notification
            $table->string('created_by'); // String to store the name of the user who created the notification
            $table->timestamps(); // Created at and updated at timestamps

            // Optional: You can add a foreign key constraint for jamiat_id if needed
            // $table->foreign('jamiat_id')->references('id')->on('jamiats')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_notifications');
    }
}