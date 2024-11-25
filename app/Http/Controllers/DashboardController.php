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
        $jamiatId = Auth::user()->jamiat_id;

        $year = $request->input('year', '%'); // Default: all years
        $sector = $request->input('sector', '%'); // Default: all sectors
        $subSector = $request->input('sub_sector', '%'); // Default: all sub-sectors

        // Query to fetch year-wise data (Distinct family count from t_users + sum hub amount from t_hub table)
        $yearWiseData = DB::table('t_users')
            ->join('t_hub', 't_users.family_id', '=', 't_hub.family_id')
            ->select(
                't_hub.year',
                DB::raw('COUNT(DISTINCT t_users.family_id) as total_houses'), // Count distinct family IDs
                DB::raw('SUM(t_hub.hub_amount) as total_hub_amount') // Sum hub_amount from hub table
            )
            ->where('t_users.jamiat_id', $jamiatId)
            ->where('t_hub.year', 'LIKE', $year)
            ->where('t_users.sector', 'LIKE', $sector)
            ->where('t_users.sub_sector', 'LIKE', $subSector)
            ->groupBy('t_hub.year')
            ->get();

        // Query to fetch sector-wise data
        $sectorWiseData = DB::table('t_users')
            ->join('t_hub', 't_users.family_id', '=', 't_hub.family_id')
            ->select(
                't_hub.year',
                't_users.sector',
                DB::raw('COUNT(DISTINCT t_users.family_id) as total_houses'),
                DB::raw('SUM(t_hub.hub_amount) as total_hub_amount')
            )
            ->where('t_users.jamiat_id', $jamiatId)
            ->where('t_hub.year', 'LIKE', $year)
            ->where('t_users.sector', 'LIKE', $sector)
            ->where('t_users.sub_sector', 'LIKE', $subSector)
            ->groupBy('t_hub.year', 't_users.sector')
            ->get();

        // Query to fetch sector-subsector-wise data
        $sectorSubSectorWiseData = DB::table('t_users')
            ->join('t_hub', 't_users.family_id', '=', 't_hub.family_id')
            ->select(
                't_hub.year',
                't_users.sector',
                't_users.sub_sector',
                DB::raw('COUNT(DISTINCT t_users.family_id) as total_houses'),
                DB::raw('SUM(t_hub.hub_amount) as total_hub_amount')
            )
            ->where('t_users.jamiat_id', $jamiatId)
            ->where('t_hub.year', 'LIKE', $year)
            ->where('t_users.sector', 'LIKE', $sector)
            ->where('t_users.sub_sector', 'LIKE', $subSector)
            ->groupBy('t_hub.year', 't_users.sector', 't_users.sub_sector')
            ->get();

        // Query to fetch payment breakdown (t_receipts table for payment details)
        $paymentData = DB::table('t_receipts')
            ->select(
                'year',
                'sector',
                'sub_sector',
                'type', // Payment type: Cash, Cheque, NEFT, etc.
                DB::raw('SUM(amount) as total_amount')
            )
            ->where('jamiat_id', $jamiatId)
            ->where('year', 'LIKE', $year)
            ->where('sector', 'LIKE', $sector)
            ->where('sub_sector', 'LIKE', $subSector)
            ->groupBy('year', 'sector', 'sub_sector', 'type')
            ->get();

        // Count users taking thaali
        $thaaliUsersCount = DB::table('t_users')
            ->where('jamiat_id', $jamiatId)
            ->where('thaali_status', 'active')
            ->where('sector', 'LIKE', $sector)
            ->where('sub_sector', 'LIKE', $subSector)
            ->count();

        // Response structure
        $response = [
            'year_wise_data' => $yearWiseData,
            'sector_wise_data' => $sectorWiseData,
            'sector_sub_sector_wise_data' => $sectorSubSectorWiseData,
            'payment_data' => $paymentData,
            'total_thaali_users' => $thaaliUsersCount
        ];

        return response()->json($response);
    }
}
