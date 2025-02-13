<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\HubModel;
use App\Models\YearModel;

class HubController extends Controller
{
    public function hub_distribution(Request $request)
    {
        // Get authenticated user's jamiat_id
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $jamiatId = $user->jamiat_id;
		
        // Get the current year from `t_year` where `is_current = 1` for the user's jamiat
        $currentYear = YearModel::where('jamiat_id', $jamiatId)->where('is_current', '1')->value('year');

        if (!$currentYear) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'Current year not found for the user',
                'data' => [],
            ]);
        }

        // Fetch permitted sub-sector IDs for the user
        $permittedSubSectorIds = $user->sub_sector_access_id ?? [];

        // Ensure sub-sector access IDs are an array
        if (!is_array($permittedSubSectorIds)) {
            $permittedSubSectorIds = json_decode($permittedSubSectorIds, true) ?? [];
        }

        // Validation for sector and sub-sector input
        $request->validate([
            'sector' => 'required|array',
            'sector.*' => ['required', function ($attribute, $value, $fail) {
                if ($value !== 'all' && !is_numeric($value)) {
                    $fail("The $attribute field must be an integer or 'all'.");
                }
            }],
            'sub_sector' => 'required|array',
            'sub_sector.*' => ['required', function ($attribute, $value, $fail) {
                if ($value !== 'all' && !is_numeric($value)) {
                    $fail("The $attribute field must be an integer or 'all'.");
                }
            }],
        ]);

        // Handle "all" for sector and sub-sector
        $requestedSectors = $request->input('sector', []);
        if (in_array('all', $requestedSectors)) {
            $requestedSectors = DB::table('t_sector')->pluck('id')->toArray();
            $requestedSectors[] = null; // Include NULL values for sector_id
        }

        $requestedSubSectors = $request->input('sub_sector', []);
        if (in_array('all', $requestedSubSectors)) {
            $requestedSubSectors = DB::table('t_sub_sector')
                ->whereIn('sector_id', array_filter($requestedSectors)) // Fetch sub-sectors for the specified sectors
                ->pluck('id')
                ->toArray();
            $requestedSubSectors[] = null; // Include NULL values for sub_sector_id
        }

        // Ensure the requested sub-sectors match the user's permissions
        $finalSubSectors = array_merge(array_intersect($requestedSubSectors, $permittedSubSectorIds), [null]);
		
        // Fetch total HoF where entry is present in `t_hub` for the current year and matches the filters
        $total_hof = HubModel::where('year', $currentYear)
            ->whereIn('family_id', function ($query) use ($requestedSectors, $finalSubSectors, $jamiatId) {
                $query->select('family_id')
                    ->from('users')
                    ->where('jamiat_id', $jamiatId)
                    ->whereIn('sector_id', $requestedSectors)
                    ->whereIn('sub_sector_id', $finalSubSectors);
            })
            ->distinct('family_id')
            ->count();

        // Get count of `hub_done` where `hub_amount` > 0 or `thali_status` is 'joint'
        $hub_done = HubModel::where('year', $currentYear)
        ->whereIn('family_id', function ($query) use ($requestedSectors, $finalSubSectors, $jamiatId) {
            $query->select('family_id')
                ->from('users')
                ->where('jamiat_id', $jamiatId)
                ->whereIn('sector_id', $requestedSectors)
                ->whereIn('sub_sector_id', $finalSubSectors);
        })
        ->where(function ($query) {
            $query->where('hub_amount', '>', 0)
                ->orWhere('thali_status', 'joint');
        })
        ->count();


        // Calculate pending hubs
        $hub_pending = $total_hof - $hub_done;

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Details fetched successfully',
            'data' => [
                'total_hof' => $total_hof,
                'hub_done' => $hub_done,
                'hub_pending' => $hub_pending,
            ]
        ]);
    }


    public function niyaz_stats(Request $request)
    {
        // Get authenticated user's jamiat_id
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $jamiatId = $user->jamiat_id;

        // Get the current year from `t_year` where `is_current = 1` for the user's jamiat
        $currentYear = YearModel::where('jamiat_id', $jamiatId)->where('is_current', 1)->value('year');

        if (!$currentYear) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'Current year not found for the user',
                'data' => [],
            ]);
        }

        // Validate sector and sub-sector input
        $request->validate([
            'sector' => 'required|array',
            'sector.*' => ['required', function ($attribute, $value, $fail) {
                if ($value !== 'all' && !is_numeric($value)) {
                    $fail("The $attribute field must be an integer or 'all'.");
                }
            }],
            'sub_sector' => 'required|array',
            'sub_sector.*' => ['required', function ($attribute, $value, $fail) {
                if ($value !== 'all' && !is_numeric($value)) {
                    $fail("The $attribute field must be an integer or 'all'.");
                }
            }],
        ]);

        // Handle "all" for sector and sub-sector
        $requestedSectors = $request->input('sector', []);
        if (in_array('all', $requestedSectors)) {
            $requestedSectors = DB::table('t_sector')->pluck('id')->toArray();
            $requestedSectors[] = null; // Include NULL values for sector_id
        }

        $requestedSubSectors = $request->input('sub_sector', []);
        if (in_array('all', $requestedSubSectors)) {
            $requestedSubSectors = DB::table('t_sub_sector')
                ->whereIn('sector_id', array_filter($requestedSectors)) // Fetch sub-sectors for the specified sectors
                ->pluck('id')
                ->toArray();
            $requestedSubSectors[] = null; // Include NULL values for sub_sector_id
        }

        // Fetch all hub_done entries where `hub_amount > 0`, filtered by sector & sub-sector
        $hubData = HubModel::where('year', $currentYear)
            ->where('jamiat_id', $jamiatId)
            ->where('hub_amount', '>', 0)
            ->whereIn('family_id', function ($query) use ($requestedSectors, $requestedSubSectors, $jamiatId) {
                $query->select('family_id')
                    ->from('users')
                    ->where('jamiat_id', $jamiatId)
                    ->whereIn('sector_id', $requestedSectors)
                    ->whereIn('sub_sector_id', $requestedSubSectors);
            })
            ->pluck('hub_amount')
            ->toArray();

        // Define slabs with amount thresholds
        $slabs = [
            "Full Niyaz" => 172000,
            "3/4 Niyaz" => 129000,
            "1/2 Niyaz" => 86000,
            "1/3 Niyaz" => 57500,
            "1/4 Niyaz" => 43000,
            "1/5 Niyaz" => 34500,
            "Hub Contribution" => 0, // Remaining values fall here
        ];

        // Initialize counts for each slab
        $slabCounts = array_fill_keys(array_keys($slabs), 0);
        $slabTotals = array_fill_keys(array_keys($slabs), 0);

        // Distribute hub amounts into slabs
        foreach ($hubData as $amount) {
            foreach ($slabs as $slab => $threshold) {
                if ($amount >= $threshold) {
                    $slabCounts[$slab]++;
                    $slabTotals[$slab] += $amount;
                    break; // Stop checking once assigned to a slab
                }
            }
        }

        // Prepare response data
        $responseData = [];
        foreach ($slabs as $slab => $amount) {
            $responseData[] = [
                'slab' => $slab,
                'amount' => (string) $amount,
                'count' => (string) $slabCounts[$slab],
                'total' => (string) $slabTotals[$slab],
            ];
        }

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Details fetched successfully',
            'data' => $responseData,
        ]);
    }

    
    
    public function mohalla_wise()
    {

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Details fetched successfully',
            'data' => [
                [
                    'mohalla' => "BURHANI",
                    'total_hof' => "350",
                    'done' => "200",
                    'pending' => "150",
                    'amount' => "100000",
                ],
                [
                    'mohalla' => "EZZY",
                    'total_hof' => "350",
                    'done' => "200",
                    'pending' => "150",
                    'amount' => "100000",
                ],
                [
                    'mohalla' => "SHUJAI",
                    'total_hof' => "350",
                    'done' => "200",
                    'pending' => "150",
                    'amount' => "100000",
                ],
                [
                    'mohalla' => "MOHAMMEDI",
                    'total_hof' => "350",
                    'done' => "200",
                    'pending' => "150",
                    'amount' => "100000",
                ],
                [
                    'mohalla' => "ZAINY",
                    'total_hof' => "350",
                    'done' => "200",
                    'pending' => "150",
                    'amount' => "100000",
                ],
            ],
        ]);    
    }
    
}
