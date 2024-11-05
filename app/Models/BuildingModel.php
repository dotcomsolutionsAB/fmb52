<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuildingModel extends Model
{
    //
    protected $table = 't_building'; // Replace with your actual table name

    protected $fillable = [
        'jamiat_id', 'name', 'address_lime_1', 'address_lime_2', 'city', 'pincode', 'state', 'lattitude', 'longtitude', 'landmark',
    ];
}
