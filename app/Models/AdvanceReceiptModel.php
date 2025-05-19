<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvanceReceiptModel extends Model
{
    //
    protected $table = 't_advance_receipt'; 

    protected $fillable = [
        'jamiat_id', 'family_id', 'name', 'amount', 'sector_id', 'sub_sector_id',
    ];
}
