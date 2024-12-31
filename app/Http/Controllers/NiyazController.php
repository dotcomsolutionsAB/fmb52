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
