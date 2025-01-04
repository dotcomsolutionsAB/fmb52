<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NiyazModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class NiyazController extends Controller
{
    /**
     * Get all Niyaz records.
     */

    public function getHubSlabs()
    {
        // Get the authenticated user's jamiat_id
        $jamiatId = Auth::user()->jamiat_id;

        // Fetch hub slabs for the user's jamiat_id
        $hubSlabs = DB::table('t_hub_slab')
            ->where('jamiat_id', $jamiatId)
            ->select('id', 'name', 'amount')
            ->get();

        if ($hubSlabs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hub slabs found for your Jamaat.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $hubSlabs,
        ], 200);
    }



    public function getUsersBySlabId($hubSlabId)
    {
        // Get authenticated user's jamiat_id
        $jamiatId = Auth::user()->jamiat_id;

        // Fetch the hub slab details using the provided ID and authenticated user's jamiat_id
        $hubSlab = DB::table('t_hub_slab')
            ->where('id', $hubSlabId)
            ->where('jamiat_id', $jamiatId)
            ->first();

        if (!$hubSlab) {
            return response()->json([
                'success' => false,
                'message' => 'Hub Slab not found for your Jamaat.',
            ], 404);
        }

        // Fetch the current year for the authenticated user's jamiat_id
        $currentYear = DB::table('t_year')
            ->where('jamiat_id', $jamiatId)
            ->where('is_current', '1')
            ->value('year');

        if (!$currentYear) {
            return response()->json([
                'success' => false,
                'message' => 'Current year not set for your Jamaat.',
            ], 404);
        }

        // Fetch matching records from t_hub table where the amount matches and year is current
        $hubs = DB::table('t_hub')
            ->where('hub_amount', $hubSlab->amount) // Correctly references hub_amount
            ->where('year', $currentYear)
            ->where('jamiat_id', $jamiatId) // Ensure jamiat_id matches the authenticated user's jamiat_id
            ->get();

        if ($hubs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No matching records found in t_hub table for the current year.',
            ], 404);
        }

        // Retrieve the associated user details for each family_id in t_hub
        $users = [];
        foreach ($hubs as $hub) {
            // Fetch user where mumeneen_type is 'HOF' and jamiat_id matches
            $user = DB::table('users')
                ->where('family_id', $hub->family_id)
                ->where('mumeneen_type', 'HOF')
                ->where('jamiat_id', $jamiatId)
                ->first();

            if ($user) {
                $users[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'its_id' => $user->its,
                    'family_id' => $user->family_id,
                    'hub_amount' => $hub->hub_amount,
                ];
            }
        }

        if (empty($users)) {
            return response()->json([
                'success' => false,
                'message' => 'No users found for the matching hubs.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $users,
        ], 200);
    }

    public function addNiyaz(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'hub_slab_id' => 'required|exists:t_hub_slab,id',
            'family_ids' => 'required|array|min:1', // Accept multiple family IDs
            'family_ids.*' => 'exists:users,family_id', // Validate each family ID
        ]);

        $hubSlabId = $request->input('hub_slab_id');
        $familyIds = $request->input('family_ids');

        // Fetch the hub slab details
        $hubSlab = DB::table('t_hub_slab')->where('id', $hubSlabId)->first();

        if (!$hubSlab) {
            return response()->json([
                'success' => false,
                'message' => 'Hub Slab not found.',
            ], 404);
        }

        // Calculate the total hub amount for the provided family IDs
        $totalHubAmount = DB::table('t_hub')
            ->whereIn('family_id', $familyIds)
            ->sum('hub_amount');

        // Check the number of family IDs
        $familyCount = count($familyIds);

        // Ensure either minimum_count is met or total hub amount >= 172000
        if ($familyCount < $hubSlab->minimum_count && $totalHubAmount < 172000) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum requirements not met: either minimum number of families or total hub amount must satisfy the conditions.',
            ], 400);
        }

        // Generate a unique niyaz_id for the batch
        $niyazId = DB::table('t_niyaz')->max('niyaz_id') + 1;

        // Prepare the data for insertion
        $data = [];
        $date = now();
        foreach ($familyIds as $familyId) {
            $data[] = [
                'niyaz_id' => $niyazId,
                'family_id' => $familyId,
                'date' => $date,
                'menu' => 'Niyaz Menu Example', // Replace with actual menu if applicable
                'total_amount' => $totalHubAmount,
               
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }

        // Insert all entries into t_niyaz table
        $inserted = DB::table('t_niyaz')->insert($data);

        if (!$inserted) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add Niyaz.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Niyaz added successfully.',
            'data' => [
                'niyaz_id' => $niyazId,
                'family_ids' => $familyIds,
                'total_amount' => $totalHubAmount,
            ],
        ], 201);
    }
    public function index()
    {
        $niyaz = NiyazModel::all();

        return response()->json([
            'success' => true,
            'data' => $niyaz,
        ], 200);
    }

    /**
     * Create a new Niyaz record.
     */
    public function store(Request $request)
    {
        $request->validate([
            'family_id' => 'required|exists:users,family_id',
            'date' => 'required|date',
            'menu' => 'nullable|string',
            'fateha' => 'nullable|string',
            'comments' => 'nullable|string',
            'type' => 'nullable|string',
            'total_amount' => 'nullable|numeric',
            'amount_due' => 'nullable|numeric',
            'amount_paid' => 'nullable|numeric',
        ]);

        $niyaz = NiyazModel::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Niyaz record created successfully.',
            'data' => $niyaz,
        ], 201);
    }

    /**
     * Get a specific Niyaz record by ID.
     */
    public function show(Request $request)
    {
        // Get jamiat_id from the authenticated user
        $jamiat_id = auth()->user()->jamiat_id;
    
        if (!$jamiat_id) {
            return response()->json([
                'success' => false,
                'message' => 'Jamiat ID is missing for the authenticated user.',
            ], 400);
        }
    
        // Fetch grouped niyaz records for the logged-in jamiat
        $niyazRecords = DB::table('t_niyaz')
            ->where('jamiat_id', $jamiat_id)
            ->select('niyaz_id', 'family_id', 'menu', 'fateha', 'comments', 'total_amount', 'date')
            ->get()
            ->groupBy('niyaz_id');
    
        if ($niyazRecords->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No Niyaz records found for the logged-in Jamiat.',
            ], 404);
        }
    
        // Fetch HOF names for each family_id
        $familyIds = $niyazRecords->flatMap(function ($group) {
            return $group->pluck('family_id');
        })->unique();
    
        $hofNames = DB::table('users')
            ->whereIn('family_id', $familyIds)
            ->where('mumeneen_type', 'HOF')
            ->pluck('name', 'family_id'); // Key = family_id, Value = HOF name
    
        // Format response
        $response = $niyazRecords->map(function ($group, $niyazId) use ($hofNames) {
            return [
                'niyaz_id' => $niyazId,
                'records' => $group->map(function ($record) use ($hofNames) {
                    return [
                        'family_id' => $record->family_id,
                        'hof_name' => $hofNames[$record->family_id] ?? 'Unknown', // Get HOF name or default
                        'menu' => $record->menu,
                        'fateha' => $record->fateha,
                        'comments' => $record->comments,
                        'total_amount' => $record->total_amount,
                        'date' => $record->date,
                    ];
                }),
            ];
        })->values();
    
        return response()->json([
            'success' => true,
            'data' => $response,
        ], 200);
    }
    /**
     * Update a specific Niyaz record.
     */
    public function update(Request $request, $id)
    {
        $niyaz = NiyazModel::find($id);

        if (!$niyaz) {
            return response()->json([
                'success' => false,
                'message' => 'Niyaz record not found.',
            ], 404);
        }

        $request->validate([
            'family_id' => 'nullable|exists:users,family_id',
            'date' => 'nullable|date',
            'menu' => 'nullable|string',
            'fateha' => 'nullable|string',
            'comments' => 'nullable|string',
            'type' => 'nullable|string',
            'total_amount' => 'nullable|numeric',
            'amount_due' => 'nullable|numeric',
            'amount_paid' => 'nullable|numeric',
        ]);

        $niyaz->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Niyaz record updated successfully.',
            'data' => $niyaz,
        ], 200);
    }

    /**
     * Delete a specific Niyaz record.
     */
    public function destroy($id)
    {
        $niyaz = NiyazModel::find($id);

        if (!$niyaz) {
            return response()->json([
                'success' => false,
                'message' => 'Niyaz record not found.',
            ], 404);
        }

        $niyaz->delete();

        return response()->json([
            'success' => true,
            'message' => 'Niyaz record deleted successfully.',
        ], 200);
    }

    /**
     * Get all Niyaz records for a specific family ID.
     */
    public function getByFamily($family_id)
    {
        $niyaz = NiyazModel::where('family_id', $family_id)->get();

        if ($niyaz->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No Niyaz records found for this family ID.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $niyaz,
        ], 200);
    }
}
