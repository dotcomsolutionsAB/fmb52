<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTHubSlabTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_hub_slab', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jamiat_id');
            $table->string('name', 255);
            $table->float('amount', 8, 2);
            $table->timestamps();

            // Add foreign key constraint if `jamiat_id` references another table
            // $table->foreign('jamiat_id')->references('id')->on('t_jamiat')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_hub_slab');
    }
}