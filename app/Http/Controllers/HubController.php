<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\HubModel;
use App\Models\YearModel;
use App\Models\SectorModel;
use App\Models\SubSectorModel;


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
        $currentYear = YearModel::where('jamiat_id', $jamiatId)->where('is_current', "1")->value('year');

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

        $slab_id = 1;

        // Prepare response data
        $responseData = [];
        foreach ($slabs as $slab => $amount) {
            $responseData[] = [
                'slab_id' => $slab_id++,
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

    
    
    public function mohalla_wise(Request $request)
    {
        // Get authenticated user's jamiat_id
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $jamiatId = $user->jamiat_id;

        // Get the current year from `t_year` where `is_current = 1` for the user's jamiat
        $currentYear = YearModel::where('jamiat_id', $jamiatId)->where('is_current', "1")->value('year');

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

        // Fetch mohalla-wise data
       


$activeSectors = SectorModel::whereIn('id', $requestedSectors)
    ->where('status', 'active')
    ->pluck('id')
    ->toArray();

if (empty($activeSectors)) {
    return response()->json([], 200);  // Or some message: no active sectors found
}

// Step 2: Query users but only for active sectors
$users = User::select('sector_id', 'family_id', 'status')
    ->with(['hubs' => function ($query) use ($currentYear) {
        $query->where('year', $currentYear);
    }])
    ->where('jamiat_id', $jamiatId)
    ->whereIn('sector_id', $activeSectors)   // Use filtered active sectors only
    ->whereIn('sub_sector_id', $requestedSubSectors)
    ->get();

$groupedBySector = $users->groupBy('sector_id');

$responseData = $groupedBySector->map(function ($usersInSector, $sectorId) {
    $activeFamilies = $usersInSector->where('status', 'active')->unique('family_id');
    $allHubs = $usersInSector->unique('family_id')->pluck('hubs')->flatten();

    $total_hof = $activeFamilies->count();
    $done = $allHubs->where('hub_amount', '<=', 0)->unique('family_id')->count();
    $amount = $allHubs->where('hub_amount', '>', 0)->unique('family_id')->sum('hub_amount');

    $sectorName = SectorModel::where('id', $sectorId)->value('name') ?? 'Unknown';

    return [
        'sector_id' => $sectorId,
        'sector' => $sectorName,
        'total_hof' => (string) $total_hof,
        'done' => (string) $done,
        'pending' => (string) ($total_hof - $done),
        'amount' => (string) $amount,
    ];
})
->sortBy('sector')
->values();

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Details fetched successfully',
            'data' => $responseData,
        ]);
    }

    public function usersByNiyazSlab(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $jamiatId = $user->jamiat_id;

        // Validate input parameters
        $request->validate([
            'niyaz_slab' => 'required|integer|min:1|max:7',
            'year' => 'required|string|max:10',
            'limit' => 'sometimes|integer|min:1|max:100',
            'offset' => 'sometimes|integer|min:0'
        ]);

        $niyazSlab = $request->input('niyaz_slab');
        $year = $request->input('year');
        $limit = $request->input('limit', 10); // Default limit 10
        $offset = $request->input('offset', 0); // Default offset 0

        // Define slabs with amount thresholds
        $slabs = [
            1 => ['min' => 172000, 'max' => 1720000],  // Full Niyaz (>= 172000)
            2 => ['min' => 129000, 'max' => 172000],  // 3/4 Niyaz (129000 - 172000)
            3 => ['min' => 86000, 'max' => 129000],  // 1/2 Niyaz (86000 - 129000)
            4 => ['min' => 57500, 'max' => 86000],  // 1/3 Niyaz (57500 - 86000)
            5 => ['min' => 43000, 'max' => 57500],  // 1/4 Niyaz (43000 - 57500)
            6 => ['min' => 34500, 'max' => 43000],  // 1/5 Niyaz (34500 - 43000)
            7 => ['min' => 0, 'max' => 34500],      // Hub Contribution (0 - 34500)
        ];

        // Get the min and max amount range for the selected slab
        $selectedSlab = $slabs[$niyazSlab];

        // Fetch user family IDs that fall under the selected slab for the given year
        $filteredFamilies = HubModel::where('jamiat_id', $jamiatId)
            ->where('year', $year)
            ->where('hub_amount', '>=', $selectedSlab['min']);

        if ($selectedSlab['max'] !== null) {
            $filteredFamilies->where('hub_amount', '<', $selectedSlab['max']);
        }

        $filteredFamilies = $filteredFamilies->pluck('family_id')->toArray();

        if (empty($filteredFamilies)) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'No users found for the given slab.',
                'data' => [],
            ]);
        }

        // Fetch user details with pagination
        $users = User::select(
                'users.folio_no',
                'users.its',
                'upload.file_url as photo_url',
                'users.name',
                'sector.name as sector',
                'sub_sector.name as sub_sector',
                DB::raw("(SELECT hub_amount FROM t_hub WHERE t_hub.family_id = users.family_id AND t_hub.year = ('1446-1447') LIMIT 1) as this_year_hub"),
                DB::raw("(SELECT hub_amount FROM t_hub WHERE t_hub.family_id = users.family_id AND t_hub.year = ('1445-1446') LIMIT 1) as last_year_hub"),
                DB::raw("(SELECT SUM(due_amount) FROM t_hub WHERE t_hub.family_id = users.family_id AND t_hub.year < $year) as total_overdue")
            )
            ->leftJoin('t_sector as sector', 'users.sector_id', '=', 'sector.id')
            ->leftJoin('t_sub_sector as sub_sector', 'users.sub_sector_id', '=', 'sub_sector.id')
            ->leftJoin('t_uploads as upload', 'users.photo_id', '=', 'upload.id')
            ->whereIn('users.family_id', $filteredFamilies)
            ->where('users.jamiat_id', $jamiatId)
            ->where('users.mumeneen_type', 'HOF')
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Fetch total count for pagination
        $totalCount = User::whereIn('users.family_id', $filteredFamilies)
            ->where('users.jamiat_id', $jamiatId)
            ->where('users.mumeneen_type', 'HOF')
            ->count();

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Details fetched successfully',
            'data' => $users,
            'pagination' => [
                'total' => $totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ]);
    }

    public function usersBySector(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $jamiatId = $user->jamiat_id;

        // Validate input parameters
        $request->validate([
            // 'niyaz_slab' => 'required|integer|min:1|max:7',
            'year' => 'required|string|max:10',
            'limit' => 'sometimes|integer|min:1|max:100',
            'offset' => 'sometimes|integer|min:0'
        ]);

        $niyazSlab = 1;
        $year = $request->input('year');
        $limit = $request->input('limit', 10); // Default limit 10
        $offset = $request->input('offset', 0); // Default offset 0

        // Define slabs with amount thresholds
        $slabs = [
            1 => ['min' => 172000, 'max' => 1720000],  // Full Niyaz (>= 172000)
            2 => ['min' => 129000, 'max' => 172000],  // 3/4 Niyaz (129000 - 172000)
            3 => ['min' => 86000, 'max' => 129000],  // 1/2 Niyaz (86000 - 129000)
            4 => ['min' => 57500, 'max' => 86000],  // 1/3 Niyaz (57500 - 86000)
            5 => ['min' => 43000, 'max' => 57500],  // 1/4 Niyaz (43000 - 57500)
            6 => ['min' => 34500, 'max' => 43000],  // 1/5 Niyaz (34500 - 43000)
            7 => ['min' => 0, 'max' => 34500],      // Hub Contribution (0 - 34500)
        ];

        // Get the min and max amount range for the selected slab
        $selectedSlab = $slabs[$niyazSlab];

        // Fetch user family IDs that fall under the selected slab for the given year
        $filteredFamilies = HubModel::where('jamiat_id', $jamiatId)
            ->where('year', $year)
            ->where('hub_amount', '>=', $selectedSlab['min']);

        if ($selectedSlab['max'] !== null) {
            $filteredFamilies->where('hub_amount', '<', $selectedSlab['max']);
        }

        $filteredFamilies = $filteredFamilies->pluck('family_id')->toArray();

        if (empty($filteredFamilies)) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'No users found for the given slab.',
                'data' => [],
            ]);
        }

        // Fetch user details with pagination
        $users = User::select(
                'users.folio_no',
                'users.its',
                'upload.file_url as photo_url',
                'users.name',
                'sector.name as sector',
                'sub_sector.name as sub_sector',
                DB::raw("(SELECT hub_amount FROM t_hub WHERE t_hub.family_id = users.family_id AND t_hub.year = ('1446-1447') LIMIT 1) as this_year_hub"),
                DB::raw("(SELECT hub_amount FROM t_hub WHERE t_hub.family_id = users.family_id AND t_hub.year = ('1445-1446') LIMIT 1) as last_year_hub"),
                DB::raw("(SELECT SUM(due_amount) FROM t_hub WHERE t_hub.family_id = users.family_id AND t_hub.year < $year) as total_overdue")
            )
            ->leftJoin('t_sector as sector', 'users.sector_id', '=', 'sector.id')
            ->leftJoin('t_sub_sector as sub_sector', 'users.sub_sector_id', '=', 'sub_sector.id')
            ->leftJoin('t_uploads as upload', 'users.photo_id', '=', 'upload.id')
            ->whereIn('users.family_id', $filteredFamilies)
            ->where('users.jamiat_id', $jamiatId)
            ->where('users.mumeneen_type', 'HOF')
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Fetch total count for pagination
        $totalCount = User::whereIn('users.family_id', $filteredFamilies)
            ->where('users.jamiat_id', $jamiatId)
            ->where('users.mumeneen_type', 'HOF')
            ->count();

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Details fetched successfully',
            'data' => $users,
            'pagination' => [
                'total' => $totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ]);
    }

    public function get_hub_by_family($id)
    {
        // Fetch the hub data for the specific family ID
        $hub = HubModel::select('jamiat_id', 'family_id', 'year', 'hub_amount', 'paid_amount', 'due_amount', 'log_user')
            ->where('family_id', $id)
            ->orderBy('id', 'desc') // or 'year', depending on what defines the latest
            ->first();
    
        // Check if the hub record is found
        if ($hub) {
            // Retrieve sector and subsector information from the users table
            $user = User::select('sector_id', 'sub_sector_id','thali_status')
                ->where('family_id', $id)
                ->where('mumeneen_type', 'HOF')
                ->first();
    
            // Check if the user exists and sector & subsector ids are available
            if ($user) {
                // Get the sector and subsector names
                $sector = SectorModel::find($user->sector_id);
                $subSector = SubSectorModel::find($user->sub_sector_id);
    
                // Extract the Incharge name from the subsector notes column
                $incharge = $subSector ? $this->extractInchargeName($subSector->notes) : 'N/A';
    
                // Add the sector and subsector names and incharge (masool) to the response data
                $hubData = $hub->toArray();
                $hubData['masool'] = $incharge; // Add the extracted Incharge name as masool
                $hubData['sector_name'] = $sector ? $sector->name : 'N/A';
                $hubData['subsector_name'] = $subSector ? $subSector->name : 'N/A';
                $hubData['thali_status'] = $user? $user->thali_status:"N/A";
    
                return response()->json([
                    'message' => 'Hub record fetched successfully!',
                    'data' => $hubData
                ], 200);
            } else {
                return response()->json(['message' => 'No user found with mumeneen_type HOF for this family!'], 404);
            }
        }
    
        // If no hub record found
        return response()->json(['message' => 'No hub record found for this family!'], 404);
    }
    
    // Helper function to extract Incharge name from the 'notes' column
    public function extractInchargeName($notes)
    {
        // Use regular expression to extract the "Incharge: <Name>" part from the string
        preg_match('/Incharge:\s*([^,]+)/', $notes, $matches);
        
        // Return the extracted name or 'N/A' if not found
        return $matches[1] ?? 'N/A';
    }

    
}
