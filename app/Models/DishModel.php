<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DishModel extends Model
{
    //
    protected $table = 't_dish';

    protected $fillable = [
        'jamiat_id', 'name', 'log_user'
    ];
}
