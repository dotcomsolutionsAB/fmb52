<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ItsModel;
use App\Models\SectorModel;
use App\Models\SubSectorModel;
use App\Models\BuildingModel;
use App\Models\YearModel;
use App\Models\MenuModel;
use App\Models\FcmModel;
use App\Models\HubModel;
use App\Models\ThaaliStatus;
use App\Models\ZabihatModel;
use App\Http\Controllers\UploadController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;
use Illuminate\Foundation\Auth\Access\AuthorizesResourcesTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesResourcesWithBasicAuth;
use Illuminate\Foundation\Auth\Access\AuthorizesResourcesWithClientCredentials;

use Auth;
use League\Csv\Reader;

use Hash;

class MumeneenController extends Controller
{
    public function __construct()
    {
    }

    public function migrate()
    {
        die("Maintenance Mode");
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Temporarily disable foreign key checks for performance

        // Step 1: Delete existing data
        // User::where('role', 'mumeneen')->where('jamiat_id', 1)->delete();
        // BuildingModel::where('jamiat_id', 1)->delete();
        // HubModel::where('jamiat_id', 1)->delete();

        // API Endpoint
        $url = 'https://www.faizkolkata.com/assets/custom/migrate/laravel/mumeneen.php';

        // Fetch data from API (All at once)
        try {
            $response = Http::timeout(300)->retry(3, 500)->get($url);
        } catch (\Exception $e) {
            Log::error("API request failed: " . $e->getMessage());
            return response()->json(['message' => "API request failed."], 500);
        }

        if ($response->failed()) {
            Log::error("API failure.");
            return response()->json(['message' => "API failure."], 500);
        }

        // Get families data
        $families = $response->json() ?? [];
        if (empty($families)) {
            return response()->json(['message' => "No data received from API."], 200);
        }

        // Fetch sector and sub-sector mappings once
        $sectors = SectorModel::pluck('id', 'name')->toArray();
        $subSectors = SubSectorModel::pluck('id', 'name')->toArray();

        // Fetch existing ITS numbers to avoid duplicates
        $existingITS = User::pluck('its')->toArray();

        $users = [];
        $buildings = [];
        $hubs = [];

        foreach ($families as $family) {
            $buildingId = null;
            $address = $family['address'] ?? [];

            // Prepare building data
            if (!empty($address['address_2'])) {
                $buildings[] = [
                    'jamiat_id' => 1,
                    'name' => $address['address_2'],
                    'address_lime_1' => $address['address_1'] ?? null,
                    'address_lime_2' => $address['address_2'] ?? null,
                    'city' => $address['city'] ?? null,
                    'pincode' => $address['pincode'] ?? null,
                    'lattitude' => $address['latitude'] ?? null,
                    'longtitude' => $address['longitude'] ?? null,
                ];
                $buildingId = count($buildings);
            }

            // Prepare users data
            foreach ($family['members'] as $member) {
                $its = substr($member['its'], 0, 8);

                // Skip duplicate ITS numbers
                if (in_array($its, $existingITS)) {
                    Log::warning("Skipping duplicate ITS: {$its}");
                    continue;
                }

                $users[] = [
                    'username' => $its,
                    'name' => $member['name'],
                    'email' => $member['email'],
                    'password' => bcrypt('default_password'),
                    'jamiat_id' => 1,
                    'family_id' => $family['family_id'],
                    'title' => in_array(strtolower($member['title']), ['shaikh', 'mulla']) ? ucfirst(strtolower($member['title'])) : null,
                    'its' => $its,
                    'hof_its' => $member['hof_id'],
                    'its_family_id' => $member['family_its_id'],
                    'mumeneen_type' => $member['type'],
                    'mobile' => (strlen($member['mobile']) <= 15) ? $member['mobile'] : null,
                    'gender' => in_array(strtolower($member['gender']), ['male', 'female']) ? strtolower($member['gender']) : null,
                    'folio_no' => $family['folio_no'],
                    'sector_id' => $sectors[strtoupper($family['sector'])] ?? null,
                    'sub_sector_id' => $subSectors[strtoupper($family['sub_sector'])] ?? null,
                    'status' => $family['status'] == 1 ? 'in_active' : 'active',
                    'role' => 'mumeneen',
                    'building' => $buildingId,
                ];

                // Add to existing ITS list to prevent further duplication
                $existingITS[] = $its;
            }

            // Mapping Thali Status
            $thaliMapping = [
                1 => 'taking',
                2 => 'not_taking',
                3 => 'once_a_week',
                9 => 'joint',
                0 => 'other_centre'
            ];

            $thaliStatus = $thaliMapping[$family['is_taking_thali']] ?? null;


            // Prepare hub data
            foreach ($family['hub_array'] as $hubEntry) {
                $hubs[] = [
                    'family_id' => $family['family_id'],
                    'year' => $hubEntry['year'],
                    'jamiat_id' => 1,
                    'thali_status' => $thaliStatus,
                    'hub_amount' => is_numeric($hubEntry['hub']) ? $hubEntry['hub'] : 0,
                    'paid_amount' => 0,
                    'due_amount' => is_numeric($hubEntry['hub']) ? $hubEntry['hub'] : 0,
                    'log_user' => 'system_migration'
                ];
            }
        }

        // Bulk insert using transactions with error handling
        DB::transaction(function () use ($users, $buildings, $hubs) {
            try {
                if (!empty($buildings)) BuildingModel::insert($buildings);
                if (!empty($users)) User::insert($users); // Will skip duplicates automatically
                if (!empty($hubs)) HubModel::insert($hubs);
            } catch (\Exception $e) {
                Log::error("Batch Insert Failed: " . $e->getMessage());
            }
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Re-enable foreign key checks

        Log::info("Migration completed successfully. Users Inserted: " . count($users));

        return response()->json([
            'message' => "Migration completed successfully.",
            'total_users' => count($users),
            'total_buildings' => count($buildings),
            'total_hubs' => count($hubs)
        ]);
    }

    //register user
    public function register_users(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'jamiat_id' => 'required|integer',
            'family_id' => 'required|string|max:10',
            'its' => [
                'required',
                'string',
                'max:8',
                Rule::unique('users')->where(function ($query) use ($request) {
                    return $query->where('jamiat_id', $request->jamiat_id)
                                ->where('role', 'mumeneen');
                })
            ],
            'hof_its' => 'required|string|max:8',
            'its_family_id' => 'nullable|string|max:10',
            'mobile' => ['required', 'string', 'min:12', 'max:20'],
            'gender' => 'required|in:male,female',
            'title' => 'nullable|in:Shaikh,Mulla',
            'folio_no' => 'nullable|string|max:20',
            'sector_id' => 'nullable|integer',
            'sub_sector_id' => 'nullable|integer',
            'building' => 'nullable|integer',
            'age' => 'nullable|integer',
            'role' => 'required|in:superadmin,jamiat_admin,mumeneen',
            'status' => 'required|in:active,inactive',
            'username' => 'required|string',
        ]);
    
        $register_user = User::create([
            'name' => $request->input('name'),
            'email' => strtolower($request->input('email')),
            'password' => bcrypt($request->input('password')),
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'its' => $request->input('its'),
            'hof_its' => $request->input('hof_its'),
            'its_family_id' => $request->input('its_family_id'),
            'mobile' => $request->input('mobile'),
            'title' => $request->input('title'),
            'gender' => $request->input('gender'),
            'age' => $request->input('age'),
            'building' => $request->input('building'),
            'folio_no' => $request->input('folio_no'),
            'sector_id' => $request->input('sector_id'), // Updated field
            'sub_sector_id' => $request->input('sub_sector_id'), // Updated field
            'role' => $request->input('role'),
            'status' => $request->input('status'),
            'username' => $request->input('username'),
        ]);
    
        unset($register_user['id'], $register_user['created_at'], $register_user['updated_at']);
    
        return isset($register_user) && $register_user !== null
            ? response()->json(['User created successfully!', 'data' => $register_user], 201)
            : response()->json(['Failed to create successfully!'], 400);
    }

