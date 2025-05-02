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
    
        // Handle "all" for sector and sub-sector inputs
        if (in_array('all', $requestedSectors)) {
            $requestedSectors = DB::table('t_sector')->pluck('id')->toArray(); // Replace "all" with all sector IDs
        }
        if (in_array('all', $requestedSubSectors)) {
            $requestedSubSectors = DB::table('t_sub_sector')
                ->whereIn('sector_id', $requestedSectors) // Fetch sub-sectors for the specified sectors
                ->pluck('id')
                ->toArray();
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
            ->whereExists(function ($query) use ($jamiatId, $subSectorFilter) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.family_id', 't_hub.family_id')
                    ->where('users.jamiat_id', $jamiatId)
                    ->where('users.role', 'mumeneen') // Include only mumeneen users
                    ->whereIn('users.sub_sector_id', $subSectorFilter);
            })
            ->first();
    
        // Payment Modes Query
        $paymentModes = DB::table('t_receipts')
            ->selectRaw("
                mode,
                SUM(amount) AS total_amount
            ")
            ->where('year', $year)
            ->whereExists(function ($query) use ($jamiatId, $subSectorFilter) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.family_id', 't_receipts.family_id')
                    ->where('users.jamiat_id', $jamiatId)
                    ->where('users.role', 'mumeneen') // Include only mumeneen users
                    ->whereIn('users.sub_sector_id', $subSectorFilter);
            })
            ->groupBy('mode')
            ->get();
    
        // Process Payment Modes
        $paymentBreakdown = $paymentModes->mapWithKeys(function ($item) {
            return [$item->mode => $item->total_amount];
        });
    
        // Thaali-Taking Query
        $thaaliTakingCount = DB::table('users')
            ->where('jamiat_id', $jamiatId)
            ->where('mumeneen_type', 'HOF') // Include only mumeneen users
            ->where('thali_status', 'taking')
            ->where('status', 'active')
            ->whereIn('sub_sector_id', $subSectorFilter)
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
            ->where('status', 'active')
            ->where('role', 'mumeneen') // Include only mumeneen users
            ->whereIn('sub_sector_id', $subSectorFilter)
            ->first();
    
        // Prepare response data
        $response = [
                'year' => $year,
                'sectors' => $sectorFilter,
                'sub-sectors' => $subSectorFilter,
                'total_sectors_count' => $totalSectorsCount,
                'total_sub_sectors_count' => $totalSubSectorsCount,
                'total_houses' => $summaryData->total_houses,
                'hub_not_set' => $summaryData->hub_not_set,
                'hub_due' => $summaryData->hub_due,
                'total_hub_amount' => $summaryData->total_hub_amount,
                'total_paid_amount' => $summaryData->total_paid_amount,
                'total_due_amount' => $summaryData->total_due_amount,
                'thaali_taking' => $thaaliTakingCount,
                'total_users' => $userStats->total_users,
                'total_hof' => $userStats->total_hof,
                'total_fm' => $userStats->total_fm,
                'total_males' => $userStats->total_males,
                'total_females' => $userStats->total_females,
                'total_children' => $userStats->total_children,
                'payment_breakdown' => $paymentBreakdown,
            ];
    
        return response()->json($response);
    }

    public function getCashSummary(Request $request)
    {
        $user = Auth::user();
        $jamiatId = $user->jamiat_id;
        $userSubSectorAccess = json_decode($user->sub_sector_access_id, true); // Get user's accessible sub-sectors
    
        // Define the default year
        $defaultYear = '1445-1446';
    
        // Get the requested sectors and sub-sectors
        $requestedSectors = $request->input('sector', []);
        $requestedSubSectors = $request->input('sub_sector', []);
    
        // Validation: Allow "all" or integers
        $request->validate([
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
    
        // Step 1: Get cash receipts grouped by sector
        $cashReceipts = DB::table('t_receipts')
            ->select('sector_id', DB::raw('SUM(amount) as cash'))
            ->where('mode', 'cash')
            ->where('year', $defaultYear)
            ->whereIn('sector_id', $sectorFilter)
            ->groupBy('sector_id')
            ->get();
    
        // Step 2: Get deposited payments grouped by sector
        $depositedPayments = DB::table('t_payments')
            ->select('sector_id', DB::raw('SUM(amount) as deposited'))
            ->where('mode', 'cash')
            ->where('year', $defaultYear)
            ->whereIn('sector_id', $sectorFilter)
            ->groupBy('sector_id')
            ->get();
    
        // Step 3: Merge data to calculate in_hand
        $summary = $cashReceipts->map(function ($receipt) use ($depositedPayments) {
            $sectorPayments = $depositedPayments->firstWhere('sector_id', $receipt->sector_id);
            $deposited = $sectorPayments ? $sectorPayments->deposited : 0;
    
            return [
                'sector_id' => $receipt->sector_id,
                'cash' => $receipt->cash,
                'deposited' => $deposited,
                'in_hand' => $receipt->cash - $deposited,
            ];
        });
    
        // Include any sectors in payments that are missing in receipts
        $additionalSectors = $depositedPayments->filter(function ($payment) use ($cashReceipts) {
            return !$cashReceipts->contains('sector_id', $payment->sector_id);
        })->map(function ($payment) {
            return [
                'sector_id' => $payment->sector_id,
                'cash' => 0,
                'deposited' => $payment->deposited,
                'in_hand' => -$payment->deposited,
            ];
        });
    
        // Combine results
        $finalSummary = $summary->concat($additionalSectors);
    
        // Step 4: Add sector names
        $sectorNames = DB::table('t_sector')
            ->whereIn('id', $finalSummary->pluck('sector_id')->toArray())
            ->pluck('name', 'id'); // Get sector names as an associative array
    
        $finalSummaryWithNames = $finalSummary->map(function ($item) use ($sectorNames) {
            return [
                'sector_id' => $item['sector_id'],
                'sector_name' => $sectorNames[$item['sector_id']] ?? 'Unknown',
                'cash' => $item['cash'],
                'deposited' => $item['deposited'],
                'in_hand' => $item['in_hand'],
            ];
        });
    
        // Step 5: Return response
        return response()->json([
            'success' => true,
            'data' => $finalSummaryWithNames,
        ]);
    }
    public function dashboard(Request $request)
{
    $familyId = $request->input('family_id');
    $date = $request->input('date', date('Y-m-d')); // fallback to today if not provided

    if (!$familyId) {
        return response()->json([
            'code' => 400,
            'status' => false,
            'message' => 'Family ID is required.',
        ]);
    }

    try {
        $client = new \GuzzleHttp\Client();

        // 1. Receipts
        $receiptRes = $client->post(env('APP_URL') . '/app/receipts/by_family_ids', [
            'json' => ['family_ids' => ["$familyId"]]
        ]);
        $receipts = json_decode($receiptRes->getBody()->getContents(), true);

        // 2. Hub
        $hubRes = $client->get(env('APP_URL') . "/app/hub/get/$familyId");
        $hub = json_decode($hubRes->getBody()->getContents(), true);

        // 3. Menu
        $menuRes = $client->post(env('APP_URL') . '/app/menus/by-date', [
            'json' => ['date' => $date]
        ]);
        $menu = json_decode($menuRes->getBody()->getContents(), true);

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Dashboard data fetched successfully.',
            'data' => [
                'receipts' => $receipts['data'] ?? [],
                'hub' => $hub['data'] ?? [],
                'menu' => $menu['data'] ?? [],
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'Failed to fetch dashboard data.',
            'error' => $e->getMessage()
        ]);
    }
}
}