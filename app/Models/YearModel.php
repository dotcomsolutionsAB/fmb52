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

    // Define a hasMany relationship with the Hub model
    public function hubs()
    {
        return $this->hasMany(HubModel::class, 'year', 'year');
    }
}
