<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuModel;
use Auth;

use Illuminate\Support\Facades\Http; // 

class MenuController extends Controller
{
    //
    public function register_menu(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'family_id' => 'nullable|integer',
            'date' => 'required|date',
            'menu' => 'required|string|max:255',
            'addons' => 'required|string|max:255',
            'niaz_by' => 'required|string|max:255',
            'year' => 'required|string|max:10',
            'slip_names' => 'required|string|max:255',
            'category' => 'required|in:chicken,mutton,veg,dal,zabihat',
            'status' => 'required|string|max:255',
        ]);

        $register_menu = MenuModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'date' => $request->input('date'),
            'menu' => $request->input('menu'),
            'addons' => $request->input('addons'),
            'niaz_by' => $request->input('niaz_by'),
            'year' => $request->input('year'),
            'slip_names' => $request->input('slip_names'),
            'category' => $request->input('category'),
            'status' => $request->input('status'),
        ]);

        unset($register_menu['id'], $register_menu['created_at'], $register_menu['updated_at']);

        return $register_menu
            ? response()->json(['message' => 'Menu created successfully!', 'data' => $register_menu], 201)
            : response()->json(['message' => 'Failed to create menu!'], 400);
    }

    // view
    public function all_menu()
    {
        $get_all_menus = MenuModel::select('jamiat_id', 'family_id', 'date', 'menu', 'addons', 'niaz_by', 'year', 'slip_names', 'category', 'status')->get();

        return $get_all_menus->isNotEmpty()
            ? response()->json(['message' => 'Menus fetched successfully!', 'data' => $get_all_menus], 200)
            : response()->json(['message' => 'No menu records found!'], 404);
    }

    // update
    public function update_menu(Request $request, $id)
    {
        $get_menu = MenuModel::find($id);

        if (!$get_menu) {
            return response()->json(['message' => 'Menu record not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'family_id' => 'nullable|integer',
            'date' => 'required|date',
            'menu' => 'required|string|max:255',
            'addons' => 'required|string|max:255',
            'niaz_by' => 'required|string|max:255',
            'year' => 'required|string|max:10',
            'slip_names' => 'required|string|max:255',
            'category' => 'required|in:chicken,mutton,veg,dal,zabihat',
            'status' => 'required|string|max:255',
        ]);

        $update_menu_record = $get_menu->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'date' => $request->input('date'),
            'menu' => $request->input('menu'),
            'addons' => $request->input('addons'),
            'niaz_by' => $request->input('niaz_by'),
            'year' => $request->input('year'),
            'slip_names' => $request->input('slip_names'),
            'category' => $request->input('category'),
            'status' => $request->input('status'),
        ]);

        return ($update_menu_record == 1)
            ? response()->json(['message' => 'Menu updated successfully!', 'data' => $update_menu_record], 200)
            : response()->json(['No changes detected!'], 304);
    }

    // delete
    public function delete_menu($id)
    {
        $delete_menu = MenuModel::where('id', $id)->delete();

        return $delete_menu
            ? response()->json(['message' => 'Menu record deleted successfully!'], 200)
            : response()->json(['message' => 'Menu record not found!'], 404);
    }
    public function getMenuByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date', // Make sure the date is required and valid
        ]);

        $date = $request->input('date');
        
        // Get the menu for that specific date
        $menu = MenuModel::where('date', $date)->get();

        if ($menu->isEmpty()) {
            return response()->json(['message' => 'No menu found for this date'], 404);
        }

        // Call the Aladhan API to get Hijri date information
        $hijriDate = $this->getHijriDate($date);

        return response()->json([
            'message' => 'Menu for the specified date fetched successfully!',
            'data' => [
                'menu' => $menu,
                'hijri_date' => $hijriDate
            ]
        ], 200);
    }

    // Method to fetch menu for the week starting from Monday
    public function getMenuForWeek(Request $request)
    {
        $request->validate([
            'date' => 'required|date', // Make sure the date is required and valid
        ]);

        $date = $request->input('date');
        
        // Calculate the start of the week (Monday)
        $startOfWeek = \Carbon\Carbon::parse($date)->startOfWeek()->toDateString();
        $endOfWeek = \Carbon\Carbon::parse($startOfWeek)->endOfWeek()->toDateString();

        // Fetch all menus for the week
        $menus = MenuModel::whereBetween('date', [$startOfWeek, $endOfWeek])->get();

        if ($menus->isEmpty()) {
            return response()->json(['message' => 'No menus found for this week'], 404);
        }

        // Call the Aladhan API to get Hijri date information for the week (using the first day of the week)
        $hijriStartOfWeek = $this->getHijriDate($startOfWeek);

        return response()->json([
            'message' => 'Menus for the week fetched successfully!',
            'data' => [
                'menus' => $menus,
                'hijri_start_of_week' => $hijriStartOfWeek
            ]
        ], 200);
    }

    // Helper method to get the Hijri date for a given Gregorian date
    private function getHijriDate($date)
    {
        // Ensure the date is in DD-MM-YYYY format
        $formattedDate = \Carbon\Carbon::parse($date)->format('d-m-Y');  // Format the input date
        
        // Aladhan API URL for Gregorian to Hijri conversion
        $apiUrl = "https://api.aladhan.com/v1/gToH/" . $formattedDate . "?calendarMethod=HJCoSA";
    
        // Send GET request to Aladhan API
        $response = Http::get($apiUrl);
    
        // Check if the response is successful
        if ($response->successful()) {
            $data = $response->json();
            
            // Fetch Hijri date information
            $hijriDate = $data['data']['hijri'];
    
            // Return Hijri date in the required format
            return [
                'day' => $hijriDate['day'],  // Day of the Hijri date
                'month' => $hijriDate['month']['en'],  // Month name in English (e.g., Rajab)
                'month_number' => $hijriDate['month']['number'],  // Month number (e.g., 7 for Rajab)
                'year' => $hijriDate['year'],  // Year in Hijri calendar
                'weekday' => $hijriDate['weekday']['en'],  // Weekday in English (e.g., Wednesday)
            ];
        } else {
            // If the API request fails, return the API URL for debugging
            return [
                'url' => $apiUrl,
                'day' => 'N/A',
                'month' => 'N/A',
                'month_number' => 'N/A',
                'year' => 'N/A',
                'weekday' => 'N/A',
            ];
        }
    }
}
