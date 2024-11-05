<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JamiatSettingsModel extends Model
{
    //
    protected $table = 't_jamiat_settings';

    protected $fillable = [
        'jamiat_id', 'name', 'value'
    ];
}
