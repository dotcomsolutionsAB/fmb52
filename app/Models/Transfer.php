<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    //
   
    protected $table = 't_transfers';

    protected $fillable = [
        'jamiat_id',
        'family_id',
        'date',
        'sector_from',
        'sector_to',
        'log_user',
        'status',
    ];
}

