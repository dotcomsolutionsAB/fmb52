<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappQueueModel extends Model
{
    // Define the table associated with the model
    protected $table = 't_whatsapp_queue';

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'jamiat_id', 
        'recipient_type', 
        'group_id', 
        'callback_data', 
        'to', 
        'template_name', 
        'content', 
        'response', 
        'status', 
        'log_user'
    ];

    // Define any relationships, if applicable
    // For example, if this model is related to Jamiat or Users:
    // public function jamiat()
    // {
    //     return $this->belongsTo(JamiatModel::class, 'jamiat_id');
    // }
}