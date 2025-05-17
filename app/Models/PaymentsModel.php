<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentsModel extends Model
{
    //
    protected $table = 't_payments';  // Replace with your actual table name

    protected $fillable = [
        'payment_no', 'jamiat_id', 'family_id', 'name', 'its', 'sector', 'sub_sector',
        'year', 'mode', 'date','sector_id','sub_sector_id', 'amount', 'comments', 'status', 'cancellation_reason', 'log_user', 'attachment',
    ];
   
    
}
