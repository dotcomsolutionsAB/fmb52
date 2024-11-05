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
        Schema::create('t_receipts', function (Blueprint $table) {
            $table->id();
            $table->integer('jamiat_id');
            $table->string('family_id', 10);
            $table->string('receipt_no', 100);
            $table->date('date');
            $table->string('its', 8);
            $table->string('folio_no', 20)->nullable();
            $table->string('name', 100);
            $table->string('sector', 100)->nullable();
            $table->string('sub_sector', 100)->nullable();
            $table->float('amount');
            $table->enum('mode', ['cheque', 'cash', 'neft', 'upi']);
            $table->string('bank_name', 100)->nullable();
            $table->string('cheque_no', 50)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('ifsc_code', 11)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('year', 10);
            $table->text('comments')->nullable();
            $table->enum('status', ['pending', 'cancelled', 'paid']);
            $table->text('cancellation_reason')->nullable();
            $table->string('collected_by', 100)->nullable();
            $table->string('log_user', 100);
            $table->integer('attachment')->nullable();
            $table->integer('payment_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_receipts');
    }
};
