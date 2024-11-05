<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubSectorModel extends Model
{
    use HasFactory;

    protected $table = 't_sub_sector';

    protected $fillable = [
        'jamiat_id', 'sector', 'name', 'notes', 'log_user',
    ];

    // Define the relationship with the Sector model
    public function get_sector()
    {
        return $this->belongsTo(SectorModel::class, 'sector', 'name');
    }
}
