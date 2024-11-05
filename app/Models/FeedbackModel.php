<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackModel extends Model
{
    //
    protected $table = 't_feedback'; // Or the actual table name

    protected $fillable = [
        'jamiat_id', 'family_id', 'date', 'subject', 'message', 'ratings', 'attachment'
    ];

}
