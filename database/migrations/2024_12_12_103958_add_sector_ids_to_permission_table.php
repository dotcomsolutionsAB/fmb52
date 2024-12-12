<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSectorIdsToPermissionTables extends Migration
{
    public function up()
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->string('sector_ids')->nullable()->after('model_id'); // Comma-separated IDs
            $table->string('sub_sector_ids')->nullable()->after('sector_ids'); // Comma-separated IDs
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->string('sector_ids')->nullable()->after('model_id'); // Comma-separated IDs
            $table->string('sub_sector_ids')->nullable()->after('sector_ids'); // Comma-separated IDs
        });
    }

    public function down()
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropColumn(['sector_ids', 'sub_sector_ids']);
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropColumn(['sector_ids', 'sub_sector_ids']);
        });
    }
}
