<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Auth\AuthController;
use Auth;

class DashboardController extends Controller
{
    public function getDashboardStats(Request $request)
    {
        // Get filters from the request
        $jamiat_id = Auth::user()->jamiat_id;

        $year = $request->input('year', '%'); // Default: all years
        $sector = $request->input('sector', '%'); // Default: all sectors
        $subSector = $request->input('sub_sector', '%'); // Default: all sub-sectors

        // Query to fetch year-wise data
        $yearWiseData = DB::table('current_normal_table') // Replace with your table name
            ->select(
                'year',
                DB::raw('COUNT(*) as total_houses'),
                DB::raw('SUM(hub_amount) as total_hub_amount')
            )
            ->where('jamiat_id', $jamiatId)
            ->where('year', 'LIKE', $year)
            ->where('sector', 'LIKE', $sector)
            ->where('sub_sector', 'LIKE', $subSector)
            ->groupBy('year')
            ->get();

        // Query to fetch sector-wise data
        $sectorWiseData = DB::table('current_normal_table')
            ->select(
                'year',
                'sector',
                DB::raw('COUNT(*) as total_houses'),
                DB::raw('SUM(hub_amount) as total_hub_amount')
            )
            ->where('jamiat_id', $jamiatId)
            ->where('year', 'LIKE', $year)
            ->where('sector', 'LIKE', $sector)
            ->where('sub_sector', 'LIKE', $subSector)
            ->groupBy('year', 'sector')
            ->get();

        // Query to fetch sector-subsector-wise data
        $sectorSubSectorWiseData = DB::table('current_normal_table')
            ->select(
                'year',
                'sector',
                'sub_sector',
                DB::raw('COUNT(*) as total_houses'),
                DB::raw('SUM(hub_amount) as total_hub_amount')
            )
            ->where('jamiat_id', $jamiatId)
            ->where('year', 'LIKE', $year)
            ->where('sector', 'LIKE', $sector)
            ->where('sub_sector', 'LIKE', $subSector)
            ->groupBy('year', 'sector', 'sub_sector')
            ->get();

        // Query to fetch payment breakdown
        $paymentData = DB::table('receipts')
            ->select(
                'year',
                'sector',
                'sub_sector',
                'type', // Payment type: Cash, Cheque, NEFT
                DB::raw('SUM(amount) as total_amount')
            )
            ->where('jamiat_id', $jamiatId)
            ->where('year', 'LIKE', $year)
            ->where('sector', 'LIKE', $sector)
            ->where('sub_sector', 'LIKE', $subSector)
            ->groupBy('year', 'sector', 'sub_sector', 'type')
            ->get();

        // Response structure
        $response = [
            'year_wise_data' => $yearWiseData,
            'sector_wise_data' => $sectorWiseData,
            'sector_sub_sector_wise_data' => $sectorSubSectorWiseData,
            'payment_data' => $paymentData,
        ];

        return response()->json($response);
    }
}
