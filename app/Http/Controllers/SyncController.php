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
    $itsOnlyInUsers = DB::table('users')
        ->select('users.its') // Qualify the column with the table name
        ->leftJoin('t_its_data', 'users.its', '=', 't_its_data.its')
        ->whereNull('t_its_data.its') // ITS in users but not in t_its_data
        ->get();

    $itsOnlyInItsData = DB::table('t_its_data')
        ->select('t_its_data.its') // Qualify the column with the table name
        ->leftJoin('users', 't_its_data.its', '=', 'users.its')
        ->whereNull('users.its') // ITS in t_its_data but not in users
        ->get();

    return [
        'its_only_in_users' => $itsOnlyInUsers,
        'its_only_in_its_data' => $itsOnlyInItsData,
    ];
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
        // Mobile numbers exist in users but not in t_its_data
        $mobileOnlyInUsers = DB::table('users')
            ->select('users.its', 'users.mobile', 't_its_data.mobile as mobile_in_its_data')
            ->leftJoin('t_its_data', 'users.its', '=', 't_its_data.its')
            ->whereNull('t_its_data.mobile') // Mobile exists in users but not in t_its_data
            ->get();

        // Mobile numbers exist in t_its_data but not in users
        $mobileOnlyInItsData = DB::table('t_its_data')
            ->select('t_its_data.its', 't_its_data.mobile', 'users.mobile as mobile_in_users')
            ->leftJoin('users', 't_its_data.its', '=', 'users.its')
            ->whereNull('users.mobile') // Mobile exists in t_its_data but not in users
            ->get();

        // Mobile numbers mismatch between the two tables for the same ITS
        $mobileMismatchForSameITS = DB::table('users')
            ->select('users.its', 'users.mobile as mobile_in_users', 't_its_data.mobile as mobile_in_its_data')
            ->join('t_its_data', 'users.its', '=', 't_its_data.its')
            ->whereColumn('users.mobile', '!=', 't_its_data.mobile') // Mobile mismatch
            ->get();

        return response()->json([
            'mobile_only_in_users' => $mobileOnlyInUsers,
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
            'mobile_mismatch' =>$itsMobileMismatch,
        ]);
    }
}