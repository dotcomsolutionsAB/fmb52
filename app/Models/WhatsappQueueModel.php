<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappQueueModel extends Model
{
    //
    protected $table = 't_whatsapp_queue';

    protected $fillable = [
        'jamiat_id', 'family_id', 'group_id', 'callback_url', 'to', 'template_name', 'content', 'json', 'response', 'status', 'log_user'
    ];
}
