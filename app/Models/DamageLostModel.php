<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageLostModel extends Model
{
    //
    protected $table = 't_damage_lost';  // This is the table name in the database

    protected $fillable = [
        'jamiat_id', 'food_item_id', 'quantity', 'remarks', 'log_user'
    ];
}
