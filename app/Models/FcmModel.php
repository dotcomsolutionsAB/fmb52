<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FcmModel extends Model
{
    //
    protected $table = 't_fcm'; // Replace this with your actual table name

    protected $fillable = [
        'jamiat_id', 'user_id', 'fcm_token', 'status',
    ];
}
