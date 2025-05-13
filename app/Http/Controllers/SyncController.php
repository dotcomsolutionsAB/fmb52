<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ItsModel;
use App\Models\SectorModel;
use App\Models\SubSectorModel;
use App\Models\HubModel;

use Auth;

class SyncController extends Controller
{
    /**
     * Scenario 1: Detect HOF present in t_its_data but missing in users.
     */
    // public function detectMissingHofInUsers()
    // {
    //     $missingHofs = DB::table('t_its_data')
    //         ->select('t_its_data.its', 't_its_data.name', 't_its_data.mobile', 't_its_data.age', 't_its_data.hof_its')
    //         ->leftJoin('users', 't_its_data.its', '=', 'users.its')
    //         ->whereNull('users.its')
    //         ->whereColumn('t_its_data.its', 't_its_data.hof_its')
    //         ->get();

    //     return response()->json([
    //         'message' => 'Missing HOFs detected.',
    //         'data' => $missingHofs
    //     ]);
    // }

    private function generateUniqueFamilyId()
    {
        do {
            $familyId = mt_rand(1000000000, 9999999999); // Generate a 10-digit random number
            $exists = User::where('family_id', $familyId)->exists();
        } while ($exists); // Ensure uniqueness

        return $familyId;
    }

    public function detectMissingHofInUsers()
    {
        $missingHofs = DB::table('t_its_data as t')
            ->select(
                't.its', 
                't.name', 
                't.mobile', 
                't.age', 
                't.hof_its', 
                'u.family_id' // Fetch family_id from users table
            )
            ->leftJoin('users as u', 't.hof_its', '=', 'u.its') // Join on HOF ITS
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.its', 't.its');
            })
            ->whereColumn('t.its', 't.hof_its') // Ensure ITS is same as HOF ITS
            ->get();

        return response()->json([
            'message' => 'Missing HOFs detected.',
            'data' => $missingHofs
        ]);
    }

    public function addMissingHofInUsers(Request $request)
    {
        $request->validate([
            'hof_its' => 'required|string|max:8'
        ]);

        $hofIts = $request->input('hof_its');

        // Check if the HOF already exists in users
        $existingUser = User::where('its', $hofIts)->first();
        if ($existingUser) {
            return response()->json([
                'message' => 'User already exists.',
                'data' => $existingUser
            ], 200);
        }

        // Fetch data from t_its_data
        $hofData = ItsModel::where('its', $hofIts)->first();

        if (!$hofData) {
            return response()->json([
                'message' => 'HOF ITS not found in t_its_data.'
            ], 404);
        }

        // Extract title if it starts with "Shaikh" or "Mulla"
        $nameParts = explode(' ', $hofData->name, 2); // Split into two parts
        $title = null;
        $name = $hofData->name;

        if (in_array($nameParts[0], ['Shaikh', 'Mulla'])) {
            $title = $nameParts[0]; // Set title
            $name = $nameParts[1] ?? ''; // Remove title from name
        }

        // Generate a unique 10-digit family_id
        $familyId = $this->generateUniqueFamilyId();

        $sectorMapping = DB::table('t_sector')->pluck('id', 'name')->toArray();
        $sectorId = $sectorMapping[$hofData->sector] ?? null;

        // Create the user using the retrieved data
        $newUser = User::create([
            'name' => $name,
            'email' => strtolower($hofData->email ?? null), // Default email if not available
            'password' => bcrypt('defaultpassword'), // Set a default password, should be changed later
            'jamiat_id' => $hofData->jamiat_id,
            'family_id' => $familyId,
            'its' => $hofData->its,
            'hof_its' => $hofData->hof_its,
            'its_family_id' => $hofData->its_family_id,
            'mobile' => $hofData->mobile,
            'title' => $title,
            'gender' => $hofData->gender,
            'age' => $hofData->age,
            'thali_status' => 'taking',
            'building' => null, // Not available in t_its_data
            'folio_no' => null, // Not available in t_its_data
            'mumeneen_type' => $hofData->mumeneen_type,
            'sector_id' => $sectorId,
            'sub_sector_id' => null,
            'role' => 'mumeneen', // Default role
            'status' => 'active', // Default status
            'username' => $hofIts
        ]);

        HubModel::updateOrCreate(
            [
                'family_id' => $familyId,
                'year' => '1446-1447',
            ],
            [
                'jamiat_id' => 1,
                'hub_amount' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'log_user' => 'admin',
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'User created successfully!',
            'data' => $newUser
        ], 201);
    }

    /**
     * Scenario 2: Confirm and add missing Family Members from t_its_data to users.
     */
    public function confirmFmFromItsData()
    {
        // Fetch ITS numbers for Family Members (FMs) that are in t_its_data but missing in users
        $missingFms = DB::table('t_its_data')
            ->leftJoin('users', 't_its_data.its', '=', 'users.its')
            ->whereNull('users.its') // Ensure the ITS is not present in users
            ->select('t_its_data.*') // Select all columns from t_its_data
            ->get();

        foreach ($missingFms as $fm) {
            // Skip if there is no HOF for the FM in the users table
            $hof = DB::table('users')
                ->where('its', $fm->hof_its)
                ->where('mumeneen_type', 'HOF')
                ->first();

            if (!$hof) {
                continue; // Skip if no HOF is found
            }

            // Insert the new FM into the users table
            DB::table('users')->insert([
                'username' => $fm->its, // ITS as username
                'role' => 'mumeneen', // Default role for members
                'name' => $fm->name, // Name from t_its_data
                'email' => $fm->email ?? null, // Email if available
                'jamiat_id' => $hof->jamiat_id, // Inherit from HOF
                'family_id' => $hof->family_id, // Inherit from HOF
                'mobile' => $fm->mobile ?? null, // Mobile number
                'its' => $fm->its, // ITS ID
                'hof_its' => $fm->hof_its, // HOF ITS
                'its_family_id' => $fm->its_family_id, // ITS Family ID from t_its_data
                'folio_no' => $hof->folio_no, // Folio number from HOF
                'mumeneen_type' => 'FM', // Family Member
                'title' => in_array($fm->title, ['Shaikh', 'Mulla']) ? $fm->title : null, // Validate title
                'gender' => $fm->gender ?? null, // Gender if available
                'age' => $fm->age ?? null, // Age if available
                'building' => $fm->building ?? null, // Building if available
                'status' => $hof->status, // Status from HOF
                'thali_status' => $hof->thali_status, // Thali status from HOF
                'otp' => null, // Default value
                'expires_at' => null, // Default value
                'email_verified_at' => null, // Default value
                'password' => bcrypt('default_password'), // Default password
                'joint_with' => null, // Default value
                'photo_id' => null, // Default value
                'sector_access_id' => null, // Default value
                'sub_sector_access_id' => null, // Default value
                'sector_id' => $hof->sector_id ?? null, // Inherit from HOF
                'sub_sector_id' => $hof->sub_sector_id ?? null, // Inherit from HOF
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Missing Family Members have been added successfully!']);
    }

    public function syncFamilyMembers()
    {
        DB::beginTransaction();

        try {
            // Step 1: Remove FM from `users` if not in `t_its_data`
            $fmsToRemove = DB::table('users')
                ->leftJoin('t_its_data', 'users.its', '=', 't_its_data.its')
                ->whereNull('t_its_data.its')
                ->where('users.mumeneen_type', 'FM')
                ->pluck('users.its'); // Get ITS numbers to remove

            if ($fmsToRemove->isNotEmpty()) {
                DB::table('users')->whereIn('its', $fmsToRemove)->delete();
            }

            // Step 2: Remove FM if its `its_family_id` is not part of its `family_id` group
            $invalidFamilyFMs = DB::table('users as u')
                ->leftJoin('t_its_data as tid', 'u.its', '=', 'tid.its')
                ->where('u.mumeneen_type', 'FM')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('users as u2')
                        ->whereRaw('u.its_family_id = u2.its_family_id')
                        ->whereRaw('u.family_id = u2.family_id');
                })
                ->pluck('u.its');

            if ($invalidFamilyFMs->isNotEmpty()) {
                DB::table('users')->whereIn('its', $invalidFamilyFMs)->delete();
            }

            // Step 3: Add missing FM from `t_its_data`
            $missingFms = DB::table('t_its_data as tid')
                ->leftJoin('users as u', 'tid.its', '=', 'u.its')
                ->whereNull('u.its') // Ensure FM is not already in users
                ->select('tid.*')
                ->get();

            foreach ($missingFms as $fm) {
                // Fetch the HOF associated with this FM
                $hof = DB::table('users')
                    ->where('its', $fm->hof_its)
                    ->where('mumeneen_type', 'HOF')
                    ->first();

                if (!$hof) {
                    continue; // Skip if no valid HOF is found
                }

                // Insert FM into users
                DB::table('users')->insert([
                    'username' => $fm->its,
                    'role' => 'mumeneen',
                    'name' => $fm->name,
                    'email' => $fm->email ?? null,
                    'jamiat_id' => $hof->jamiat_id,
                    'family_id' => $hof->family_id,
                    'mobile' => $fm->mobile ?? null,
                    'its' => $fm->its,
                    'hof_its' => $fm->hof_its,
                    'its_family_id' => $fm->its_family_id,
                    'folio_no' => $hof->folio_no,
                    'mumeneen_type' => 'FM',
                    'title' => in_array($fm->title, ['Shaikh', 'Mulla']) ? $fm->title : null,
                    'gender' => $fm->gender ?? null,
                    'age' => $fm->age ?? null,
                    'building' => $fm->building ?? null,
                    'status' => $hof->status,
                    'thali_status' => $hof->thali_status,
                    'otp' => null,
                    'expires_at' => null,
                    'email_verified_at' => null,
                    'password' => bcrypt('default_password'),
                    'joint_with' => null,
                    'photo_id' => null,
                    'sector_access_id' => null,
                    'sub_sector_access_id' => null,
                    'sector_id' => $hof->sector_id ?? null,
                    'sub_sector_id' => $hof->sub_sector_id ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Family Members sync completed!',
                'removed_fms' => $fmsToRemove,
                'removed_invalid_fms' => $invalidFamilyFMs,
                'added_fms' => $missingFms
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Sync failed!', 'message' => $e->getMessage()], 500);
        }
    }

    
    /**
     * Scenario 3: Detect HOF present in users but not in t_its_data.
     */
    public function detectInvalidHofInUsers()
    {
        $invalidHofs = DB::table('users')
            ->select('users.family_id', 'users.its', 'users.name','users.label')
            ->leftJoin('t_its_data', 'users.its', '=', 't_its_data.its')
            ->whereNull('t_its_data.its')
            ->whereColumn('users.its', 'users.hof_its')
            ->where('users.status', 'active')
            ->get();

        return response()->json([
            'message' => 'Invalid HOFs detected in users.',
            'data' => $invalidHofs
        ]);
    }

    /**
     * Scenario 4: Remove FMs in users table that are not in t_its_data.
     */
    public function removeFmNotInItsData()
    {
        $fmsToRemove = DB::table('users')
            ->select('users.its', 'users.name')
            ->leftJoin('t_its_data', 'users.its', '=', 't_its_data.its')
            ->whereNull('t_its_data.its')
            ->where('users.mumeneen_type', 'FM')
            ->get();

        foreach ($fmsToRemove as $fm) {
            DB::table('users')->where('its', $fm->its)->delete();
        }

        return response()->json([
            'message' => 'Family Members not present in t_its_data have been removed.',
            'data' => $fmsToRemove
        ]);
    }

    /**
     * Scenario 5: Detect role mismatches - HOF marked as FM in users.
     */
    public function detectHofMarkedAsFmInUsers()
    {
        $roleMismatches = DB::table('users')
            ->join('t_its_data', 'users.its', '=', 't_its_data.its')
            ->where('users.mumeneen_type', 'FM') // Check mumeneen_type in users
            ->whereColumn('t_its_data.its', 't_its_data.hof_its') // Check HOF in t_its_data
            ->select('users.its', 'users.name', 'users.mumeneen_type as current_type', 't_its_data.hof_its')
            ->get();
    
        return response()->json([
            'message' => 'HOF marked as FM in users detected.',
            'data' => $roleMismatches
        ]);
    }

    /**
     * Scenario 5: Confirm role update - Mark FM in users as HOF.
     */
    public function confirmHofRoleUpdate(Request $request)
    {
        $validated = $request->validate([
            'its_list' => 'required|array',
            'its_list.*.its' => 'required|string',
        ]);
    
        foreach ($validated['its_list'] as $record) {
            DB::table('users')->where('its', $record['its'])->update(['mumeneen_type' => 'HOF']);
        }
    
        return response()->json(['message' => 'Mumeneen type updated to HOF successfully!']);
    }

    /**
     * Scenario 6: Detect role mismatches - HOF marked as HOF in users but FM in t_its_data.
     */
    public function detectHofMarkedAsFmInItsData()
    {
        $roleMismatches = DB::table('users')
            ->join('t_its_data', 'users.its', '=', 't_its_data.its')
            ->where('users.mumeneen_type', 'HOF') // Check HOF in users
            ->whereColumn('t_its_data.hof_its', '!=', 't_its_data.its') // Check FM in t_its_data
            ->select('users.its', 'users.name', 'users.mumeneen_type as current_type', 't_its_data.hof_its','users.label', )
            ->get();
    
        return response()->json([
            'message' => 'HOF in users but marked as FM in t_its_data detected.',
            'data' => $roleMismatches
        ]);
    }

    /**
     * Scenario 6: Confirm role update - Mark HOF in users as FM.
     */
    public function confirmFmRoleUpdate(Request $request)
    {
        $validated = $request->validate([
            'its_list' => 'required|array',
            'its_list.*.its' => 'required|string',
        ]);
    
        foreach ($validated['its_list'] as $record) {
            DB::table('users')->where('its', $record['its'])->update(['mumeneen_type' => 'FM']);
        }
    
        return response()->json(['message' => 'Mumeneen type updated to FM successfully!']);
    }

    /**
     * Consolidated Sync Function: Runs all scenarios sequentially.
     */

public function updateHofData()
{
    DB::beginTransaction();

    try {
        // Fetch all users with mumeneen_type as HOF
        $hofUsers = DB::table('users')
            ->where('mumeneen_type', 'HOF')
            ->get();

        // Check if there are any HOF users
        if ($hofUsers->isEmpty()) {
            return response()->json(['message' => 'No HOF users found in the users table.'], 404);
        }

        // Loop through each HOF user and update the data
        foreach ($hofUsers as $hofUser) {
            // Find matching data from t_its_data where its matches the user's username
            $hofData = DB::table('t_its_data')
                ->where('its', $hofUser->username) // Match users.username to t_its_data.its
                ->first();

            // If HOF data exists, update the user
            if ($hofData) {
                // Prepare data for update
                $updateData = [
                    'name' => $hofData->name,
                    'mobile' => $hofData->mobile,
                    'email' => $hofData->email,
                    'updated_at' => now(),  // Set the updated timestamp
                ];

                // Update user data in the users table
                DB::table('users')
                    ->where('its', $hofUser->username)
                    ->update($updateData);
            }
        }

        // Commit the transaction
        DB::commit();

        return response()->json([
            'message' => 'HOF data updated successfully for all users.',
        ], 200);
        
    } catch (\Exception $e) {
        // Rollback in case of failure
        DB::rollBack();

        // Return the error message
        return response()->json([
            'message' => 'Failed to update HOF data!',
            'error' => $e->getMessage(),
            'stack' => $e->getTraceAsString(),
        ], 500);
    }
}
     public function consolidatedSync()
    {
        $missingHofs = $this->detectMissingHofInUsers();
        $invalidHofs = $this->detectInvalidHofInUsers();
        $fmsRemoved = $this->removeFmNotInItsData();
        $fixedHofRoles = $this->detectHofMarkedAsFmInUsers();
        $fixedFmRoles = $this->detectHofMarkedAsFmInItsData();

        return response()->json([
            'status' => 'Sync completed successfully!',
            'results' => [
                'missing_hofs' => $missingHofs->original['data'],
                'invalid_hofs' => $invalidHofs->original['data'],
                'fms_removed' => $fmsRemoved->original['data'],
                'fixed_hof_roles' => $fixedHofRoles->original['data'],
                'fixed_fm_roles' => $fixedFmRoles->original['data'],
            ],
        ]);
    }
}