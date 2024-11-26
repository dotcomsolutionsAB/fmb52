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
        $jamiatId = Auth::user()->jamiat_id;
        $year = $request->input('year', 'all');
        $sector = $request->input('sector', 'all');
        $subSector = $request->input('sub_sector', 'all');

        $yearFilter = $year === 'all' ? '%' : $year;
        $sectorFilter = $sector === 'all' ? '%' : $sector;
        $subSectorFilter = $subSector === 'all' ? '%' : $subSector;

        // Log filters for debugging
        Log::info("Filters: Year = $year, Sector = $sector, Sub-Sector = $subSector");

        $summaryData = DB::table('t_hub')
            ->selectRaw("COUNT(DISTINCT t_hub.family_id) as total_houses")
            ->selectRaw("SUM(CASE WHEN t_hub.hub_amount = 0 THEN 1 ELSE 0 END) as hub_not_set")
            ->selectRaw("SUM(CASE WHEN t_hub.due_amount > 0 THEN 1 ELSE 0 END) as hub_due")
            ->selectRaw("SUM(t_hub.hub_amount) as total_hub_amount")
            ->selectRaw("SUM(t_hub.paid_amount) as total_paid_amount")
            ->selectRaw("SUM(t_hub.due_amount) as total_due_amount")
            ->where('t_hub.year', 'LIKE', $yearFilter)
            ->whereExists(function ($query) use ($jamiatId, $sectorFilter, $subSectorFilter) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.family_id', 't_hub.family_id')
                    ->where('users.jamiat_id', $jamiatId)
                    ->where('users.sector', 'LIKE', $sectorFilter)
                    ->where('users.sub_sector', 'LIKE', $subSectorFilter);
            })
            ->first();

        $paymentBreakdown = DB::table('t_receipts')
            ->join('users', 'users.family_id', '=', 't_receipts.family_id')
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
            ->distinct('family_id')
            ->count('family_id');

        $response = [
            'year'=>$yearFilter,
            'sector'=>$sectorFilter,
            'sub-sector'=>$subSectorFilter,
            'total_houses' => number_format($summaryData->total_houses, 0, '.', ','),
            'hub_not_set' => number_format($summaryData->hub_not_set, 0, '.', ','),
            'hub_due' => number_format($summaryData->hub_due, 0, '.', ','),
            'total_hub_amount' => number_format($summaryData->total_hub_amount, 0, '.', ','),
            'total_paid_amount' => number_format($summaryData->total_paid_amount, 0, '.', ','),
            'total_due_amount' => number_format($summaryData->total_due_amount, 0, '.', ','),
            'thaali_taking' => number_format($thaaliTakingCount, 0, '.', ','),
        ];

        foreach ($paymentBreakdown as $key => $value) {
            $response[$key] = number_format($value, 0, '.', ',');
        }

        return response()->json($response);
    }
}
