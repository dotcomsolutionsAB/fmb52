<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * Find ITS mismatches between users and t_its_data from both perspectives.
     */
    public function findItsMismatches()
{
    // ITS in users but not in t_its_data
    $itsOnlyInUsers = DB::table('users')
        ->select(
            'users.hof_its',
            DB::raw('GROUP_CONCAT(CONCAT(users.its, ":", users.name, " (", IFNULL(users.age, "N/A"), "y, ", IFNULL(users.mobile, "N/A"), ")") SEPARATOR ", ") as family_members'),
            DB::raw('COUNT(users.its) as total_members'),
            'users.hof_its as hof_its_id',
            DB::raw('(SELECT name FROM users WHERE its = users.hof_its LIMIT 1) as hof_name'),
            DB::raw('(SELECT COUNT(*) > 0 FROM users WHERE its = users.hof_its LIMIT 1) as hof_present_in_users')
        )
        ->leftJoin('t_its_data', 'users.its', '=', 't_its_data.its')
        ->whereNull('t_its_data.its') // ITS in users but not in t_its_data
        ->where('users.jamiat_id', 1)
        ->where('users.role', 'mumeneen')
        ->groupBy('users.hof_its')
        ->orderBy('users.hof_its')
        ->get();

    // ITS in t_its_data but not in users
    $itsOnlyInItsData = DB::table('t_its_data')
        ->select(
            't_its_data.hof_its',
            DB::raw('GROUP_CONCAT(CONCAT(t_its_data.its, ":", t_its_data.name, " (", IFNULL(t_its_data.age, "N/A"), "y, ", IFNULL(t_its_data.mobile, "N/A"), ")") SEPARATOR ", ") as family_members'),
            DB::raw('COUNT(t_its_data.its) as total_members'),
            't_its_data.hof_its as hof_its_id',
            DB::raw('(SELECT name FROM t_its_data WHERE its = t_its_data.hof_its LIMIT 1) as hof_name'),
            DB::raw('(SELECT COUNT(*) > 0 FROM users WHERE its = t_its_data.hof_its LIMIT 1) as hof_present_in_users')
        )
        ->leftJoin('users', 't_its_data.its', '=', 'users.its')
        ->whereNull('users.its') // ITS in t_its_data but not in users
        ->groupBy('t_its_data.hof_its')
        ->orderBy('t_its_data.hof_its')
        ->get();

    // Prepare response
    return response()->json([
        'its_only_in_users' => $itsOnlyInUsers,
        'its_only_in_its_data' => $itsOnlyInItsData,
    ]);
}
    /**
     * Find mismatches in ITS and Mumeneen type between users and t_its_data.
     */
    public function findItsAndMumeneenTypeMismatches()
    {
        $mismatchedFromUsers = DB::table('users')
            ->select('users.its', 'users.mumeneen_type as mumeneen_type_in_users', 't_its_data.mumeneen_type as mumeneen_type_in_its_data')
            ->join('t_its_data', 'users.its', '=', 't_its_data.its')
            ->whereColumn('users.mumeneen_type', '!=', 't_its_data.mumeneen_type') // Mumeneen type mismatch
            ->get();

        $mismatchedFromItsData = DB::table('t_its_data')
            ->select('t_its_data.its', 't_its_data.mumeneen_type as mumeneen_type_in_its_data', 'users.mumeneen_type as mumeneen_type_in_users')
            ->join('users', 't_its_data.its', '=', 'users.its')
            ->whereColumn('t_its_data.mumeneen_type', '!=', 'users.mumeneen_type') // Mumeneen type mismatch
            ->get();

        return [
            'mismatched_from_users' => $mismatchedFromUsers,
            'mismatched_from_its_data' => $mismatchedFromItsData,
        ];
    }

    /**
     * Central method to execute all comparisons and consolidate results.
     */
   
     public function findMobileMismatches()
     {
         // Mobile numbers in t_its_data but not in users
         $mobileOnlyInItsData = DB::table('t_its_data')
             ->select('t_its_data.its', 't_its_data.mobile')
             ->leftJoin('users', 't_its_data.its', '=', 'users.its')
             ->whereNull('users.its') // ITS not found in users
             ->get();
 
         // Mobile numbers mismatch between the two tables for the same ITS
         $mobileMismatchForSameITS = DB::table('users')
             ->select('users.its', 'users.mobile as mobile_in_users', 't_its_data.mobile as mobile_in_its_data')
             ->join('t_its_data', 'users.its', '=', 't_its_data.its')
             ->whereColumn('users.mobile', '!=', 't_its_data.mobile') // Mobile mismatch
             ->get();
 
         return response()->json([
             'mobile_only_in_its_data' => $mobileOnlyInItsData,
             'mobile_mismatch_for_same_its' => $mobileMismatchForSameITS,
         ]);
     }

    public function syncData()
    {
        $itsMismatch = $this->findItsMismatches();
        $itsAndTypeMismatch = $this->findItsAndMumeneenTypeMismatches();
        $itsMobileMismatch = $this->findMobileMismatches();

        return response()->json([
            'its_mismatch' => $itsMismatch,
            'its_and_type_mismatch' => $itsAndTypeMismatch,
           // 'mobile_mismatch' =>$itsMobileMismatch,
        ]);
    }
}