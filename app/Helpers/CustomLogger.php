<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class CustomLogger
{
    public static function log(string $message): void
    {
        DB::table('mylogs')->insert([
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}