<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTThaliStatusTable extends Migration
{
    public function up()
    {
        Schema::create('t_thaali_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jamiat_id');
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('t_thaali_status');
    }
}