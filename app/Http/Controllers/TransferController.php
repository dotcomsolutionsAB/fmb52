<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\ItsModel;
use App\Models\SectorModel;
use App\Models\SubSectorModel;
use App\Models\BuildingModel;
use App\Models\YearModel;
use App\Models\MenuModel;
use App\Models\FcmModel;
use App\Models\HubModel;
use App\Models\ThaaliStatus;
use App\Models\ZabihatModel;
use Auth;
use App\Models\Transfer;


class TransferController extends Controller
{
    //
     public function transferOut(Request $request)
    {
        $request->validate([
            'family_id' => 'required|integer',
        ]);

        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jamiat_id = $user->jamiat_id;
        $familyId = $request->input('family_id');

        // Get current year for the jamiat
        $currentYear = YearModel::where('jamiat_id', $jamiat_id)
            ->where('is_current', '1')
            ->value('year');

        if (!$currentYear) {
            return response()->json([
                'message' => 'Current year not set',
            ], 400);
        }

        // Fetch the hub record for this family and current year
        $hubRecord = HubModel::where('family_id', $familyId)
            ->where('jamiat_id', $jamiat_id)
            ->where('year', $currentYear)
            ->first();

        if (!$hubRecord) {
            return response()->json([
                'message' => 'Hub record not found for this family in the current year.',
            ], 404);
        }

        if ($hubRecord->hub_amount == 0) {
            // If hub amount is 0 => delete hub record and mark users inactive
            HubModel::where('family_id', $familyId)
                ->where('year', $currentYear)
                ->delete();

            User::where('family_id', $familyId)
                ->update(['status' => 'in_active']);

            return response()->json([
                'message' => 'Hub deleted and users marked inactive as hub amount is zero.',
                'code' => 200,
            ]);
        } else {
            // Hub amount > 0 - check if due amount is zero
            if ($hubRecord->due_amount > 0) {
                return response()->json([
                    'message' => 'There is due amount pending against this family, kindly adjust the amount before marking the family as inactive.',
                    'code' => 422,
                ], 422);
            } else {
                // No due, mark users inactive
                User::where('family_id', $familyId)
                    ->update(['status' => 'in_active']);

                return response()->json([
                    'message' => 'Users marked inactive successfully as there is no due amount.',
                    'code' => 200,
                ]);
            }
        }
    }

   public function create(Request $request)
{
    $request->validate([
        'family_id' => 'required|integer',
        'sector_to' => 'required|integer',
    ]);

    $hof = User::where('family_id', $request->family_id)
                ->where('mumeneen_type', 'HOF')
                ->first();

    if (!$hof) {
        return response()->json(['message' => 'HOF not found for given family_id'], 404);
    }

    $transfer = Transfer::create([
        'jamiat_id'   => $hof->jamiat_id,
        'family_id'   => $hof->family_id,
        'date'        => now()->toDateString(),
        'sector_from' => $hof->sector_id,
        'sector_to'   => $request->sector_to,
        'log_user'    => Auth()->User()->name ?? 'system',
        'status'      => 0,
    ]);

    return response()->json([
        'message' => 'Transfer created successfully.',
        'data' => $transfer
    ], 201);
}

    // Retrieve all or single transfer
   public function index($id = null)
{
    if ($id) {
        $transfer = Transfer::find($id);

        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found'], 404);
        }

        $transfer->hof = User::where('family_id', $transfer->family_id)
                            ->where('mumeneen_type', 'HOF')
                            ->first();

        $transfer->sector_from_obj = DB::table('t_sector')->where('id', $transfer->sector_from)->first();
        $transfer->sector_to_obj   = DB::table('t_sector')->where('id', $transfer->sector_to)->first();

        return response()->json($transfer);
    }

    $transfers = Transfer::all();

    foreach ($transfers as $transfer) {
        $transfer->hof = User::where('family_id', $transfer->family_id)
                            ->where('mumeneen_type', 'HOF')
                            ->first();

        $transfer->sector_from_obj = DB::table('t_sector')->where('id', $transfer->sector_from)->first();
        $transfer->sector_to_obj   = DB::table('t_sector')->where('id', $transfer->sector_to)->first();
    }

    return response()->json($transfers);
}
    // Update a transfer
    public function update(Request $request, $id)
    {
        $transfer = Transfer::find($id);
        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found'], 404);
        }

        $request->validate([
            'jamiat_id' => 'sometimes|required|integer',
            'family_id' => 'sometimes|required|integer',
            'date' => 'sometimes|required|date',
            'sector_from' => 'sometimes|required|integer',
            'sector_to' => 'sometimes|required|integer',
            'log_user' => 'sometimes|required|string',
            'status' => 'sometimes|required|string',
        ]);

        $transfer->update($request->all());

        return response()->json([
            'message' => 'Transfer updated successfully.',
            'data' => $transfer
        ]);
    }

    // Delete a transfer
    public function delete($id)
    {
        $transfer = Transfer::find($id);
        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found'], 404);
        }

        $transfer->delete();

        return response()->json([
            'message' => 'Transfer deleted successfully.'
        ]);
    }

    public function acceptTransfer(Request $request)
{
    $request->validate([
        'transfer_id'   =>'required|integer',
        'sub_sector_id' => 'required|integer',
        'folio_no'      => 'required|string|max:50',
    ]);

    $transfer = Transfer::find($request->transfer_id);

    if (!$transfer) {
        return response()->json(['message' => 'Transfer not found.'], 404);
    }

    $familyId = $transfer->family_id;

    // Update all users of that family
    $updated = User::where('family_id', $familyId)->update([
        'sector_id'     => $transfer->sector_to,
        'sub_sector_id' => $request->sub_sector_id,
        'folio_no'      => $request->folio_no,
    ]);

    if ($updated) {
        $transfer->status = 1;
        $transfer->save();

        return response()->json([
            'message' => 'Transfer accepted and user data updated successfully.',
            'transfer' => $transfer
        ], 200);
    } else {
        return response()->json([
            'message' => 'User update failed. No records updated.'
        ], 422);
    }
}
}
