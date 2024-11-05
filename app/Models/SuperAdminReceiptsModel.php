<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuperAdminReceiptsModel extends Model
{
    //
    protected $table = 't_super_admin_receipts';

    protected $fillable = [
        'jamiat_id', 'amount', 'package', 'payment_date', 'receipt_number', 'created_by', 'notes'
    ];
}
