<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodPurchaseModel extends Model
{
    //
    protected $table = 't_food_purchase';  // This is the name of the table

    protected $fillable = [
        'jamiat_id', 'vendor_id', 'invoice_no', 'date', 'remarks', 'attachment', 'total', 'log_user'
    ];
}
