<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseModel extends Model
{
    //
    protected $table = 't_expense';  // Replace this with your actual table name

    protected $fillable = [
        'jamiat_id', 'voucher_no', 'year', 'name', 'date', 'cheque_no', 'description', 'log_user', 'attachment',
    ];
}
