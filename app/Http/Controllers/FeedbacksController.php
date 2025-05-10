<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'images' => $imageUrl,
        ]);

        return response()->json([
            'success' => true,
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
                'success' => false,
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
            'success' => true,
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
                'success' => false,
                'message' => 'Feedback not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $feedback
        ]);
    }

    // View all feedbacks
    public function viewAll()
    {
        $feedbacks = Feedback::all();
        return response()->json([
            'success' => true,
            'data' => $feedbacks
        ]);
    }
}