    // view
    public function users()
    {
        $jamiat_id = Auth::user()->jamiat_id;
        $get_all_users = User::select('id', 'name', 'email', 'jamiat_id', 'family_id', 'mobile', 'its', 'hof_its', 'its_family_id', 'folio_no', 'mumeneen_type', 'title', 'gender', 'age', 'building', 'sector', 'sub_sector', 'status', 'role', 'username', 'photo_id')
        ->with(['photo:id,file_url'])
        ->where('jamiat_id', $jamiat_id)
        ->get();

        // Transform the users to include `file_url` in the main array
        $transformed_users = $get_all_users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'jamiat_id' => $user->jamiat_id,
                'family_id' => $user->family_id,
                'mobile' => $user->mobile,
                'its' => $user->its,
                'hof_its' => $user->hof_its,
                'its_family_id' => $user->its_family_id,
                'folio_no' => $user->folio_no,
                'mumeneen_type' => $user->mumeneen_type,
                'title' => $user->title,
                'gender' => $user->gender,
                'age' => $user->age,
                'building' => $user->building,
                'sector' => $user->sector,
                'sub_sector' => $user->sub_sector,
                'status' => $user->status,
                'role' => $user->role,
                'username' => $user->username,
                'file_url' => $user->photo ? $user->photo->file_url : null, // Add `file_url` from the photo relationship
            ];
        });
    
        return isset($transformed_users) && $transformed_users->isNotEmpty()
            ? response()->json(['User Fetched Successfully!', 'data' => $transformed_users], 200)
            : response()->json(['Sorry, failed to fetched records!'], 404);
    }
    
    public function usersWithHubData(Request $request, $year = 0)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $jamiat_id = $user->jamiat_id;

        // Determine the year
        if ($year !== 0) {
            $yearRecord = YearModel::where('jamiat_id', $jamiat_id)->where('id', $year)->first();
            $year = $yearRecord->year ?? date('Y');
        } else {
            $currentYearRecord = YearModel::where('jamiat_id', $jamiat_id)->where('is_current', '1')->first();
            $year = $currentYearRecord->year ?? date('Y');
        }

        // Fetch sub-sector permissions from authenticated user
        $permittedSubSectorIds = $user->sub_sector_access_id ?? [];

        // Ensure sub-sector access IDs are an array
        if (!is_array($permittedSubSectorIds)) {
            $permittedSubSectorIds = json_decode($permittedSubSectorIds, true) ?? [];
        }

        // Validation
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

        // Fetch hub data for the specified year including thali_status
        $hub_data = HubModel::select('family_id', 'hub_amount', 'paid_amount', 'due_amount', 'thali_status', 'year')
            ->where('jamiat_id', $jamiat_id)
            ->where('year', $year)
            ->get()
            ->keyBy('family_id');

        // Get all users with permitted sub-sectors and filter out those without hub data
        $get_all_users = User::select(
            'id', 'name', 'email', 'jamiat_id', 'family_id', 'mobile', 'its', 'hof_its',
            'its_family_id', 'folio_no', 'label', 'mumeneen_type', 'title', 'gender', 'age',
            'building', 'sector_id', 'sub_sector_id', 'status', 'role', 'username', 'photo_id','thali_status'
        )
        ->with([
            'photo:id,file_url', // Existing relationship
            'sector:id,name',     // Eager load sector name
            'subSector:id,name'   // Eager load sub-sector name
        ])
        ->where('jamiat_id', $jamiat_id)
        ->where('status', 'active')
        ->where('role', 'mumeneen')
        ->where(function ($query) use ($requestedSectors) {
            $query->whereIn('sector_id', $requestedSectors)
                ->orWhereNull('sector_id'); // Include NULL sector_id
        })
        ->where(function ($query) use ($finalSubSectors) {
            $query->whereIn('sub_sector_id', $finalSubSectors)
                ->orWhereNull('sub_sector_id'); // Include NULL sub_sector_id
        })
        ->whereIn('family_id', $hub_data->keys()->toArray()) // Filter users with hub records
        ->orderByRaw("sub_sector_id IS NULL OR sub_sector_id = ''") 
        ->orderBy('sub_sector_id') 
        ->orderBy('folio_no') 
        ->get();

        if ($get_all_users->isNotEmpty()) {
            $family_ids = $get_all_users->pluck('family_id')->toArray();

            // Calculate overdue amounts
            $previous_years = YearModel::where('jamiat_id', $jamiat_id)
                ->where('year', '<', $year)
                ->pluck('year');

            $overdue_data = HubModel::select('family_id', DB::raw('SUM(due_amount) as overdue'))
                ->whereIn('family_id', $family_ids)
                ->where('jamiat_id', $jamiat_id)
                ->whereIn('year', $previous_years)
                ->groupBy('family_id')
                ->get()
                ->keyBy('family_id');

            // Map hub data and overdue amounts to users
            $users_with_hub_data = $get_all_users->map(function ($user) use ($hub_data, $overdue_data) {
                $hub_record = $hub_data->get($user->family_id);

                if ($user->mumeneen_type === 'FM') {
                    // Set hub amounts to 0 for FM users
                    $user->hub_amount = 0;
                    $user->paid_amount = 0;
                    $user->due_amount = 0;
                } else {
                    $user->hub_amount = $hub_record->hub_amount ?? 'NA';
                    $user->paid_amount = $hub_record->paid_amount ?? 'NA';
                    $user->due_amount = $hub_record->due_amount ?? 'NA';
                }

                //$user->thali_status = $hub_record->thali_status ?? 'NA'; // Assign thali_status

                $overdue_record = $overdue_data->get($user->family_id);
                $user->overdue = $overdue_record->overdue ?? 0;

                return $user;
            });

            return response()->json(['message' => 'User Fetched Successfully!', 'data' => $users_with_hub_data], 200);
        }

        return response()->json(['message' => 'Sorry, failed  fetch records!'], 404);
    }


    // dashboard
   public function get_user($id)
{
    // Fetch the user records where mumeneen_type is HOF and family_id matches
    $get_user_records = User::select(
        'id', 'name', 'email', 'jamiat_id', 'family_id', 'mobile', 'its', 'hof_its', 'its_family_id', 'folio_no', 
        'mumeneen_type', 'title', 'gender', 'age', 'building', 'sector_id', 'sub_sector_id', 'status', 
        'role', 'username', 'photo_id'
    )
    ->where('family_id', $id)
    ->where('mumeneen_type', "HOF")
    ->with(['photo:id,file_url'])
    ->first(); // Assuming you're getting a single record based on family_id

    // Check if the user exists
    if ($get_user_records) {
        // Get sector and sub-sector names
        $sector = DB::table('t_sector')
                    ->where('id', $get_user_records->sector_id)
                    ->value('name');

       $sub_sector = DB::table('t_sub_sector')
    ->where('id', $get_user_records->sub_sector_id)
    ->first(['name', 'notes']);  // Retrieve the full record (name and notes)

                       

        // Extract in-charge details from the string
        $inchargeDetails = $this->extractInchargeDetails($sub_sector->notes);

        // Add sector, sub-sector names, and in-charge details to the user data
        $get_user_records->sector_name = $sector;
        $get_user_records->sub_sector_name = $sub_sector->name;
        $get_user_records->incharge_name = $inchargeDetails['name'] ?? null;
        $get_user_records->incharge_mobile = $inchargeDetails['mobile'] ?? null;

        // Return the user data with sector and sub-sector names, and in-charge info
        return response()->json(
            [
                'code'=>200,
                'status'=>true,
                'message' => 'User Record Fetched Successfully!', 
                'data' => $get_user_records],
            200,
            [],
            JSON_UNESCAPED_SLASHES
        );
    } else {
        return response()->json(['code'=>404,
                'status'=>false,'message' => 'Sorry failed to fetch records!'], 404);
    }
}

