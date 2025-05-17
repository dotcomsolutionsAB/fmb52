<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptsModel extends Model
{
    //
    public function user()
    {
        return $this->belongsTo(User::class, 'its', 'its');
    }
    
    protected $table = 't_receipts';  // Replace with your actual table name

    protected $fillable = [
        'jamiat_id', 'family_id', 'receipt_no', 'date', 'its', 'folio_no', 'name',
        'sector_id', 'sub_sector_id', 'amount', 'mode', 'bank_name', 'cheque_no', 'cheque_date', 
        'ifsc_code', 'transaction_id', 'transaction_date', 'year', 'comments', 'status',
        'cancellation_reason', 'collected_by', 'log_user', 'attachment', 'payment_id',
    ];
    public function sector()
    {
        return $this->belongsTo(SectorModel::class, 'sector_id');
    }

    public function subSector()
    {
        return $this->belongsTo(SubSectorModel::class, 'sub_sector_id');
    }
}
