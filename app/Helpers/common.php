<?php

use Illuminate\Support\Str;

if (!function_exists('generateUniqueFamilyId')) {
    function generateUniqueFamilyId() {
        do {
            $newFamilyId = Str::random(10); // Generate a random 10-character ID
            // $newFamilyId = mt_rand(1000000000, 9999999999); // Generate a random 10-digit integer
        } while (\App\Models\User::where('family_id', $newFamilyId)->exists());

        return $newFamilyId;
    }
}