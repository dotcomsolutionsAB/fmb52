<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodPurchaseItemsModel extends Model
{
    //
    protected $table = 't_food_purchase_items';  

    protected $fillable = [
        'jamiat_id', 'purchase_id', 'food_item_id', 'quantity', 'unit', 'rate', 'discount', 'tax'
    ];
}
