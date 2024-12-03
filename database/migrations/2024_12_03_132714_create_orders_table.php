<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('jamiat_id'); // Foreign key or related to another table
            $table->integer('thaali_count'); // Number of thaalis
            $table->date('payment_date')->nullable(); // Payment date
            
            // Razorpay related columns
            $table->string('razorpay_order_id')->unique(); // Razorpay Order ID
            $table->string('razorpay_payment_id')->nullable(); // Razorpay Payment ID
            $table->string('razorpay_signature')->nullable(); // Razorpay signature for verification
            $table->decimal('amount', 10, 2); // Payment amount
            $table->string('currency', 3)->default('INR'); // Currency
            $table->string('status')->default('pending'); // Payment status (e.g., pending, paid, failed)

            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};