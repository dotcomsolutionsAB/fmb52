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
            ->pluck('family_id');
    
        if ($hubs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No matching records found in t_hub table for the current year.',
            ], 404);
        }
    
        // Exclude family IDs already present in the t_niyaz table
        $excludedFamilyIds = DB::table('t_niyaz')
            ->whereIn('family_id', $hubs)
            ->pluck('family_id')
            ->toArray();
    
        $remainingFamilyIds = $hubs->diff($excludedFamilyIds);
    
        if ($remainingFamilyIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'All matching family IDs are already in the Niyaz table.',
            ], 404);
        }
    
        // Retrieve the associated user details for each family_id in t_hub
        $users = DB::table('users')
            ->whereIn('family_id', $remainingFamilyIds)
            ->where('mumeneen_type', 'HOF')
            ->where('jamiat_id', $jamiatId)
            ->get(['id', 'name', 'its', 'family_id']);
    
        if ($users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No users found for the remaining family IDs.',
            ], 404);
        }
    
        // Include hub amount for each user
        $usersWithHubAmount = $users->map(function ($user) use ($hubSlab) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'its_id' => $user->its,
                'family_id' => $user->family_id,
                'hub_amount' => $hubSlab->amount,
            ];
        });
    
        return response()->json([
            'success' => true,
            'data' => $usersWithHubAmount,
        ], 200);
    }

    
    public function addNiyaz(Request $request)
{
    // Validate the incoming request
    $request->validate([
        'hub_slab_id' => 'required|exists:t_hub_slab,id',
        'family_ids' => 'required|array|min:1', // Accept multiple family IDs
        'family_ids.*' => 'exists:users,family_id', // Validate each family ID
        'menu' => 'nullable|string|max:255',
        'fateha' => 'nullable|string|max:255',
        'comments' => 'nullable|string|max:500',
    ]);

    $hubSlabId = $request->input('hub_slab_id');
    $familyIds = $request->input('family_ids');
    $menu = $request->input('menu', null);
    $fateha = $request->input('fateha', null);
    $comments = $request->input('comments', null);

    // Fetch the hub slab details
    $hubSlab = DB::table('t_hub_slab')->where('id', $hubSlabId)->first();

    if (!$hubSlab) {
        return response()->json([
            'success' => false,
            'message' => 'Hub Slab not found.',
        ], 404);
    }

    $totalHubAmount=0;
    // Calculate the total hub amount for the provided family IDs
    $totalHubAmount = DB::table('t_hub')
        ->whereIn('family_id', $familyIds)
        ->sum('hub_amount');

    // Check the number of family IDs
    $familyCount = count($familyIds);

    // Ensure the hub slab conditions are met
    if ($familyCount < $hubSlab->minimum_count || $totalHubAmount < 172000) {
        return response()->json([
            'success' => false,
            'message' => 'Minimum requirements not met: either minimum number of families or total hub amount must satisfy the slab conditions.',
            'details' => [
                'required_minimum_count' => $hubSlab->minimum_count,
                'required_total_amount' => 172000,
                'provided_family_count' => $familyCount,
                'provided_total_amount' => $totalHubAmount,
            ]
        ], 400);
    }

    // Generate a unique niyaz_id for the batch
    $niyazId = DB::table('t_niyaz')->max('niyaz_id') + 1;

    // Calculate total amount per Niyaz entry
    $totalAmountPerFamily = $hubSlab->count > 0 ? $totalHubAmount / $hubSlab->count : 0;

    // Prepare the data for insertion
    $data = [];
    $date = now();
    foreach ($familyIds as $familyId) {
        $data[] = [
            'niyaz_id' => $niyazId,
            'jamiat_id' => auth()->user()->jamiat_id,
            'family_id' => $familyId,
            'date' => $date,
            'menu' => $menu,
            'fateha' => $fateha,
            'comments' => $comments,
            'total_amount' => $totalAmountPerFamily,
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }

    // Insert all entries into t_niyaz table
    DB::table('t_niyaz')->insert($data);

    return response()->json([
        'success' => true,
        'message' => 'Niyaz added successfully.',
        'data' => [
            'niyaz_id' => $niyazId,
            'family_ids' => $familyIds,
            'menu' => $menu,
            'fateha' => $fateha,
            'comments' => $comments,
            'total_amount_per_family' => $totalAmountPerFamily,
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
    public function getNiyazDetailsById($niyazId)
    {
        // Fetch the Niyaz details grouped by family_id
        $niyazRecords = DB::table('t_niyaz')
            ->where('niyaz_id', $niyazId)
            ->select('family_id', 'menu', 'fateha', 'comments', 'total_amount', 'date', 'jamiat_id')
            ->get();
    
        if ($niyazRecords->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Niyaz record not found.',
            ], 404);
        }
    
        // Extract unique family_ids from the Niyaz records
        $familyIds = $niyazRecords->pluck('family_id')->unique();
    
        // Fetch HOF details for each family_id
        $hofDetails = DB::table('users')
            ->whereIn('family_id', $familyIds)
            ->where('mumeneen_type', 'HOF')
            ->pluck('name', 'family_id'); // Key = family_id, Value = HOF name
    
        // Format the response
        $response = $niyazRecords->map(function ($record) use ($hofDetails) {
            return [
                'family_id' => $record->family_id,
                'hof_name' => $hofDetails[$record->family_id] ?? 'Unknown', // Fallback if HOF name not found
                'menu' => $record->menu,
                'fateha' => $record->fateha,
                'comments' => $record->comments,
                'total_amount' => $record->total_amount,
                'date' => $record->date,
                'jamiat_id' => $record->jamiat_id,
            ];
        });
    
        return response()->json([
            'success' => true,
            'data' => $response,
        ], 200);
    }

    public function editNiyaz(Request $request, $niyazId)
{
    // Validate input fields
    $request->validate([
        'menu' => 'nullable|string|max:255',
        'fateha' => 'nullable|string|max:255',
        'comments' => 'nullable|string|max:500',
        'total_amount' => 'nullable|numeric|min:0',
        'date' => 'nullable|date',
        'family_ids' => 'nullable|array|min:1', // Validate family_ids as an array if provided
        'family_ids.*' => 'exists:users,family_id', // Validate each family_id exists in the users table
    ]);

    // Fetch all instances of the niyaz_id
    $niyazRecords = DB::table('t_niyaz')->where('niyaz_id', $niyazId);

    // Check if the niyaz_id exists
    if (!$niyazRecords->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'Niyaz record not found.',
        ], 404);
    }

    // Get jamiat_id from the authenticated user
    $jamiatId = auth()->user()->jamiat_id;

    if (!$jamiatId) {
        return response()->json([
            'success' => false,
            'message' => 'Jamiat ID is missing for the authenticated user.',
        ], 400);
    }

    // Prepare the fields to update
    $updateData = [
        'jamiat_id' => $jamiatId, // Always include jamiat_id
    ];

    if ($request->has('menu')) {
        $updateData['menu'] = $request->input('menu');
    }
    if ($request->has('fateha')) {
        $updateData['fateha'] = $request->input('fateha');
    }
    if ($request->has('comments')) {
        $updateData['comments'] = $request->input('comments');
    }
    if ($request->has('total_amount')) {
        $updateData['total_amount'] = $request->input('total_amount');
    }
    if ($request->has('date')) {
        $updateData['date'] = $request->input('date');
    }
    $updateData['updated_at'] = now();

    // Update all instances of the niyaz_id for the non-family fields
    $updatedRows = $niyazRecords->update($updateData);

    // Handle updating family_ids if provided
    if ($request->has('family_ids')) {
        // Delete existing records for the niyaz_id
        DB::table('t_niyaz')->where('niyaz_id', $niyazId)->delete();

        // Prepare new data for insertion
        $newFamilyIds = $request->input('family_ids');
        $newData = [];
        foreach ($newFamilyIds as $familyId) {
            $newData[] = array_merge($updateData, [
                'niyaz_id' => $niyazId,
                'family_id' => $familyId,
                'created_at' => now(),
            ]);
        }

        // Insert the updated records
        DB::table('t_niyaz')->insert($newData);
    }

    // Return success response
    return response()->json([
        'success' => true,
        'message' => "{$updatedRows} record(s) updated successfully.",
        'updated_fields' => $updateData,
    ], 200);
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
    public function destroy($niyazId)
    {
        // Fetch all records associated with the given niyaz_id
        $niyazRecords = NiyazModel::where('niyaz_id', $niyazId);
    
        // Check if there are any records for the provided niyaz_id
        if (!$niyazRecords->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Niyaz records not found for the provided Niyaz ID.',
            ], 404);
        }
    
        // Delete all records for the provided niyaz_id
        $deletedCount = $niyazRecords->delete();
    
        return response()->json([
            'success' => true,
            'message' => "Niyaz records with Niyaz ID {$niyazId} deleted successfully.",
            'deleted_count' => $deletedCount,
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
