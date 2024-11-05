<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItsModel extends Model
{
    //
    protected $table = 't_its_data';

    protected $fillable = [
        'jamiat_id', 'hof_its', 'its_family_id', 'name', 'email', 'mobile', 'title', 'mumeneen_type', 'gender', 'age', 'sector', 'sub_sector', 'name_arabic'
    ];
}
