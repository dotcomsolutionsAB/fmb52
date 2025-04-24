<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    // The table associated with the model
    protected $table = 't_feedbacks';

    // The attributes that are mass assignable
    protected $fillable = [
        'jamiat_id',
        'menu_id',
        'date',
        'user_id',
        'family_id',
        'food_taste',
        'food_quantity',
        'food_quality',
        'others',
        'remarks',
        'images',
    ];

    // Enable timestamps
    public $timestamps = true;
}