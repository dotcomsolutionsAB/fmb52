<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TNotification extends Model
{
    use HasFactory;

    // The table associated with the model
    protected $table = 't_notifications'; // Define the table name if it doesn't follow Laravel's default naming convention

    // The attributes that are mass assignable
    protected $fillable = [
        'jamiat_id',  // Foreign key for Jamiat
        'title',      // Notification title
        'msg',        // Notification message
        'image',      // Image URL or path (nullable)
        'type',       // Notification type (image, text, mixed)
        'created_by', // Name of the user who created the notification
    ];

    // Optionally, if you want to ensure the "created_at" and "updated_at" timestamps are maintained
    public $timestamps = true; // By default, Laravel assumes `created_at` and `updated_at`

    // Optionally, if you don't want to allow the `id` field to be mass assignable, you can add this:
    // protected $guarded = ['id']; 
}