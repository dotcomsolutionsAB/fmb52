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
                'users.its',
                'users.name as user_name',
                'users.hof_its as user_hof_its',
                'users.mumeneen_type as user_mumeneen_type',
                'users.gender as user_gender',
                'users.age as user_age',
                'users.sector as user_sector'
            )
            ->leftJoin('t_its_data', 'users.its', '=', 't_its_data.its')
            ->whereNull('t_its_data.its') // ITS in users but not in t_its_data
            ->orderBy('users.hof_its')
            ->get();
    
        // ITS in t_its_data but not in users
        $itsOnlyInItsData = DB::table('t_its_data')
            ->select(
                't_its_data.its',
                't_its_data.name as its_data_name',
                't_its_data.hof_its as its_data_hof_its',
                't_its_data.mumeneen_type as its_data_mumeneen_type',
                't_its_data.gender as its_data_gender',
                't_its_data.age as its_data_age',
                't_its_data.sector as its_data_sector'
            )
            ->leftJoin('users', 't_its_data.its', '=', 'users.its')
            ->whereNull('users.its') // ITS in t_its_data but not in users
            ->orderBy('t_its_data.hof_its')
            ->get();
    
        // Prepare response with headers
        return response()->json([
            'header' => [
                'hof_its' => 'Head of Family ITS',
                'name' => 'Name',
            ],
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