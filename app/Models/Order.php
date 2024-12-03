<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'jamiat_id',
        'thaali_count',
        'payment_date',
        'razorpay_order_id',
        'amount',
        'currency',
        'status',
    ];
}