<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentsModel extends Model
{
    //
    protected $table = 't_payments';  // Replace with your actual table name

    protected $fillable = [
        'payment_no', 'jamiat_id', 'family_id', 'folio_no', 'name', 'its', 'sector', 'sub_sector',
        'year', 'mode', 'date', 'bank_name', 'cheque_no', 'cheque_date', 'ifsc_code', 'transaction_id',
        'transaction_date', 'amount', 'comments', 'status', 'cancellation_reason', 'log_user', 'attachment',
    ];
}
