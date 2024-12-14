<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccessColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add sector_access_id and sub_sector_access_id columns
            $table->json('sector_access_id')->nullable()->after('remember_token');
            $table->json('sub_sector_access_id')->nullable()->after('sector_access_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the columns
            $table->dropColumn('sector_access_id');
            $table->dropColumn('sub_sector_access_id');
        });
    }
}
