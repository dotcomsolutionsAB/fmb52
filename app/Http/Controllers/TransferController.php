<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
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
}
