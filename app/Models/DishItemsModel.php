<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DishItemsModel extends Model
{
    //
    protected $table = 't_dish_items';

    protected $fillable = [
        'jamiat_id', 'dish_id', 'food_item_id', 'quantity', 'unit'
    ];
}
