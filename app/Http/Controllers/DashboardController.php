<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function getDashboardStats(Request $request)
    {
        $user = Auth::user();
        $jamiatId = $user->jamiat_id;
        $userSubSectorAccess = json_decode($user->sub_sector_access_id, true); // Get user's accessible sub-sectors
    
        $year = $request->input('year');
        $requestedSectors = $request->input('sector', []);
        $requestedSubSectors = $request->input('sub_sector', []);
    
        // Validation: Allow "all" or integers
        $request->validate([
            'year' => 'required|string',
            'sector' => 'required|array',
            'sector.*' => ['required', function ($attribute, $value, $fail) {
                if ($value !== 'all' && !is_numeric($value)) {
                    $fail("The $attribute field must be an integer or the string 'all'.");
                }
            }],
            'sub_sector' => 'required|array',
            'sub_sector.*' => ['required', function ($attribute, $value, $fail) {
                if ($value !== 'all' && !is_numeric($value)) {
                    $fail("The $attribute field must be an integer or the string 'all'.");
                }
            }],
        ]);
    
        // Handle "all" for sub-sector and sector inputs
        if (in_array('all', $requestedSubSectors)) {
            $requestedSubSectors = $userSubSectorAccess; // Replace "all" with user's accessible sub-sectors
        }
        if (in_array('all', $requestedSectors)) {
            $requestedSectors = DB::table('t_sub_sector')
                ->whereIn('id', $userSubSectorAccess)
                ->distinct()
                ->pluck('sector_id')
                ->toArray(); // Replace "all" with sectors linked to user's accessible sub-sectors
        }
    
        // Ensure the requested sub-sectors match the user's access
        $subSectorFilter = array_intersect($requestedSubSectors, $userSubSectorAccess);
    
        if (empty($subSectorFilter)) {
            return response()->json([
                'message' => 'Access denied for the requested sub-sectors.',
            ], 403);
        }
    
        // Fetch sector IDs corresponding to the accessible sub-sectors
        $accessibleSectors = DB::table('t_sub_sector')
            ->whereIn('id', $subSectorFilter)
            ->distinct()
            ->pluck('sector_id')
            ->toArray();
    
        // Validate that the requested sectors match the accessible ones
        $sectorFilter = array_intersect($requestedSectors, $accessibleSectors);
    
        if (empty($sectorFilter)) {
            return response()->json([
                'message' => 'Access denied for the requested sectors.',
            ], 403);
        }
    
        // Count total accessible sectors and sub-sectors
        $totalSectorsCount = DB::table('t_sector')
            ->whereIn('id', $accessibleSectors)
            ->count();
    
        $totalSubSectorsCount = DB::table('t_sub_sector')
            ->whereIn('id', $subSectorFilter)
            ->count();
    
        // Prepare response data
        $response = [
            'year' => $year,
            'sectors' => $sectorFilter,
            'sub-sectors' => $subSectorFilter,
            'total_sectors_count' => $totalSectorsCount,
            'total_sub_sectors_count' => $totalSubSectorsCount,
        ];
    
        return response()->json($response);
    }

public function getCashSummary()
{
    // Define the default year
    $defaultYear = '1445-1446';

    // Step 1: Get cash receipts grouped by sector
    $cashReceipts = DB::table('t_receipts')
        ->select('sector', DB::raw('SUM(amount) as cash'))
        ->where('mode', 'cash')
        ->where('year', $defaultYear)
        ->groupBy('sector')
        ->get();

    // Step 2: Get deposited payments grouped by sector
    $depositedPayments = DB::table('t_payments')
        ->select('sector', DB::raw('SUM(amount) as deposited'))
        ->where('mode', 'cash')
        ->where('year', $defaultYear)
        ->groupBy('sector')
        ->get();

    // Step 3: Merge data to calculate in_hand
    $summary = $cashReceipts->map(function ($receipt) use ($depositedPayments) {
        $sectorPayments = $depositedPayments->firstWhere('sector', $receipt->sector);
        $deposited = $sectorPayments ? $sectorPayments->deposited : 0;

        return [
            'sector' => $receipt->sector,
            'cash' => $receipt->cash,
            'deposited' => $deposited,
            'in_hand' => $receipt->cash - $deposited,
        ];
    });

    // Include any sectors in payments that are missing in receipts
    $additionalSectors = $depositedPayments->filter(function ($payment) use ($cashReceipts) {
        return !$cashReceipts->contains('sector', $payment->sector);
    })->map(function ($payment) {
        return [
            'sector' => $payment->sector,
            'cash' => 0,
            'deposited' => $payment->deposited,
            'in_hand' => -$payment->deposited,
        ];
    });

    // Combine results
    $finalSummary = $summary->concat($additionalSectors);

    // Step 4: Return response
    return response()->json([
        'success' => true,
        'data' => $finalSummary,
    ]);
}
}