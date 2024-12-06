<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKolkataMumineenTable extends Migration
{
    public function up()
    {
        Schema::create('kolkata_mumineen', function (Blueprint $table) {
            $table->id();
            $table->string('year');
            $table->string('event');
            $table->string('type');
            $table->bigInteger('family_id');
            $table->string('title')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('its')->nullable();
            $table->string('hof_id')->nullable();
            $table->string('family_its_id')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('building')->nullable();
            $table->string('delivery_person')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob');
            $table->string('folio_no')->nullable();
            $table->string('hub')->nullable();
            $table->integer('zabihat')->nullable();
            $table->string('prev_tanzeem')->nullable();
            $table->string('sector')->nullable();
            $table->string('sub_sector')->nullable();
            $table->boolean('is_taking_thali');
            $table->boolean('status');
            $table->string('log_user');
            $table->dateTime('log_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kolkata_mumineen');
    }
}