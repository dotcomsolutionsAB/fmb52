<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\HubModel;
use App\Models\YearModel;

class HubController extends Controller
{
    public function hub_distribution()
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
