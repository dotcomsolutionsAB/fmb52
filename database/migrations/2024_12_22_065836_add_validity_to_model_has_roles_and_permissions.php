<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValidityToModelHasRolesAndPermissions extends Migration
{
    public function up()
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
        });
    }

    public function down()
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropColumn(['valid_from', 'valid_to']);
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropColumn(['valid_from', 'valid_to']);
        });
    }
}