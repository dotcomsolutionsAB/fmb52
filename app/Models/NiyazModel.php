<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class NiyazModel extends Model
{
    protected $table = 't_niyaz';

    protected $fillable = [
        'niyaz_id',
        'family_id',
        'date',
        'menu',
        'fateha',
        'comments',
        'type',
        'total_amount',
        'amount_due',
        'amount_paid',
    ];

    // Relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class, 'family_id', 'family_id');
    }
}