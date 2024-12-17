<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\NiyazModel;
use App\Models\User;

class EventsModel extends Model
{
    protected $table = 'events';

    protected $fillable = [
        'type',
        'family_id',
        'type_id',
        'remarks',
        'amount',
        'menu',
        'total_amount',
        'amount_due',
        'amount_paid',
        'date',
        'logged_user',
    ];

    // Relationship with User table
    public function user()
    {
        return $this->belongsTo(User::class, 'family_id', 'family_id');
    }

    // Conditional relationship with TNiyaz table
    public function niyaz()
    {
        return $this->belongsTo(NiyazModel::class, 'type_id');
    }
}