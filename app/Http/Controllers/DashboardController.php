<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function getDashboardStats(Request $request)
    {
        // Get authenticated user's Jamiat ID
        $jamiatId = Auth::user()->jamiat_id;

        // Get parameters from the request, default to "all" if not provided
        $year = $request->input('year', 'all');
        $sector = $request->input('sector', 'all');
        $subSector = $request->input('sub_sector', 'all');

        // Set up filters
        $yearFilter = $year === 'all' ? '%' : $year;
        $sectorFilter = $sector === 'all' ? '%' : $sector;
        $subSectorFilter = $subSector === 'all' ? '%' : $subSector;

        // Log the filters for debugging
        $dats="Filters: Year = $yearFilter, Sector = $sectorFilter, Sub-Sector = $subSectorFilter";

        // Fetch summary data
        $summaryData = DB::table('t_hub')
            ->join('users', 'users.family_id', '=', 't_hub.family_id') // Join t_hub with users
            ->selectRaw("COUNT(DISTINCT users.family_id) as total_houses")
            ->selectRaw("SUM(CASE WHEN t_hub.hub_amount = 0 THEN 1 ELSE 0 END) as hub_not_set")
            ->selectRaw("SUM(CASE WHEN t_hub.due_amount > 0 THEN 1 ELSE 0 END) as hub_due")
            ->selectRaw("SUM(t_hub.hub_amount) as total_hub_amount")
            ->selectRaw("SUM(t_hub.paid_amount) as total_paid_amount")
            ->selectRaw("SUM(t_hub.due_amount) as total_due_amount")
            ->where('users.jamiat_id', $jamiatId)
            ->where('t_hub.year', 'LIKE', $yearFilter)
            ->where('users.sector', 'LIKE', $sectorFilter)
            ->where('users.sub_sector', 'LIKE', $subSectorFilter)
            ->first();

        $paymentBreakdown = DB::table('t_receipts')
            ->join('users', 'users.family_id', '=', 't_receipts.family_id') // Join t_receipts with users
            ->select('t_receipts.mode', DB::raw('SUM(t_receipts.amount) as total_amount'))
            ->where('users.jamiat_id', $jamiatId)
            ->where('t_receipts.year', 'LIKE', $yearFilter)
            ->where('users.sector', 'LIKE', $sectorFilter)
            ->where('users.sub_sector', 'LIKE', $subSectorFilter)
            ->groupBy('t_receipts.mode')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->mode . '_amount' => $item->total_amount];
            });

        $thaaliTakingCount = DB::table('users')
            ->where('jamiat_id', $jamiatId)
            ->where('sector', 'LIKE', $sectorFilter)
            ->where('sub_sector', 'LIKE', $subSectorFilter)
            ->where('thali_status', 'taking')
            ->distinct('family_id') // Ensure distinct family_id
            ->count('family_id');

        // Build the response with the desired format
        $response = [
            'Details' => $dats,
            'total_houses' => (int) $summaryData->total_houses,
            'hub_not_set' => (int) $summaryData->hub_not_set,
            'hub_due' => (int) $summaryData->hub_due,
            'total_hub_amount' => (int) $summaryData->total_hub_amount,
            'total_paid_amount' => (int) $summaryData->total_paid_amount,
            'total_due_amount' => (int) $summaryData->total_due_amount,
            'thaali_taking' => (int) $thaaliTakingCount,
        ];

        // Add payment breakdown to the response, cast as integers
        foreach ($paymentBreakdown as $key => $value) {
            $response[$key] = (int) $value;
        }

        // Return JSON response
        return response()->json($response);
    }
}
