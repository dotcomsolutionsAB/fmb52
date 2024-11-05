<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptsModel extends Model
{
    //
    protected $table = 't_receipts';  // Replace with your actual table name

    protected $fillable = [
        'jamiat_id', 'family_id', 'receipt_no', 'date', 'its', 'folio_no', 'name',
        'sector', 'sub_sector', 'amount', 'mode', 'bank_name', 'cheque_no', 'cheque_date', 
        'ifsc_code', 'transaction_id', 'transaction_date', 'year', 'comments', 'status',
        'cancellation_reason', 'collected_by', 'log_user', 'attachment', 'payment_id',
    ];
}
