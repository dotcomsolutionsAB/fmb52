<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackResponseModel extends Model
{
    //
    protected $table = 't_feedback_response'; // Update this with the actual table name if different

    protected $fillable = [
        'jamiat_id', 'family_id', 'feedback_id', 'name', 'date', 'message', 'attachment'
    ];
}
