<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMinimumCountToTHubSlabTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('t_hub_slab', function (Blueprint $table) {
            $table->integer('minimum_count')->default(1)->after('amount'); // Add 'minimum_count' column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('t_hub_slab', function (Blueprint $table) {
            $table->dropColumn('minimum_count'); // Remove the column if rolled back
        });
    }
}
