<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodSaleModel extends Model
{
    //
    protected $table = 't_food_sale';  // This is the table name in the database

    protected $fillable = [
        'jamiat_id', 'name', 'menu', 'family_id', 'date', 'thaal_count', 'total', 'log_user'
    ];
}
