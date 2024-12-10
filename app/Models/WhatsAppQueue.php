<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppQueue extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_queue';

    protected $fillable = [
        'group_id',
        'callback_data',
        'recipient_type',
        'to',
        'type',
        'file_url',
        'content',
        'status',
        'response',
        'msg_id',
        'msg_status'
    ];
}