/**
 * Extract incharge details from the provided string.
 * Assumes format: "Incharge: Name, Folio: X, Mobile: XXXXXXXXXX, Email: X"
 */
private function extractInchargeDetails($building)
{
    // Check if 'Incharge' is present in the string
    if (strpos($building, 'Incharge:') !== false) {
        // Use regex to capture the incharge name and mobile number
        preg_match('/Incharge: (.*?), Folio:/', $building, $nameMatch);
        preg_match('/Mobile: (\d{10})/', $building, $mobileMatch);

        return [
            'name' => $nameMatch[1] ?? null,  // Incharge name
            'mobile' => $mobileMatch[1] ?? null // Incharge mobile number
        ];
    }

    return [];
}

public function update_user_details(Request $request, $id)
{
    // Fetch the record by ID
    $get_user = User::find($id);

    // Check if the record exists
    if (!$get_user) {
        return response()->json([
            'code' => 404,
            'status' => 'error',
            'message' => 'Record not found!',
            'data' => null
        ], 404);
    }

    // Define validation rules - only for hof_its, mobile, and email
    $rules = [
        'name' => 'sometimes|string',  // If you want to update the name as well
        'email' => 'sometimes|email|unique:users,email,' . $id,
        'mobile' => ['sometimes', 'string', 'min:12', 'max:20'],  // Mobile validation
    ];

    // Validate only the fields present in the request
    $validatedData = $request->validate($rules);

    // Update only the fields provided in the request
    if (isset($validatedData['password'])) {
        $validatedData['password'] = bcrypt($validatedData['password']);
    }

    // Update the specific fields (name, mobile, and email) if provided in the request
    $updated = $get_user->update($validatedData);

    // Return appropriate response
    return $updated
        ? response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => 'Record updated successfully!',
            'data' => $get_user
        ], 200)
        : response()->json([
            'code' => 304,
            'status' => 'info',
            'message' => 'No changes detected',
            'data' => null
        ], 304);
}
    // update
    public function update_record(Request $request, $id)
    {
        // Fetch the record by ID
        $get_user = User::find($id);
    
        // Check if the record exists
        if (!$get_user) {
            return response()->json([
                'message' => 'Record not found!',
            ], 404);
        }
    
        // Define validation rules
        $rules = [
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string',
            'family_id' => 'sometimes|string|max:10',
            'hof_its' => 'sometimes|string|max:8',
            'its_family_id' => 'sometimes|nullable|string|max:10',
            'mobile' => ['sometimes', 'string', 'min:12', 'max:20'],
            'gender' => 'sometimes|in:male,female',
            'title' => 'sometimes|nullable|in:Shaikh,Mulla',
            'folio_no' => 'sometimes|nullable|string|max:20',
            'sector' => 'sometimes|nullable|string|max:100',
            'sub_sector' => 'sometimes|nullable|string|max:100',
            'building' => 'sometimes|nullable|integer',
            'age' => 'sometimes|nullable|integer',
            'role' => 'sometimes|required|in:superadmin,jamiat_admin,mumeneen',
            'status' => 'sometimes|required|in:active,inactive',
        ];
    
        // Validate only the fields present in the request
        $validatedData = $request->validate($rules);
    
        // Encrypt the password if provided
        if (isset($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        }
    
        // Update only the fields provided in the request
        $updated = $get_user->update($validatedData);
    
        // Return appropriate response
        return $updated
            ? response()->json(['message' => 'Record updated successfully!', 'data' => $get_user], 200)
            : response()->json(['message' => 'No changes detected'], 304);
    }

    // split family
    public function split_family(Request $request)
    {
        $request->validate([
            'members' => 'required|array|min:1', // Array of member IDs who are leaving
            'members.*' => 'exists:users,id',
            'new_head_id' => 'required|integer|exists:users,id', // Specify which member will be the new head
        ]);

        $members = User::whereIn('id', $request->members)->get();

        if($members->isEmpty())
        {
            return response()->json(['message' => 'No members found!'], 404);
        }

        // Check if the specified head is among the leaving members
        $newHead = $members->firstWhere('id', $request->new_head_id);
        
        if(!$newHead)
        {
            return response()->json(['message' => 'The specified head is not among the leaving members!'], 400);
        }

        // Generate a new unique family_id
            $newFamilyId = generateUniqueFamilyId();
        
        // Update the new head with the new family_id and role
        $newHead->family_id = $newFamilyId;
        $newHead->mumeneen_type	 = 'HOF';
        $newHead->save();
        
        // Update all other members as 'family_member' and assign the new family_id
        $members->each(function ($members) use ($newFamilyId, $newHead)
        {
            $members->family_id = $newFamilyId;
            $members->mumeneen_type = $members->id === $newHead->id ? 'HOF' : 'FM';
            $members->save();
        });

        unset($newHead['created_at'], $newHead['updated_at']);

        return response()->json([
            'message' => 'Family reassigned successfully!',
            'new_family_id' => $newFamilyId,
            'new_head' => $newHead,
        ], 200);
    }

    // merge family
    public function merge_family(Request $request)
    {
        $request->validate([
            'family_id' => 'required|string|exists:users,family_id', // Existing family ID
            'new_members' => 'required|array|min:1', // Array of new member IDs
            'new_members.*' => 'exists:users,id',
            'new_head_id' => 'nullable|integer|exists:users,id', // ID of the new member to be potentially assigned as head
        ]);

        // Fetch the existing family members
        $existingFamilyMembers = User::where('family_id', $request->family_id)->get();

        // Check if there's already an existing head of the family
        $existingHead = $existingFamilyMembers->firstWhere('mumeneen_type', 'HOF');

        // Fetch new members trying to join
        $newMembers = User::whereIn('id', $request->new_members)->get();

        if ($newMembers->isEmpty()) {
            return response()->json(['message' => 'No new members found!'], 404);
        }

        // Determine the new head
        $newHead = null;
        if ($request->new_head_id) {
            // If a new head ID is provided, ensure it is among the new members
            $newHead = $newMembers->firstWhere('id', $request->new_head_id);
            if (!$newHead) {
                return response()->json(['message' => 'The specified new head is not among the new members!'], 400);
            }

            // Update all existing family members to be 'FM' (Family Members)
            $existingFamilyMembers->each(function ($member) {
                $member->mumeneen_type = 'FM';
                $member->save();
            });

            // Set the new head's role as 'HOF' and assign the family ID
            $newHead->family_id = $request->family_id;
            $newHead->mumeneen_type = 'HOF';
            $newHead->save();
        } else {
            // If no new head ID is provided, keep the existing head
            $newHead = $existingHead;
        }

        // Ensure the existing head remains if no new head is assigned
        if (!$newHead) {
            return response()->json(['message' => 'No head of the family specified or found!'], 400);
        }

        // Update all new members as 'FM' (Family Members) and assign the existing family ID
        $newMembers->each(function ($member) use ($request, $newHead) {
            $member->family_id = $request->family_id;
            $member->mumeneen_type = ($member->id === $newHead->id) ? 'HOF' : 'FM';
            $member->save();
        });

        unset($newHead['created_at'], $newHead['updated_at']);

        return response()->json([
            'message' => 'New members added to the family successfully!',
            'family_id' => $request->family_id,
            'head_of_family' => $newHead,
        ], 200);
    }

    // delete
    public function delete_user($id)
    {
        // Delete the fabrication
        $delete_user = User::where('id', $id)->delete();

        // Return success response if deletion was successful
        return $delete_user
        ? response()->json(['message' => 'Delete User Record successfully!'], 200)
        : response()->json(['message' => 'Sorry, Record not found'], 404);
    }

    // create
    public function register_its(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'hof_its' => 'required|integer',
            'its_family_id' => 'required|integer',
            'name' => 'required|string',
            'email' => 'required|unique:t_its_data,email',
            'mobile' => ['required', 'string', 'min:12', 'max:20', 'unique:t_its_data,mobile'],
            'title' => 'nullable|in:Shaikh,Mulla',
            'mumeneen_type' => 'required|in:HOF,FM',
            'gender' => 'required|in:male,female',
            'age' => 'nullable|integer',
            'sector' => 'nullable|integer',
            'sub_sector' => 'nullable|integer',
            'name_arabic' => 'nullable|string',
        ]);

        $register_its = ItsModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'hof_its' => $request->input('hof_its'),
            'its_family_id' => $request->input('its_family_id'),
            'name' => $request->input('name'),
            'email' => strtolower($request->input('email')),
            'mobile' => $request->input('mobile'),
            'title' => $request->input('title'),
            'mumeneen_type' => $request->input('mumeneen_type'),
            'gender' => $request->input('gender'),
            'age' => $request->input('age'),
            'sector' => $request->input('sector'),
            'sub_sector' => $request->input('sub_sector'),
            'name_arabic' => $request->input('name_arabic'),
        ]);

        unset($register_its['id'], $register_its['created_at'], $register_its['updated_at']);

        return $register_its
            ? response()->json(['message' => 'Its registered successfully!', 'data' => $register_its], 201)
            : response()->json(['message' => 'Failed to register Its!'], 400);
    }

    // view
    public function all_its()
    {
        $get_all_mumeneens = ItsModel::select('jamiat_id', 'hof_its', 'its_family_id', 'name', 'email', 'mobile', 'title', 'mumeneen_type', 'gender', 'age', 'sector', 'sub_sector', 'name_arabic')->get();

        return $get_all_mumeneens->isNotEmpty()
            ? response()->json(['message' => 'Mumeneen records fetched successfully!', 'data' => $get_all_mumeneens], 200)
            : response()->json(['message' => 'No Mumeneen records found!'], 404);
    }

    // update
    public function update_its(Request $request, $id)
    {
        $get_its = ItsModel::find($id);

        if (!$get_its) {
            return response()->json(['message' => 'Mumeneen record not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'hof_its' => 'required|integer',
            'its_family_id' => 'required|integer',
            'name' => 'required|string',
            // 'email' => 'required|unique:mumeneens,email,'.$id, // Ignore the current record's email during validation
            'email' => 'required',
            // 'mobile' => ['required', 'string', 'min:12', 'max:20', 'unique:mumeneens,mobile,'.$id], // Ignore current mobile
            'mobile' => ['required', 'string', 'min:12', 'max:20'],
            'title' => 'nullable|in:Shaikh,Mulla',
            'mumeneen_type' => 'required|in:HOF,FM',
            'gender' => 'required|in:male,female',
            'age' => 'nullable|integer',
            'sector' => 'nullable|integer',
            'sub_sector' => 'nullable|integer',
            'name_arabic' => 'nullable|string',
        ]);

        $update_its_record = $get_its->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'hof_its' => $request->input('hof_its'),
            'its_family_id' => $request->input('its_family_id'),
            'name' => $request->input('name'),
            'email' => strtolower($request->input('email')),
            'mobile' => $request->input('mobile'),
            'title' => $request->input('title'),
            'mumeneen_type' => $request->input('mumeneen_type'),
            'gender' => $request->input('gender'),
            'age' => $request->input('age'),
            'sector' => $request->input('sector'),
            'sub_sector' => $request->input('sub_sector'),
            'name_arabic' => $request->input('name_arabic'),
        ]);

        return ($update_its_record == 1)
            ? response()->json(['message' => 'Its record updated successfully!', 'data' => $update_its_record], 200)
            : response()->json(['message' => 'No changes detected!'], 304);
    }

    // delete
    public function delete_its($id)
    {
        $delete_its = ItsModel::where('id', $id)->delete();

        return $delete_its
            ? response()->json(['message' => 'Its record deleted successfully!'], 200)
            : response()->json(['message' => 'Its record not found!'], 404);
    }

    // create
    public function register_sector(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:100',
            'notes' => 'nullable|string',
            'log_user' => 'required|string|max:100',
        ]);

        $register_sector = SectorModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'notes' => $request->input('notes'),
            'log_user' => $request->input('log_user'),
        ]);

        unset($register_sector['id'], $register_sector['created_at'], $register_sector['updated_at']);

        return $register_sector
            ? response()->json(['message' => 'sector created successfully!', 'data' => $register_sector], 201)
            : response()->json(['message' => 'Failed to create sector!'], 400);
    }

    // view
    public function all_sector(Request $request)
    {
        $user = Auth::user();
    
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
    
        // Fetch the user's accessible sub-sectors from the `sub_sector_access_id` column
        $userSubSectorAccess = json_decode($user->sub_sector_access_id, true); // Get user's accessible sub-sectors as an array
    
        if (empty($userSubSectorAccess)) {
            return response()->json([
                'message' => 'No access to any sectors or sub-sectors.',
            ], 403);
        }
    
        // Fetch all sector IDs linked to the user's accessible sub-sectors
        $permittedSectorIds = \DB::table('t_sub_sector')
            ->whereIn('id', $userSubSectorAccess)
            ->distinct()
            ->pluck('sector_id')
            ->toArray();
    
        if (empty($permittedSectorIds)) {
            return response()->json([
                'message' => 'No sectors linked to the accessible sub-sectors.',
            ], 404);
        }
    
        // Fetch sector details based on permitted sector IDs
        $get_all_sector = \DB::table('t_sector')
            ->whereIn('id', $permittedSectorIds)
            ->where('status','active')
            ->select('id', 'jamiat_id', 'name', 'log_user')
            ->get();
    
        return $get_all_sector->isNotEmpty()
            ? response()->json([
                'message' => 'Sector records fetched successfully!',
                'data' => $get_all_sector,
            ], 200)
            : response()->json([
                'message' => 'No Sector records found or you do not have access to any sectors!',
            ], 404);
    }
    // update
    public function update_sector(Request $request, $id)
    {
        $get_sector = SectorModel::find($id);

        if (!$get_sector) {
            return response()->json(['message' => 'Sector record not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:100',
            'notes' => 'nullable|string',
            'log_user' => 'required|string|max:100',
        ]);

        $update_sector_record = $get_sector->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'notes' => $request->input('notes'),
            'log_user' => $request->input('log_user'),
        ]);

        return ($update_sector_record == 1)
            ? response()->json(['message' => 'Sector record updated successfully!', 'data' => $update_sector_record], 200)
            : response()->json(['No changes detected!'], 304);
    }

    // delete
    public function delete_sector($id)
    {
        $delete_sector = SectorModel::where('id', $id)->delete();

        return $delete_sector
            ? response()->json(['message' => 'Sector record deleted successfully!'], 200)
            : response()->json(['message' => 'Sector record not found!'], 404);
    }

    // create
    public function register_sub_sector(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'sector' => 'required|integer',
            'name' => 'required|string|max:100',
            'notes' => 'nullable|string',
            'log_user' => 'required|string|max:100',
        ]);

        $register_sub_sector = SubSectorModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'sector' => $request->input('sector'),
            'name' => $request->input('name'),
            'notes' => $request->input('notes'),
            'log_user' => $request->input('log_user'),
        ]);

        unset($register_sub_sector['id'], $register_sub_sector['created_at'], $register_sub_sector['updated_at']);

        return $register_sub_sector
            ? response()->json(['message' => 'Sub-Sector created successfully!', 'data' => $register_sub_sector], 201)
            : response()->json(['message' => 'Failed to create sub-sector!'], 400);
    }

    // view
    public function all_sub_sector()
    {
        $user = Auth::user();
    
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
    
        // Get accessible sector IDs from the user's sector_access_id field
        $permittedSectorIds = json_decode($user->sub_sector_access_id, true);
    
        if (empty($permittedSectorIds)) {
            return response()->json(['message' => 'No access to any sectors.'], 403);
        }
    
        // Fetch sub-sectors within the permitted sectors
        $get_all_sub_sector = SubSectorModel::select(
            't_sub_sector.id', 
            't_sub_sector.sector_id', 
            't_sub_sector.name as sub_sector_name', 
            't_sub_sector.notes', 
            't_sub_sector.log_user', 
            't_sector.name as sector_name'
        )
        ->join('t_sector', 't_sector.id', '=', 't_sub_sector.sector_id') // Join with t_sector table
        ->whereIn('t_sub_sector.sector_id', $permittedSectorIds)
        ->get();
    
        return $get_all_sub_sector->isNotEmpty()
            ? response()->json([
                'message' => 'Sub-Sector records fetched successfully!',
                'data' => $get_all_sub_sector,
            ], 200)
            : response()->json(['message' => 'No sub-sector records found or you do not have access to any sub-sectors!'], 404);
    }
    public function getSubSectorsBySector($sector)
    {
        $user = Auth::user();
    
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
    
        // Get accessible sector IDs from the user's sector_access_id field
        $permittedSectorIds = json_decode($user->sub_sector_access_id, true);
    
        if (empty($permittedSectorIds)) {
            return response()->json(['message' => 'No access to any sectors.'], 403);
        }
    
        // Check if the requested sector is within the user's permitted sectors
        if (!in_array($sector, $permittedSectorIds)) {
            return response()->json([
                'message' => 'Access denied for the requested sector!',
                'sector' => $sector
            ], 403);
        }
    
        // Fetch sub-sectors within the permitted sector
        $subSectors = SubSectorModel::select(
            't_sub_sector.id', 
            't_sub_sector.jamiat_id', 
            't_sub_sector.name as sub_sector_name', 
            't_sub_sector.notes', 
            't_sub_sector.log_user', 
            't_sector.name as sector_name'
        )
        ->join('t_sector', 't_sector.id', '=', 't_sub_sector.sector_id') // Join with t_sector table
        ->where('t_sub_sector.sector_id', $sector)
         ->where('t_sector.status', 'active') 
        ->get();
    
        return $subSectors->isNotEmpty()
            ? response()->json([
                'message' => 'Sub-Sectors for the given sector fetched successfully!',
                'data' => $subSectors
            ], 200)
            : response()->json([
                'message' => 'No sub-sectors found for the given sector!',
                'sector' => $sector
            ], 404);
    }

    // update
    public function update_sub_sector(Request $request, $id)
    {
        $get_sub_sector = SubSectorModel::find($id);

        if (!$get_sub_sector) {
            return response()->json(['message' => 'Sub-Sector record not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'sector' => 'required|integer',
            'name' => 'required|string|max:100',
            'notes' => 'nullable|string',
            'log_user' => 'required|string|max:100',
        ]);

        $update_sub_sector_record = $get_sub_sector->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'sector' => $request->input('sector'),
            'name' => $request->input('name'),
            'notes' => $request->input('notes'),
            'log_user' => $request->input('log_user'),
        ]);

        return ($update_sub_sector_record == 1)
            ? response()->json(['message' => 'Sub-Sector record updated successfully!', 'data' => $update_sub_sector_record], 200)
            : response()->json(['No changes detected!'], 304);
    }

    // delete
    public function delete_sub_sector($id)
    {
        $delete_sub_sector = SubSectorModel::where('id', $id)->delete();

        return $delete_sub_sector
            ? response()->json(['message' => 'Sub-Sector record deleted successfully!'], 200)
            : response()->json(['message' => 'Sub-Sector record not found!'], 404);
    }

    // create
    public function register_building(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'address_lime_1' => 'nullable|string|max:255',
            'address_lime_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'lattitude' => 'nullable|string|max:100',
            'longtitude' => 'nullable|string|max:100',
            'landmark' => 'nullable|string|max:255',
        ]);

        $register_building = BuildingModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'address_lime_1' => $request->input('address_lime_1'),
            'address_lime_2' => $request->input('address_lime_2'),
            'city' => $request->input('city'),
            'pincode' => $request->input('pincode'),
            'state' => $request->input('state'),
            'lattitude' => $request->input('lattitude'),
            'longtitude' => $request->input('longtitude'),
            'landmark' => $request->input('landmark'),
        ]);

        unset($register_building['id'], $register_building['created_at'], $register_building['updated_at']);

        return $register_building
            ? response()->json(['message' => 'Building  created successfully!', 'data' => $register_building], 201)
            : response()->json(['message' => 'Failed to create Building!'], 400);
    }

    // view
    public function all_building()
    {
        $get_all_building = BuildingModel::select('jamiat_id', 'name', 'address_lime_1', 'address_lime_2', 'city', 'pincode', 'state', 'lattitude', 'longtitude', 'landmark')->get();

        return $get_all_building->isNotEmpty()
            ? response()->json(['message' => 'Building fetched successfully!', 'data' => $get_all_building], 200)
            : response()->json(['message' => 'No building records found!'], 404);
    }

    // update
    public function update_building(Request $request, $id)
    {
        $get_building = BuildingModel::find($id);

        if (!$get_building) {
            return response()->json(['message' => 'Building record not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'address_lime_1' => 'nullable|string|max:255',
            'address_lime_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'lattitude' => 'nullable|string|max:100',
            'longtitude' => 'nullable|string|max:100',
            'landmark' => 'nullable|string|max:255',
        ]);

        $update_building = $get_building->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'address_lime_1' => $request->input('address_lime_1'),
            'address_lime_2' => $request->input('address_lime_2'),
            'city' => $request->input('city'),
            'pincode' => $request->input('pincode'),
            'state' => $request->input('state'),
            'lattitude' => $request->input('lattitude'),
            'longtitude' => $request->input('longtitude'),
            'landmark' => $request->input('landmark'),
        ]);

        return ($update_building == 1)
            ? response()->json(['message' => 'Building fetchedted successfully!', 'data' => $update_building], 200)
            : response()->json(['No changes detected!'], 304);
    }

    // delete
    public function delete_building($id)
    {
        $delete_building = BuildingModel::where('id', $id)->delete();

        return $delete_building
            ? response()->json(['message' => 'Building  record deleted successfully!'], 200)
            : response()->json(['message' => 'Building  record not found!'], 404);
    }

    // view
    public function all_years()
    {
        $user = Auth::user(); // Get the authenticated user

        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $jamiatId = $user->jamiat_id; // Get the user's jamiat_id

        // Fetch years only for the logged-in user's jamiat_id
        $get_all_years = YearModel::select('year', 'jamiat_id', 'is_current')
            ->where('jamiat_id', $jamiatId)
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return $get_all_years->isNotEmpty()
            ? response()->json(['message' => 'Year records fetched successfully!', 'data' => $get_all_years], 200)
            : response()->json(['message' => 'No year records found!'], 404);
    }

    // update
    public function update_year(Request $request, $id)
    {
        $get_year = YearModel::find($id);

        if (!$get_year) {
            return response()->json(['message' => 'Year record not found!'], 404);
        }

        $request->validate([
            'year' => 'required|string|max:10',
            'jamiat_id' => 'required|integer',
            'is_current' => 'required|in:0,1',
        ]);

        $update_year_record = $get_year->update([
            'year' => $request->input('year'),
            'jamiat_id' => $request->input('jamiat_id'),
            'is_current' => $request->input('is_current'),
        ]);

        return ($update_year_record == 1)
            ? response()->json(['message' => 'Year updated successfully!', 'data' => $update_year_record], 200)
            : response()->json(['No changes detected!'], 304);
    }

    // delete
    public function delete_year($id)
    {
        $delete_year = YearModel::where('id', $id)->delete();

        return $delete_year
            ? response()->json(['message' => 'Year record deleted successfully!'], 200)
            : response()->json(['message' => 'Year record not found!'], 404);
    }

    // create
    public function register_fcm(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'user_id' => 'required|integer',
            'fcm_token' => 'required|string', // Since it's a text field, validation is lenient
            'status' => 'required|string|max:255',
        ]);

        $register_fcm = FcmModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'user_id' => $request->input('user_id'),
            'fcm_token' => $request->input('fcm_token'),
            'status' => $request->input('status'),
        ]);

        unset($register_fcm['id'], $register_fcm['created_at'], $register_fcm['updated_at']);

        return $register_fcm
            ? response()->json(['message' => 'FCM Token registered successfully!', 'data' => $register_fcm], 201)
            : response()->json(['message' => 'Failed to register FCM token!'], 400);
    }

    // view
    public function all_fcm()
    {
        $get_all_fcm_tokens = FcmModel::select('jamiat_id', 'user_id', 'fcm_token', 'status')->get();

        return $get_all_fcm_tokens->isNotEmpty()
            ? response()->json(['message' => 'FCM tokens fetched successfully!', 'data' => $get_all_fcm_tokens], 200)
            : response()->json(['message' => 'No FCM token records found!'], 404);
    }

    // update
    public function update_fcm(Request $request, $id)
    {
        $get_fcm_token = FcmModel::find($id);

        if (!$get_fcm_token) {
            return response()->json(['message' => 'FCM token record not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'user_id' => 'required|integer',
            'fcm_token' => 'required|string',
            'status' => 'required|string|max:255',
        ]);

        $update_fcm_record = $get_fcm_token->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'user_id' => $request->input('user_id'),
            'fcm_token' => $request->input('fcm_token'),
            'status' => $request->input('status'),
        ]);

        return ($update_fcm_record == 1)
            ? response()->json(['message' => 'FCM Token updated successfully!', 'data' => $update_fcm_record], 200)
            : response()->json(['No changes detected!'], 304);
    }

    // delete
    public function delete_fcm($id)
    {
        $delete_fcm_token = FcmModel::where('id', $id)->delete();

        return $delete_fcm_token
            ? response()->json(['message' => 'FCM token deleted successfully!'], 200)
            : response()->json(['message' => 'FCM token record not found!'], 404);
    }

    // create
    public function register_hub(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'nullable|integer',
            'family_id' => 'required|string|max:10',
            'year' => 'required|string|max:10',
            'hub_amount' => 'required|numeric',
            'paid_amount' => 'nullable|numeric',
            'due_amount' => 'nullable|numeric',
            
        ]);

        $register_hub = HubModel::create([
            'jamiat_id' => $request->input('jamiat_id')?? auth()->user()->jamiat_id,
            'family_id' => $request->input('family_id'),
            'year' => $request->input('year'),
            'hub_amount' => $request->input('hub_amount'),
            'paid_amount' => $request->input('paid_amount')??0,
            'due_amount' => $request->input('due_amount')??0,
            'log_user' => auth()->user()->name,
        ]);

        unset($register_hub['id'], $register_hub['created_at'], $register_hub['updated_at']);

        return $register_hub
            ? response()->json(['message' => 'Hub record created successfully!', 'data' => $register_hub], 201)
            : response()->json(['message' => 'Failed to create hub record!'], 400);
    }

    // view
    public function all_hub()
    {
        $get_all_hubs = HubModel::select('id','jamiat_id', 'family_id', 'year', 'hub_amount', 'paid_amount', 'due_amount', 'log_user')->get();

        return $get_all_hubs->isNotEmpty()
            ? response()->json(['message' => 'Hub records fetched successfully!', 'data' => $get_all_hubs], 200)
            : response()->json(['message' => 'No hub records found!'], 404);
    }

    // update
   public function update_hub(Request $request, $family_id)
{
    // Validate the incoming request
    $request->validate([
        'year' => 'required|string|max:10', // Ensure the year is provided
        'hub_amount' => 'required',          // Ensure the hub slab ID exists
        'thali_status' => 'nullable',        // Thali status can be null
    ]);

    // Get jamiat_id from authenticated user
    $jamiat_id = auth()->user()->jamiat_id;

    if (!$jamiat_id) {
        return response()->json(['message' => 'Jamiat ID is missing for the authenticated user.'], 400);
    }

    // Find or create the hub record
    $get_hub = HubModel::firstOrCreate(
        [
            'jamiat_id' => $jamiat_id,
            'family_id' => $family_id,
            'year' => $request->input('year'),
        ],
        [
            'hub_amount' => $request->input('hub_amount'),
            'paid_amount' => 0,
            'due_amount' => 0,
            'log_user' => auth()->user()->username,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    // If the record already existed, update only the hub_amount and thali_status fields
    if (!$get_hub->wasRecentlyCreated) {
        $get_hub->update([
            'hub_amount' => $request->input('hub_amount'),
            'thali_status' => $request->input('thali_status'),
        ]);
        // Also update due_amount on update
        $get_hub->due_amount = $request->input('hub_amount');
        $get_hub->save();
    }

    // Update thali_status in users table for the given family_id
    if ($request->has('thali_status')) {
        \App\Models\User::where('family_id', $family_id)
            ->update(['thali_status' => $request->input('thali_status')]);
    }

    // Return a success response
    return response()->json([
        'message' => 'Hub record updated successfully, and thali status updated for family users!',
        'data' => [
            'hub' => $get_hub,
            'hub_amount' => $request->input('hub_amount'),
        ],
    ], 200);
}

    private function addNiyazEntries($family_id, $jamiat_id, $hubSlab, $totalHubAmount)
    {
        // Check if hub_slab count is valid (greater than or equal to 1)
        if (empty($hubSlab->count) || $hubSlab->count < 1) {
            return; // Exit the function if count is not valid
        }

        // Generate a unique niyaz_id for the batch
        $niyazId = DB::table('t_niyaz')->max('niyaz_id') + 1;

        // Prepare the data for insertion
        $data = [];
        $date = now();
        $entryCount = $hubSlab->count; // Number of entries to add based on slab count

        // Calculate total amount per Niyaz entry
        $niyazAmount = $totalHubAmount / $entryCount;

        for ($i = 0; $i < $entryCount; $i++) {
            $data[] = [
                'niyaz_id' => $niyazId + $i, // Ensure unique niyaz_id per entry
                'jamiat_id' => $jamiat_id,
                'family_id' => $family_id,
                'date' => $date,
                'menu' => $hubSlab->name ?? 'Niyaz Menu Example', // Use slab name as menu
                'total_amount' => $niyazAmount,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }

        // Insert all entries into t_niyaz table
        DB::table('t_niyaz')->insert($data);
    }

    // delete
    public function delete_hub($id)
    {
        $delete_hub = HubModel::where('id', $id)->delete();

        return $delete_hub
            ? response()->json(['message' => 'Hub record deleted successfully!'], 200)
            : response()->json(['message' => 'Hub record not found!'], 404);
    }

    // users by family
    public function usersByFamily(Request $request)
    {
        $family_id = $request->input('family_id');
    
        // Fetch all family members sorted by age descending
        $family_members = User::select(
                'id','name', 'email', 'jamiat_id', 'family_id', 'mobile', 'its', 'hof_its', 'its_family_id', 'folio_no', 
                'mumeneen_type', 'title', 'gender', 'age', 'building', 'sector_id', 'sub_sector_id', 'status', 
                'role', 'username', 'photo_id'
            )
            ->with(['photo:id,file_url'])
            ->where('mumeneen_type','FM')
            ->where('family_id', $family_id)
            ->orderBy('age', 'desc') // Start with the eldest
            ->get();
    
        if ($family_members->isEmpty()) {
            return response()->json(['Sorry, failed to fetch records!'], 404);
        }
    
        // Sort members manually by `its_family_id` grouping
        $sorted_members = [];
        $grouped_by_its_family_id = [];
    
        // Group members by `its_family_id`
        foreach ($family_members as $member) {
            $grouped_by_its_family_id[$member->its_family_id][] = $member;
        }
    
        // Process each group by age
        foreach ($grouped_by_its_family_id as $its_family_id => $members) {
            // Sort each group by age descending (already sorted from initial query, but for safety)
            usort($members, function ($a, $b) {
                return $b->age <=> $a->age;
            });
            // if (!empty($sorted_members)) {
            //     array_shift($sorted_members); // Remove the first element
            // }
    
            // Append members to the final sorted list
            foreach ($members as $member) {
                $sorted_members[] = $member;
            }
        }
    
        return response()->json(['User Record Fetched Successfully!', 'data' => $sorted_members], 200);
    }  

    public function familyHubDetails($family_id)
    {
        $jamiat_id = Auth::user()->jamiat_id;

        // Fetch all years for the current jamiat
        $years = YearModel::where('jamiat_id', $jamiat_id)
            ->orderBy('year', 'desc') // Ensure the years are in descending order
            ->limit(3) // Limit to the last 3 years
            ->get();

        if ($years->isEmpty()) {
            return response()->json(['message' => 'No year data found for the Jamiat.'], 404);
        }

        // Fetch hub data for the given family_id for the last three years
        $hub_data = HubModel::select('id','year', 'hub_amount', 'paid_amount', 'due_amount')
            ->where('family_id', $family_id)
            ->where('jamiat_id', $jamiat_id)
            ->whereIn('year', $years->pluck('year')) // Only fetch data for the last three years
            ->get()
            ->keyBy('year'); // Key by year for easy lookup

        // Prepare a response with year-wise hub details for the last three years
        $yearly_details = $years->map(function ($year) use ($hub_data) {
            $year_str = $year->year; // Extract the year string

            $hub_record = $hub_data->get($year_str); // Find hub record for this year

            return [
                'id' => $hub_record->id??null,
                'year' => $year_str,
                'hub_amount' => $hub_record->hub_amount ?? 0,
                'paid_amount' => $hub_record->paid_amount ?? 0,
                'due_amount' => $hub_record->due_amount ?? 0,
            ];
        });

        return response()->json(['message' => 'Hub details fetched successfully!', 'data' => $yearly_details], 200);
    }

    public function createYearAndHubEntries(Request $request)
    {
        // Validate input to ensure a year and jamiat_id are provided
        $request->validate([
            'year' => 'required|string|max:10',
            'jamiat_id' => 'required|integer'
        ]);

        $year = $request->input('year');
        $jamiatId = $request->input('jamiat_id');

        DB::beginTransaction();

        try {
            // Step 1: Mark all previous years as non-current for this jamiat
            YearModel::where('jamiat_id', $jamiatId)->update(['is_current' => '0']);

            // Step 2: Create new year entry in `t_year` table (if it does not exist)
            $newYear = YearModel::updateOrCreate(
                ['year' => $year, 'jamiat_id' => $jamiatId],
                ['is_current' => '1'] // Ensure only one year is marked as current
            );

            // Step 3: Get all unique `family_id`s from `users` table
            $uniqueFamilyIds = User::select('family_id')
                ->distinct()
                ->whereNotNull('family_id')
                ->pluck('family_id');

            foreach ($uniqueFamilyIds as $familyId) {
                // Fetch the most recent `thali_status` from `t_hub` for this `family_id`
                $previousThaliStatus = HubModel::where('family_id', $familyId)
                    ->where('jamiat_id', $jamiatId)
                    ->where('year', '<', $year)
                    ->orderBy('year', 'desc') // Get the latest year entry
                    ->value('thali_status');

                // Check if an entry already exists for the current year
                $existingHubEntry = HubModel::where([
                    'jamiat_id' => $jamiatId,
                    'family_id' => $familyId,
                    'year' => $year,
                ])->first();

                if ($existingHubEntry) {
                    // Update only `thali_status`
                    $existingHubEntry->update(['thali_status' => $previousThaliStatus]);
                } else {
                    // Create a new entry with default values
                    HubModel::create([
                        'jamiat_id' => $jamiatId,
                        'family_id' => $familyId,
                        'year' => $year,
                        'hub_amount' => 0, // Default value, update as needed
                        'paid_amount' => 0,
                        'due_amount' => 0,
                        'thali_status' => $previousThaliStatus, // Store the previous thali_status
                        'log_user' => auth()->user()->name ?? 'system', // Assuming user is logged in
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Year and hub entries created/updated successfully!',
                'year_id' => $newYear->id
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $e->getMessage()
            ], 500);
        }
    }



    public function getDistinctFamilyCountUnderAge14()
    {
        // Count distinct family IDs where users are under age 14
        $distinctFamilyCount = DB::table('users')
            ->where('age', '<', 14)
            ->distinct('family_id')
            ->count('family_id');

        // Count total number of children under age 14
        $totalChildrenCount = DB::table('users')
            ->where('age', '<', 14)
            ->count();

        return response()->json([
            'message' => 'Number of distinct families and total children under age 14',
            'distinct_family_count' => $distinctFamilyCount,
            'total_children_count' => $totalChildrenCount,
        ]);
    }

    public function getUsersBelowAge15WithHofDetails()
    {
        // Fetch users below age 15 with their HOF details grouped by HOF and sector
        $rawData = DB::table('users as children')
            ->select(
                'hof.name as hof_name',
                'hof.its as hof_its',
                'hof.mobile as hof_mobile',
                'children.name as child_name',
                'children.age as child_age',
                'children.its as child_its',
                'children.family_id',
                't_sector.name as sector_name'
            )
            ->leftJoin('users as hof', function ($join) {
                $join->on('children.family_id', '=', 'hof.family_id')
                    ->where('hof.mumeneen_type', '=', 'HOF');
            })
            ->leftJoin('t_sector', 'children.sector_id', '=', 't_sector.id')
            ->where('children.age', '<', 15)
            ->where('children.mumeneen_type', '=', 'FM') // Include only Family Members
            ->orderBy('t_sector.name')
            ->orderBy('hof.name')
            ->orderBy('children.name')
            ->get();
    
        // Group data by sectors
        $groupedData = $rawData->groupBy('sector_name')->map(function ($sector) {
            $hofGrouped = $sector->groupBy('hof_its')->map(function ($hofGroup) {
                $hof = $hofGroup->first(); // HOF details
                return [
                    'hof_name' => $hof->hof_name,
                    'hof_its' => $hof->hof_its,
                    'hof_mobile' => $hof->hof_mobile,
                    'sector_name' => $hof->sector_name,
                    'children_count' => $hofGroup->count(),
                    'children' => $hofGroup->map(function ($child) {
                        return [
                            'child_name' => $child->child_name,
                            'child_age' => $child->child_age,
                            'child_its' => $child->child_its,
                        ];
                    })->values(),
                ];
            })->values();
    
            return [
                'sector_name' => $sector->first()->sector_name,
                'sector_count' => $hofGrouped->sum('children_count'),
                'users' => $hofGrouped,
            ];
        })->values();
    
        return response()->json([
            'message' => 'Users below age 15 grouped by HOF and sector',
            'data' => $groupedData,
        ]);
    }

    public function getUserNameByIts($its)
{
    // Search for the user by ITS (username)
    $user = User::where('username', $its)->first();  // Searching by the username column which contains ITS

    // If the user is not found, return a 404 response
    if (!$user) {
        return response()->json([
            'code' => 404,
            'status' => 'error',
            'message' => 'User not found',
            'data' => null
        ], 404);
    }

    // Return the user's name if found
    return response()->json([
        'code' => 200,
        'status' => 'success',
        'message' => 'User found',
        'data' => [
            'name' => $user->name  // Only returning the user's name
        ]
    ], 200);
}
public function thaali(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $jamiatId = $user->jamiat_id;

        $thaaliStatuses = ThaaliStatus::where('jamiat_id', $jamiatId)
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Thaali statuses fetched successfully',
            'data' => $thaaliStatuses,
        ]);
    }
}
