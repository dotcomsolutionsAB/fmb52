<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearModel extends Model
{
    use HasFactory;

    protected $table = 't_year';

    protected $fillable = [
        'year', 'jamiat_id', 'is_current',
    ];

    // Define the relationship with the Hub model
    public function get_year()
    {
        return $this->belongsTo(HubModel::class, 'year', 'year'); 
    }
}
