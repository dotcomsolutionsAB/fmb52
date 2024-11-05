<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounterModel extends Model
{
    //
    protected $table = 't_counter';  // Replace this with your actual table name

    protected $fillable = [
        'jamiat_id', 'sector', 'type', 'year', 'value',
    ];
}
