<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->string('email')->nullable()->default(null)->change();
            $table->integer('jamiat_id')->after('email');
            $table->string('family_id', 10)->after('jamiat_id');
            $table->string('mobile', 20)->after('family_id');
            $table->string('its', 8)->nullable()->unique()->after('mobile');
            $table->string('hof_its', 8)->after('its')->nullable();
            $table->string('its_family_id', 10)->after('hof_its')->nullable();
            $table->string('folio_no', 20)->after('its_family_id')->nullable();
            $table->enum('mumeneen_type', ['HOF', 'FM'])->after('folio_no')->nullable();
            $table->enum('title', ['Shaikh', 'Mulla'])->after('mumeneen_type')->nullable();
            $table->enum('gender', ['male', 'female'])->after('title')->nullable();
            $table->integer('age')->after('gender')->nullable();
            $table->integer('building')->after('age')->nullable();
            $table->enum('status', ['active', 'in_active'])->after('sub_sector_id');
            $table->enum('role', ['superadmin', 'jamiat_admin', 'mumeneen'])->after('status');
            $table->integer('otp')->after('role')->nullable();
            $table->timestamp('expires_at')->after('otp')->nullable();
            $table->string('username')->unique()->after('password');
            $table->enum('thali_status', ['taking', 'not_taking', 'once_a_week', 'joint'])->after('username')->nullable();
            $table->longText('joint_with')->after('thali_status')->nullable();
            $table->longText('photo_id')->after('joint_with')->nullable();
             $table->unsignedBigInteger('access_role_id')->nullable()->after('photo_id');
            $table->unsignedBigInteger('sector_id')->nullable()->after('folio_no');
            $table->unsignedBigInteger('sub_sector_id')->nullable()->after('sector_id');
            $table->unsignedBigInteger('sector_access_id')->nullable();
            $table->unsignedBigInteger('sub_sector_access_id')->nullable();
            $table->string('user_access_id')->nullable();
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
