<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushNotificationsQueueModel extends Model
{
    //
    protected $table = 't_whatsapp_queue';

    protected $fillable = [
        'jamiat_id', 'family_id', 'title', 'message', 'icon', 'callback', 'status', 'response'
    ];
}
