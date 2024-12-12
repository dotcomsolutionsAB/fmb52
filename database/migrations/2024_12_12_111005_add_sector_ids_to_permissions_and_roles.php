<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSectorIdsToPermissionsAndRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add columns to model_has_roles
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->string('sector_ids')->nullable()->after('model_id'); // Comma-separated sector IDs
            $table->string('sub_sector_ids')->nullable()->after('sector_ids'); // Comma-separated sub-sector IDs
        });

        // Add columns to model_has_permissions
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->string('sector_ids')->nullable()->after('model_id'); // Comma-separated sector IDs
            $table->string('sub_sector_ids')->nullable()->after('sector_ids'); // Comma-separated sub-sector IDs
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop columns from model_has_roles
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropColumn(['sector_ids', 'sub_sector_ids']);
        });

        // Drop columns from model_has_permissions
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropColumn(['sector_ids', 'sub_sector_ids']);
        });
    }
}