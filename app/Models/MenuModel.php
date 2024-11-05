<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuModel extends Model
{
    //
    protected $table = 't_menu';

    protected $fillable = [
        'jamiat_id', 'family_id', 'date', 'menu', 'addons', 'niaz_by', 'year', 'slip_names', 'category', 'status',
    ];
}
