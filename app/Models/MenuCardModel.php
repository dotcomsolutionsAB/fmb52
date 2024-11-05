<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCardModel extends Model
{
    //
    protected $table = 't_menu_card';

    protected $fillable = [
        'jamiat_id', 'name', 'dish'
    ];
}
