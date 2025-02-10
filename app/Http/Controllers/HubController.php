<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\HubModel;
use App\Models\YearModel;

class HubController extends Controller
{
    public function hub_distribution($year)
    {
        // Get total users for the given year
        // $total_hof = User::whereHas('sector', function ($query) use ($year) {
        //     $query->where('year', $year);
        // })->count();

        // // Count users whose hub amount is greater than 0 or thali_status is "joint"
        // $hub_done = User::whereHas('sector', function ($query) use ($year) {
        //     $query->where('year', $year);
        // })->whereHas('hub', function ($query) {
        //     $query->where('hub_amount', '>', 0);
        // })->orWhere('thali_status', 'joint')->count();

        // // Count users whose hub amount is 0 and thali_status is either "taking", "not_taking", or "once_a_week"
        // $hub_pending = User::whereHas('sector', function ($query) use ($year) {
        //     $query->where('year', $year);
        // })->whereDoesntHave('hub', function ($query) {
        //     $query->where('hub_amount', '>', 0);
        // })->whereIn('thali_status', ['taking', 'not_taking', 'once_a_week'])->count();

        $total_hof = 850;
        $hub_done = 600;
        $hub_pending = 250;

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
}
