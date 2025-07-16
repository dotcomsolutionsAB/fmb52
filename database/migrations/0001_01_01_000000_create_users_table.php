<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Auth & identity
            $table->string('username')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Personal Info
            $table->string('title')->nullable();
            $table->string('its')->nullable();
            $table->string('hof_its')->nullable();
            $table->string('its_family_id')->nullable();
            $table->string('family_id')->nullable()->index();
            $table->string('mobile')->nullable();
            $table->text('address')->nullable();
            $table->string('building')->nullable();
            $table->string('flat_no')->nullable();
            $table->decimal('lattitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->integer('age')->nullable();

            // Jamiat/Thali/Mumeneen Details
            $table->string('jamiat_id')->nullable();
            $table->string('folio_no')->nullable();
            $table->enum('thali_status', ['Active', 'Inactive'])->default('Active');
            $table->enum('mumeneen_type', ['Regular', 'Guest'])->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->string('role')->nullable();
            $table->unsignedBigInteger('access_role_id')->nullable();

            // Access control fields
            $table->string('user_access_id')->nullable();
            $table->unsignedBigInteger('sector_access_id')->nullable();
            $table->unsignedBigInteger('sub_sector_access_id')->nullable();
            $table->unsignedBigInteger('sector_id')->nullable();
            $table->unsignedBigInteger('sub_sector_id')->nullable();

            // Media
            $table->unsignedBigInteger('photo_id')->nullable();

            // Laravel-specific
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};