<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodSaleItemsModel extends Model
{
    //
    protected $table = 't_food_sale_items';  // This is the table name in the database

    protected $fillable = [
        'jamiat_id', 'sale_id', 'food_item_id', 'quantity', 'unit', 'rate'
    ];
}
