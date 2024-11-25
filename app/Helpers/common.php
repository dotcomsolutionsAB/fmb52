<?php

use Illuminate\Support\Str;

if (!function_exists('generateUniqueFamilyId')) {
    function generateUniqueFamilyId() {
        do {
            $newFamilyId = random_int(1000000000, 9999999999); // Generates a 10-digi 
            // $newFamilyId = mt_rand(1000000000, 9999999999); // Generate a random 10-digit integer
        } while (\App\Models\User::where('family_id', $newFamilyId)->exists());

        return $newFamilyId;
    }
}