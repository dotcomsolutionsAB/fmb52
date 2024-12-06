<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KolkataMumineen extends Model
{
    use HasFactory;

    protected $table = 'kolkata_mumineen';

    protected $fillable = [
        'year', 'event', 'type', 'family_id', 'title', 'name', 'its', 'hof_id',
        'family_its_id', 'mobile', 'email', 'address', 'building', 'delivery_person',
        'gender', 'dob', 'folio_no', 'hub', 'zabihat', 'prev_tanzeem', 'sector',
        'sub_sector', 'is_taking_thali', 'status', 'log_user', 'log_date'
    ];
}