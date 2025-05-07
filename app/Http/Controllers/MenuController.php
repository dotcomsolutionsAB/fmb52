<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuModel;
use App\Http\Controllers\Auth\AuthController;
use Auth;

use Illuminate\Support\Facades\Http; // 

class MenuController extends Controller
{
    //
    public function register_menu(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'nullable|integer',
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
            'jamiat_id' => $request->input('jamiat_id')??auth()->$user->name,
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
        $get_all_menus = MenuModel::select('id','jamiat_id', 'family_id', 'date', 'menu', 'addons', 'niaz_by', 'year', 'slip_names', 'category', 'status')->get();

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
            'date' => 'required|date', // Ensure the date is required and valid
        ]);
    
        $date = $request->input('date');
        
        // Get the menu for that specific date
        $menu = MenuModel::where('date', $date)->get();
    
        if ($menu->isEmpty()) {
            return response()->json(['message' => 'No menu found for this date'], 404);
        }
    
        // Call the Aladhan API to get Hijri date information for the specified date
        $hijriDate = $this->getHijriDate($date); // Hijri date (e.g., "27 Shawwal al-Mukarram 1446")
        $dayName = \Carbon\Carbon::parse($date)->format('l'); // Get the day name (e.g., "Friday")
    
        return response()->json([
            'message' => 'Menu for the specified date fetched successfully!',
            'menu' => $menu->map(function ($item) use ($hijriDate, $dayName) {
                return [
                    'id' => $item->id,
                    'date' => $item->date,
                    'hijri_date' => $hijriDate, // Add Hijri date
                    'day_name' => $dayName,     // Add day name (e.g., Friday)
                    'menu' => $item->menu,
                    'addons' => $item->addons,
                    'niaz_by' => $item->niaz_by,
                    'slip_names' => $item->slip_names,
                    'category' => $item->category,
                ];
            }),
        ], 200);
    }

    // Method to fetch menu for the week starting from Monday
    public function getMenuForWeek(Request $request)
    {
        $request->validate([
            'date' => 'required|date', // Ensure the date is required and valid
        ]);
    
        $date = $request->input('date');
        
        // Calculate the start of the week (Monday) and end of the week (Sunday)
        $startOfWeek = \Carbon\Carbon::parse($date)->startOfWeek()->toDateString();
        $endOfWeek = \Carbon\Carbon::parse($startOfWeek)->endOfWeek()->toDateString();
    
        // Fetch all menus for the week
        $menus = MenuModel::whereBetween('date', [$startOfWeek, $endOfWeek])->get();
    
        if ($menus->isEmpty()) {
            return response()->json(['message' => 'No menus found for this week'], 404);
        }
    
        // Call the Aladhan API to get Hijri date information for each day of the week
        $hijriDates = [];
        foreach (\Carbon\Carbon::parse($startOfWeek)->daysUntil($endOfWeek) as $day) {
            $hijriDates[$day->toDateString()] = $this->getHijriDate($day->toDateString());
        }
    
        return response()->json([
            'message' => 'Menus for the week fetched successfully!',
            'menus' => $menus->map(function ($item) use ($hijriDates) {
                $hijriDate = $hijriDates[$item->date] ?? 'N/A'; // Get Hijri date for the specific day
                $dayName = \Carbon\Carbon::parse($item->date)->format('l'); // Get the day name (e.g., "Monday")
    
                return [
                    'id'=> $item->id,
                    'date' => $item->date,
                    'hijri_date' => $hijriDate, // Add Hijri date for the specific day
                    'day_name' => $dayName,     // Add day name (e.g., Monday)
                    'menu' => $item->menu,
                    'addons' => $item->addons,
                    'niaz_by' => $item->niaz_by,
                    'slip_names' => $item->slip_names,
                    'category' => $item->category,
                ];
            }),
        ], 200);
    }

    // Helper method to get the Hijri date for a given Gregorian date
    public function getHijriDate($date)
    {
        // Define an array with Hijri month names in order
        $hijriMonths = [
            1 => "Moharram al-Haraam",
            2 => "Safar al-Muzaffar",
            3 => "Rabi al-Awwal",
            4 => "Rabi al-Aakhar",
            5 => "Jumada al-Ula",
            6 => "Jumada al-Ukhra",
            7 => "Rajab al-Asab",
            8 => "Shabaan al-Karim",
            9 => "Ramadaan al-Moazzam",
            10 => "Shawwal al-Mukarram",
            11 => "Zilqadah al-Haraam",
            12 => "Zilhaj al-Haraam"
        ];
    
        // Format the date to match the API's expected format (DD-MM-YYYY)
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
    
            // Map the month number to the Hijri month name from the array
            $monthName = $hijriMonths[$hijriDate['month']['number']] ?? 'Unknown Month';
    
            // Return Hijri date in the required format: "day month_name year"
            return $hijriDate['day'] . ' ' . $monthName . ' ' . $hijriDate['year'];
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
