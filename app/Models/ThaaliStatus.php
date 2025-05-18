<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThaaliStatus extends Model
{
    protected $table = 't_thaali_status';

    protected $fillable = [
        'jamiat_id', 'name', 'slug','created_at','updated_at',
    ];
}