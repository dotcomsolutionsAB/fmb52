<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyModel extends Model
{
    use HasFactory;

    // Define the table name if it's not the default "currencies"
    protected $table = 'currencies';

    // Specify the fields that can be mass assigned
    protected $fillable = [
        'country_name',
        'currency_name',
        'currency_code',
        'currency_symbol',
        'created_at',
        'updated_at',
    ];

    public function jamiats()
    {
        return $this->hasMany(JamiatModel::class, 'currency_id');
    }
}