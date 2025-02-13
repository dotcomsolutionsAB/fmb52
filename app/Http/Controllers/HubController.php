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


    public function niyaz_stats()
    {

        $total_hof = 850;
        $hub_done = 600;
        $hub_pending = 250;

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Details fetched successfully',
            'data' => [
                [
                    'slab' => "Full Niyaz",
                    'amount' => "172000",
                    'count' => "10",
                    'total' => "1720000",
                ],
                [
                    'slab' => "3/4 Niyaz",
                    'amount' => "129000",
                    'count' => "10",
                    'total' => "1290000",
                ],
                [
                    'slab' => "1/2 Niyaz",
                    'amount' => "86000",
                    'count' => "10",
                    'total' => "860000",
                ],
                [
                    'slab' => "1/3 Niyaz",
                    'amount' => "57500",
                    'count' => "10",
                    'total' => "575000",
                ],
                [
                    'slab' => "1/4 Niyaz",
                    'amount' => "43000",
                    'count' => "10",
                    'total' => "430000",
                ],
                [
                    'slab' => "1/5 Niyaz",
                    'amount' => "34500",
                    'count' => "10",
                    'total' => "345000",
                ],
                [
                    'slab' => "Hub Contribution",
                    'amount' => "0", // If '-' is meant to indicate no amount, use "0"
                    'count' => "10",
                    'total' => "45000",
                ]
            ],
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
