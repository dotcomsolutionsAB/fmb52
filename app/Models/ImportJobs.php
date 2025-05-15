<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
   protected $fillable = ['user_id', 'jamiat_id', 'file_path', 'status', 'message'];

    // Add relation to user if needed
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}