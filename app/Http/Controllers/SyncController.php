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
            ->select('its')
            ->leftJoin('t_its_data', 'users.its', '=', 't_its_data.its')
            ->whereNull('t_its_data.its') // ITS in users but not in t_its_data
            ->get();

        $itsOnlyInItsData = DB::table('t_its_data')
            ->select('its')
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
    public function syncData()
    {
        $itsMismatch = $this->findItsMismatches();
        $itsAndTypeMismatch = $this->findItsAndMumeneenTypeMismatches();

        return response()->json([
            'its_mismatch' => $itsMismatch,
            'its_and_type_mismatch' => $itsAndTypeMismatch,
        ]);
    }
}