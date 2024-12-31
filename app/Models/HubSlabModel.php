<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HubSlabModel extends Model
{
    use HasFactory;

    protected $table = 't_hub_slab';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'jamiat_id',
        'name',
        'amount',
    ];

    /**
     * Define a relationship to the Jamiat model if applicable.
     */
    public function jamiat()
    {
        return $this->belongsTo(JamiatModel::class, 'jamiat_id', 'id');
    }
}