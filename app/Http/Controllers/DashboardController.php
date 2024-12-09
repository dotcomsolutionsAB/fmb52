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
                ->where('users.jamiat_id', $jamiatId)
                ->where('users.role', 'mumeneen'); // Include only mumeneen users

            if ($sectorFilter) {
                $query->whereIn('users.sector', $sectorFilter);
            }
            if ($subSectorFilter) {
                $query->whereIn('users.sub_sector', $subSectorFilter);
            }
        })
        ->first();

    // Payment Modes Query
    $paymentModes = DB::table('t_receipts')
        ->selectRaw("
            mode,
            SUM(amount) AS total_amount
        ")
        ->where('year', $year)
        ->whereExists(function ($query) use ($jamiatId, $sectorFilter, $subSectorFilter) {
            $query->select(DB::raw(1))
                ->from('users')
                ->whereColumn('users.family_id', 't_receipts.family_id')
                ->where('users.jamiat_id', $jamiatId)
                ->where('users.role', 'mumeneen'); // Include only mumeneen users

            if ($sectorFilter) {
                $query->whereIn('users.sector', $sectorFilter);
            }
            if ($subSectorFilter) {
                $query->whereIn('users.sub_sector', $subSectorFilter);
            }
        })
        ->groupBy('mode')
        ->get();

    // Process Payment Modes
    $paymentBreakdown = $paymentModes->mapWithKeys(function ($item) {
        return [$item->mode => number_format($item->total_amount, 0, '.', ',')];
    });

    // Thaali-Taking Query
    $thaaliTakingCount = DB::table('users')
        ->where('jamiat_id', $jamiatId)
        ->where('role', 'mumeneen') // Include only mumeneen users
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

    // User Demographics Query
    $userStats = DB::table('users')
        ->selectRaw("
            COUNT(*) AS total_users,
            SUM(CASE WHEN mumeneen_type = 'HOF' THEN 1 ELSE 0 END) AS total_hof,
            SUM(CASE WHEN mumeneen_type = 'FM' THEN 1 ELSE 0 END) AS total_fm,
            SUM(CASE WHEN LOWER(gender) = 'male' THEN 1 ELSE 0 END) AS total_males,
            SUM(CASE WHEN LOWER(gender) = 'female' THEN 1 ELSE 0 END) AS total_females,
            SUM(CASE WHEN age < 13 THEN 1 ELSE 0 END) AS total_children
        ")
        ->where('jamiat_id', $jamiatId)
        ->where('role', 'mumeneen') // Include only mumeneen users
        ->where(function ($query) use ($sectorFilter, $subSectorFilter) {
            if ($sectorFilter) {
                $query->whereIn('sector', $sectorFilter);
            }
            if ($subSectorFilter) {
                $query->whereIn('sub_sector', $subSectorFilter);
            }
        })
        ->first();

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
        'total_users' => number_format($userStats->total_users, 0, '.', ','),
        'total_hof' => number_format($userStats->total_hof, 0, '.', ','),
        'total_fm' => number_format($userStats->total_fm, 0, '.', ','),
        'total_males' => number_format($userStats->total_males, 0, '.', ','),
        'total_females' => number_format($userStats->total_females, 0, '.', ','),
        'total_children' => number_format($userStats->total_children, 0, '.', ','),
        'payment_breakdown' => $paymentBreakdown, // Add payment breakdown
    ];

    return response()->json($response);
}


public function getCashSummary()
{
    // Define the default year
    $defaultYear = '1445-1446';

    // Step 1: Get cash receipts grouped by sector and filtered by year
    $cashReceipts = DB::table('t_receipts')
        ->select('sector', DB::raw('SUM(amount) as cash'))
        ->where('mode', 'cash')
        ->where('year', $defaultYear)
        ->groupBy('sector')
        ->get();

    // Step 2: Get deposited payments grouped by sector, filtered by year
    $depositedPayments = DB::table('t_payments')
        ->join('t_receipts', 't_payments.id', '=', 't_receipts.payment_id') // Use payment_id to relate
        ->select('t_receipts.sector', DB::raw('SUM(t_payments.amount) as deposited'))
        ->where('t_payments.mode', 'cash')
        ->where('t_payments.year', $defaultYear)
        ->groupBy('t_receipts.sector')
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

    // Step 4: Return response
    return response()->json([
        'success' => true,
        'data' => $summary,
    ]);
}
}