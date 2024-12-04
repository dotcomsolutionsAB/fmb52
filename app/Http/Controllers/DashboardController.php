<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
//     

public function getDashboardStats(Request $request)
{
    $jamiatId = Auth::user()->jamiat_id;
    $year = $request->input('year', '1445-1446'); // Default year
    $sectors = $request->input('sector', ['all']);
    $subSectors = $request->input('sub_sector', ['all']);

    // Handle filters for SQL query
    $sectorFilter = in_array('all', $sectors) ? null : $sectors;
    $subSectorFilter = in_array('all', $subSectors) ? null : $subSectors;

    // SQL Query for Summary Data
    $summaryData = DB::selectOne("
        SELECT 
            COUNT(DISTINCT t_hub.family_id) AS total_houses,
            SUM(CASE WHEN t_hub.hub_amount = 0 THEN 1 ELSE 0 END) AS hub_not_set,
            SUM(CASE WHEN t_hub.due_amount > 0 THEN 1 ELSE 0 END) AS hub_due,
            SUM(t_hub.hub_amount) AS total_hub_amount,
            SUM(t_hub.paid_amount) AS total_paid_amount,
            SUM(t_hub.due_amount) AS total_due_amount
        FROM t_hub
        JOIN users ON users.family_id = t_hub.family_id
        WHERE t_hub.year = :year
          AND users.jamiat_id = :jamiatId
          AND (:sectorFilter IS NULL OR users.sector IN (:sectorFilter))
          AND (:subSectorFilter IS NULL OR users.sub_sector IN (:subSectorFilter))",
        [
            'year' => $year,
            'jamiatId' => $jamiatId,
            'sectorFilter' => $sectorFilter,
            'subSectorFilter' => $subSectorFilter,
        ]
    );

    // SQL Query for Payment Breakdown
    $paymentBreakdown = DB::select("
        SELECT 
            t_receipts.mode,
            SUM(t_receipts.amount) AS total_amount
        FROM t_receipts
        JOIN users ON users.family_id = t_receipts.family_id
        WHERE t_receipts.year = :year
          AND users.jamiat_id = :jamiatId
          AND (:sectorFilter IS NULL OR users.sector IN (:sectorFilter))
          AND (:subSectorFilter IS NULL OR users.sub_sector IN (:subSectorFilter))
        GROUP BY t_receipts.mode",
        [
            'year' => $year,
            'jamiatId' => $jamiatId,
            'sectorFilter' => $sectorFilter,
            'subSectorFilter' => $subSectorFilter,
        ]
    );

    // SQL Query for Thaali-Taking Families
    $thaaliTakingCount = DB::selectOne("
        SELECT 
            COUNT(DISTINCT users.family_id) AS thaali_taking
        FROM users
        WHERE users.jamiat_id = :jamiatId
          AND users.thali_status = 'taking'
          AND (:sectorFilter IS NULL OR users.sector IN (:sectorFilter))
          AND (:subSectorFilter IS NULL OR users.sub_sector IN (:subSectorFilter))",
        [
            'jamiatId' => $jamiatId,
            'sectorFilter' => $sectorFilter,
            'subSectorFilter' => $subSectorFilter,
        ]
    );

    // Format the Response
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
        'thaali_taking' => number_format($thaaliTakingCount->thaali_taking, 0, '.', ','),
    ];

    // Add Payment Breakdown
    foreach ($paymentBreakdown as $item) {
        $response[$item->mode . '_amount'] = number_format($item->total_amount, 0, '.', ',');
    }

    return response()->json($response);
}
}
