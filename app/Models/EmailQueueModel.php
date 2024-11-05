<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailQueueModel extends Model
{
    //
    protected $table = 't_email_queue';

    protected $fillable = [
        'jamiat_id', 'family_id', 'to', 'cc', 'bcc', 'from', 'subject', 'content', 'attachment', 'response', 'status', 'log_user'
    ];
}
