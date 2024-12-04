<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function getDashboardStats(Request $request)
    {
        $jamiatId = Auth::user()->jamiat_id;
        $year = $request->input('year', '1445-1446'); // Default year
        $sectors = $request->input('sector', ['all']);
        $subSectors = $request->input('sub_sector', ['all']);

        // Handle filters
        $sectorFilter = in_array('all', $sectors) ? null : $sectors;
        $subSectorFilter = in_array('all', $subSectors) ? null : $subSectors;

        // Summary Data Query
        $summaryData = DB::table('t_hub')
            ->selectRaw("
                COUNT(DISTINCT t_hub.family_id) AS total_houses,
                SUM(CASE WHEN t_hub.hub_amount = 0 THEN 1 ELSE 0 END) AS hub_not_set,
                SUM(CASE WHEN t_hub.due_amount > 0 THEN 1 ELSE 0 END) AS hub_due,
                SUM(t_hub.hub_amount) AS total_hub_amount,
                SUM(t_hub.paid_amount) AS total_paid_amount,
                SUM(t_hub.due_amount) AS total_due_amount
            ")
            ->where('t_hub.year', $year)
            ->whereExists(function ($query) use ($jamiatId, $sectorFilter, $subSectorFilter) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.family_id', 't_hub.family_id')
                    ->where('users.jamiat_id', $jamiatId);

                if ($sectorFilter) {
                    $query->whereIn('users.sector', $sectorFilter);
                }
                if ($subSectorFilter) {
                    $query->whereIn('users.sub_sector', $subSectorFilter);
                }
            })
            ->first();

        // Payment Breakdown Query
        $paymentBreakdown = DB::table('t_receipts')
            ->join('users', 'users.family_id', '=', 't_receipts.family_id')
            ->select('t_receipts.mode', DB::raw('SUM(t_receipts.amount) AS total_amount'))
            ->where('t_receipts.year', $year)
            ->where('users.jamiat_id', $jamiatId);

        if ($sectorFilter) {
            $paymentBreakdown->whereIn('users.sector', $sectorFilter);
        }
        if ($subSectorFilter) {
            $paymentBreakdown->whereIn('users.sub_sector', $subSectorFilter);
        }

        $paymentBreakdown = $paymentBreakdown
            ->groupBy('t_receipts.mode')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->mode . '_amount' => $item->total_amount];
            });

        // Thaali-Taking Query
        $thaaliTakingCount = DB::table('users')
            ->where('jamiat_id', $jamiatId)
            ->where('thali_status', 'taking')
            ->where(function ($query) use ($sectorFilter, $subSectorFilter) {
                if ($sectorFilter) {
                    $query->whereIn('sector', $sectorFilter);
                }
                if ($subSectorFilter) {
                    $query->whereIn('sub_sector', $subSectorFilter);
                }
            })
            ->distinct('family_id')
            ->count('family_id');

        // Prepare Response
        $response = [
            'year' => $year,
            'sectors' => $sectors,
            'sub-sectors' => $subSectors,
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