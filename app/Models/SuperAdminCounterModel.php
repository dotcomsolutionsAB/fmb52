<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuperAdminCounterModel extends Model
{
    //
    protected $table = 't_super_admin_counter';

    protected $fillable = [
        'key', 'value'
    ];
}
