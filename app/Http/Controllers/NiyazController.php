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
    public function show($id)
    {
        $niyaz = NiyazModel::find($id);

        if (!$niyaz) {
            return response()->json([
                'success' => false,
                'message' => 'Niyaz record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $niyaz,
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
