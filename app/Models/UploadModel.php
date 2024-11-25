<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadModel extends Model
{
    //
    protected $table = 't_uploads';

    protected $fillable = [
        'jamiat_id', 'family_id', 'file_ext', 'file_url', 'file_size', 'type'
    ];
}
