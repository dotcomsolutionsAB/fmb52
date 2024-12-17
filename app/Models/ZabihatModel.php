<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZabihatModel extends Model
{
    //
    protected $table = 't_zabihat';  // Replace this with your actual table name

    protected $fillable = [
        'jamiat_id', 'family_id', 'year', 'zabihat_count', 'hub_amount', 'paid_amount', 'due_amount', 'log_user','zabihat_left',
    ];
}
