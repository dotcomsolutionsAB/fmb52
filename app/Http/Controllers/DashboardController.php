<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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

        // Always include consolidated data
        $consolidatedData = DB::table('t_hub')
            ->selectRaw("'All' as year, 'All' as sector, 'All' as sub_sector")
            ->selectRaw("COUNT(DISTINCT family_id) as total_houses")
            ->selectRaw("SUM(CASE WHEN hub_amount = 0 THEN 1 ELSE 0 END) as hub_not_set")
            ->selectRaw("SUM(CASE WHEN hub_due > 0 THEN 1 ELSE 0 END) as hub_due_count")
            ->selectRaw("SUM(hub_amount) as total_hub_amount")
            ->selectRaw("SUM(hub_paid) as total_hub_received")
            ->selectRaw("SUM(hub_due) as total_hub_due")
            ->where('jamiat_id', $jamiatId)
            ->first();

        $paymentBreakdown = DB::table('t_receipts')
            ->select('mode', DB::raw('SUM(amount) as total_amount'))
            ->where('jamiat_id', $jamiatId)
            ->groupBy('mode')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->mode . '_amount' => $item->total_amount];
            });

        $thaaliTakingCount = DB::table('users')
            ->where('jamiat_id', $jamiatId)
            ->where('thali_status', 'taking')
            ->count();

        // Consolidated response format
        $response = [
            'year' => $consolidatedData->year,
            'sector' => $consolidatedData->sector,
            'sub_sector' => $consolidatedData->sub_sector,
            'total_houses' => $consolidatedData->total_houses,
            'hub_not_set' => $consolidatedData->hub_not_set,
            'hub_due' => $consolidatedData->hub_due_count,
            'total_hub_amount' => $consolidatedData->total_hub_amount,
            'total_hub_received' => $consolidatedData->total_hub_received,
            'total_hub_due' => $consolidatedData->total_hub_due,
            'thaali_taking' => $thaaliTakingCount,
        ];

        // Add payment breakdown
        $response = array_merge($response, $paymentBreakdown->toArray());

        // Add filtered data if requested
        if ($year !== 'all' || $sector !== 'all' || $subSector !== 'all') {
            $response['filtered_data'] = [
                'year_wise_data' => DB::table('users')
                    ->join('t_hub', 'users.family_id', '=', 't_hub.family_id')
                    ->select(
                        't_hub.year',
                        DB::raw('COUNT(DISTINCT users.family_id) as total_houses'),
                        DB::raw('SUM(t_hub.hub_amount) as total_hub_amount')
                    )
                    ->where('users.jamiat_id', $jamiatId)
                    ->where('t_hub.year', 'LIKE', $yearFilter)
                    ->where('users.sector', 'LIKE', $sectorFilter)
                    ->where('users.sub_sector', 'LIKE', $subSectorFilter)
                    ->groupBy('t_hub.year')
                    ->get(),
                'sector_wise_data' => DB::table('users')
                    ->join('t_hub', 'users.family_id', '=', 't_hub.family_id')
                    ->select(
                        't_hub.year',
                        'users.sector',
                        DB::raw('COUNT(DISTINCT users.family_id) as total_houses'),
                        DB::raw('SUM(t_hub.hub_amount) as total_hub_amount')
                    )
                    ->where('users.jamiat_id', $jamiatId)
                    ->where('t_hub.year', 'LIKE', $yearFilter)
                    ->where('users.sector', 'LIKE', $sectorFilter)
                    ->where('users.sub_sector', 'LIKE', $subSectorFilter)
                    ->groupBy('t_hub.year', 'users.sector')
                    ->get(),
                'sector_sub_sector_wise_data' => DB::table('users')
                    ->join('t_hub', 'users.family_id', '=', 't_hub.family_id')
                    ->select(
                        't_hub.year',
                        'users.sector',
                        'users.sub_sector',
                        DB::raw('COUNT(DISTINCT users.family_id) as total_houses'),
                        DB::raw('SUM(t_hub.hub_amount) as total_hub_amount')
                    )
                    ->where('users.jamiat_id', $jamiatId)
                    ->where('t_hub.year', 'LIKE', $yearFilter)
                    ->where('users.sector', 'LIKE', $sectorFilter)
                    ->where('users.sub_sector', 'LIKE', $subSectorFilter)
                    ->groupBy('t_hub.year', 'users.sector', 'users.sub_sector')
                    ->get(),
            ];
        }

        // Return JSON response
        return response()->json($response);
    }
}
