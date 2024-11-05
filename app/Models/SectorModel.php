<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectorModel extends Model
{
    use HasFactory;

    protected $table = 't_sector';

    protected $fillable = [
        'jamiat_id', 'name', 'notes', 'log_user',
    ];

    // Define the relationship with the SubSector model
    public function get_subSectors()
    {
        return $this->hasMany(SubSectorModel::class, 'sector', 'name');
    }
}
