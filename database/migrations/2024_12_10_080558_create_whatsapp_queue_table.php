<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappQueueTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_queue', function (Blueprint $table) {
            $table->id();
            $table->string('group_id', 100)->default('');
            $table->string('callback_data', 256)->default('');
            $table->string('recipient_type', 256)->default('individual');
            $table->string('to', 100);
            $table->string('type', 100);
            $table->string('file_url', 1000)->nullable();
            $table->longText('content')->default('');
            $table->integer('status')->default(0);
            $table->longText('response')->default('');
            $table->string('msg_id', 1000)->nullable();
            $table->string('msg_status', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_queue');
    }
}