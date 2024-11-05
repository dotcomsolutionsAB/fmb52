<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodItemsModel extends Model
{
    //
    protected $table = 't_food_items';  // This is the name of the table

    protected $fillable = [
        'jamiat_id', 'food_item_id', 'name', 'category', 'unit', 'rate', 'hsn', 'tax', 'log_user'
    ];
}
