<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTFeedbacksTable extends Migration
{
   public function up()
{
   Schema::create('import_jobs', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');       // user who started import
    $table->unsignedBigInteger('jamiat_id');     // add jamiat_id here
    $table->string('file_path');
    $table->string('status')->default('pending');
    $table->text('message')->nullable();
    $table->timestamps();

    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    // add foreign if you have jamiats table, else just store as integer
});
}

public function down()
{
    Schema::dropIfExists('import_jobs');
}
}