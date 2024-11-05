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
        Schema::create('t_vendors', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->integer('vendor_id');
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('group');
            $table->string('mobile');
            $table->string('email')->nullable();
            $table->string('pan_card')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->integer('pincode')->nullable();
            $table->string('state')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('vpa')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_vendors');
    }
};
