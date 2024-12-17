<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    /**
     * Scenario 1: Detect HOF present in t_its_data but missing in users.
     */
    public function detectMissingHofInUsers()
    {
        $missingHofs = DB::table('t_its_data')
            ->select('t_its_data.its', 't_its_data.name', 't_its_data.mobile', 't_its_data.age', 't_its_data.hof_its')
            ->leftJoin('users', 't_its_data.its', '=', 'users.its')
            ->whereNull('users.its')
            ->whereColumn('t_its_data.its', 't_its_data.hof_its')
            ->get();

        return response()->json([
            'message' => 'Missing HOFs detected.',
            'data' => $missingHofs
        ]);
    }

    /**
     * Scenario 1: Confirm and apply missing HOFs.
     */
    public function confirmMissingHofInUsers(Request $request)
    {
        $validated = $request->validate([
            'hofs' => 'required|array',
            'hofs.*.its' => 'required|string',
            'hofs.*.name' => 'required|string',
            'hofs.*.mobile' => 'nullable|string',
            'hofs.*.age' => 'nullable|integer',
            'hofs.*.hof_its' => 'required|string',
        ]);

        foreach ($validated['hofs'] as $hof) {
            DB::table('users')->insert([
                'its' => $hof['its'],
                'name' => $hof['name'],
                'mobile' => $hof['mobile'],
                'age' => $hof['age'],
                'hof_its' => $hof['hof_its'],
                'role' => 'hof',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Missing HOFs have been synced successfully!']);
    }

    /**
     * Scenario 3: Detect HOF present in users but not in t_its_data.
     */
    public function detectInvalidHofInUsers()
    {
        $invalidHofs = DB::table('users')
            ->select('users.its', 'users.name')
            ->leftJoin('t_its_data', 'users.its', '=', 't_its_data.its')
            ->whereNull('t_its_data.its')
            ->whereColumn('users.its', 'users.hof_its')
            ->get();

        return response()->json([
            'message' => 'Invalid HOFs detected in users.',
            'data' => $invalidHofs
        ]);
    }

    /**
     * Scenario 3: Confirm and delete invalid HOFs.
     */
    public function confirmInvalidHofDeletion(Request $request)
    {
        $validated = $request->validate([
            'hofs' => 'required|array',
            'hofs.*.its' => 'required|string',
        ]);

        foreach ($validated['hofs'] as $hof) {
            DB::table('users')->where('its', $hof['its'])->delete();
        }

        return response()->json(['message' => 'Invalid HOFs have been removed successfully!']);
    }
}