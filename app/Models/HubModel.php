<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HubModel extends Model
{
    use HasFactory;

    protected $table = 't_hub';

    protected $fillable = [
        'jamiat_id', 'family_id', 'year', 'hub_amount', 'paid_amount', 'due_amount', 'log_user',
    ];

    // Define the relationship with the Year model
    public function get_year()
    {
        return $this->belongsTo(YearModel::class, 'year', 'year'); 
    }
}
