<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class FeedbacksController extends Controller
{
    // Add a new feedback entry
    public function add(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'jamiat_id' => 'required|integer',
            'menu_id' => 'required|integer',
            'date' => 'required|date',
            'user_id' => 'required|integer',
            'family_id' => 'required|integer',
            'food_taste' => 'required|integer|min:1|max:10',
            'food_quantity' => 'required|integer|min:1|max:10',
            'food_quality' => 'required|integer|min:1|max:10',
            'oily' => 'required|integer|min:0|max:1',
            'spicy' => 'required|integer|min:0|max:1',
            'remarks' => 'nullable|string',
            'images' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle image upload if it exists
        $imageUrl = null;
        if ($request->hasFile('images')) {
            $imageUrl = $request->file('images')->store('feedback_images', 'public');
        }

        // Create new feedback record
        $feedback = Feedback::create([
            'jamiat_id' => $validated['jamiat_id'],
            'menu_id' => $validated['menu_id'],
            'date' => $validated['date'],
            'user_id' => $validated['user_id'],
            'family_id' => $validated['family_id'],
            'food_taste' => $validated['food_taste'],
            'food_quantity' => $validated['food_quantity'],
            'food_quality' => $validated['food_quality'],
            'oily' => $validated['oily'],
            'spicy' => $validated['spicy'],
            'remarks' => $validated['remarks'],
            'images' => 'https://api.fmb52.com/storage/'.$imageUrl,
        ]);

        return response()->json([
            'code' =>200,
            'status' => true,
            'message' => 'Feedback added successfully',
            'data' => $feedback
        ]);
    }

    // Edit an existing feedback entry
    public function edit(Request $request, $id)
    {
        // Validate the request data
        $validated = $request->validate([
            'jamiat_id' => 'required|integer',
            'menu_id' => 'required|integer',
            'date' => 'required|date',
            'user_id' => 'required|integer',
            'family_id' => 'required|integer',
            'food_taste' => 'required|integer|min:1|max:10',
            'food_quantity' => 'required|integer|min:1|max:10',
            'food_quality' => 'required|integer|min:1|max:10',
            'oily' => 'required|integer|min:0|max:1',
            'spicy' => 'required|integer|min:0|max:1',
            'remarks' => 'nullable|string',
            'images' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Find the feedback by ID
        $feedback = Feedback::find($id);
        if (!$feedback) {
            return response()->json([
                'code'=>404,
                'status' => false,
                'message' => 'Feedback not found',
            ], 404);
        }

        // Handle image upload if present
        if ($request->hasFile('images')) {
            // Delete the old image if it exists
            if ($feedback->images && Storage::exists('public/' . $feedback->images)) {
                Storage::delete('public/' . $feedback->images);
            }

            // Store the new image
            $imageUrl = $request->file('images')->store('feedback_images', 'public');
            $feedback->images = $imageUrl;
        }

        // Update the feedback record
        $feedback->update([
            'jamiat_id' => $validated['jamiat_id'],
            'menu_id' => $validated['menu_id'],
            'date' => $validated['date'],
            'user_id' => $validated['user_id'],
            'family_id' => $validated['family_id'],
            'food_taste' => $validated['food_taste'],
            'food_quantity' => $validated['food_quantity'],
            'food_quality' => $validated['food_quality'],
            'oily' => $validated['oily'],
            'spicy' => $validated['spicy'],
            'remarks' => $validated['remarks'],
        ]);

        return response()->json([
            'code'=>404,
            'status' => true,
            'message' => 'Feedback updated successfully',
            'data' => $feedback
        ]);
    }

    // View a specific feedback by ID
    public function view($id)
    {
        $feedback = Feedback::find($id);
        if (!$feedback) {
            return response()->json([
                'code' => 404,
                'success' => false,
                'message' => 'Feedback not found',
            ], 404);
        }

        return response()->json([
            'code'=>200,
            'status' => true,
            'message' => 'Feedback not found',
            'data' => $feedback
        ]);
    }

    // View all feedbacks
    public function viewAll()
    {
        $feedbacks = Feedback::all();
        return response()->json([
            'code'=>200,
            'status' => true,
            'message'=>"Feedbacks Found",
            'data' => $feedbacks
        ]);
    }
    public function dailyMenuReport(Request $request)
{
    // Optionally validate date range filters
    $validated = $request->validate([
        'date_from' => 'nullable|date',
        'date_to' => 'nullable|date|after_or_equal:date_from',
    ]);

    $query = DB::table('t_feedbacks as f')
        ->join('t_menu as m', 'f.menu_id', '=', 'm.id') // Assuming you have a 'menus' table with menu names
        ->select(
            'm.menu as menu_name',
            DB::raw('DATE(f.date) as feedback_date'),
            DB::raw('COUNT(f.id) as review_count'),
            DB::raw('SUM(f.oily) as oily_count'),
            DB::raw('SUM(f.spicy) as spicy_count'),
            DB::raw('AVG(f.food_taste) as avg_taste'),
            DB::raw('AVG(f.food_quality) as avg_quality'),
            DB::raw('AVG(f.food_quantity) as avg_quantity')
        )
        ->groupBy('feedback_date', 'm.name')
        ->orderBy('feedback_date', 'desc')
        ->orderBy('m.name');

    // Apply date filters if provided
    if (!empty($validated['date_from'])) {
        $query->whereDate('f.date', '>=', $validated['date_from']);
    }
    if (!empty($validated['date_to'])) {
        $query->whereDate('f.date', '<=', $validated['date_to']);
    }

    $report = $query->get();

    return response()->json([
        'code' => 200,
        'status' => true,
        'message' => 'Daily menu feedback report generated successfully',
        'data' => $report,
    ]);
}
}