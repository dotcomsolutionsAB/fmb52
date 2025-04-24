<?php

namespace App\Http\Controllers;

use App\Models\TNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NotificationController extends Controller
{
    // Function to add a new notification
    public function add(Request $request)
{
    // Validate the request data
    $validated = $request->validate([
        'jamiat_id' => 'required|integer',
        'title' => 'required|string|max:255',
        'msg' => 'required|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'type' => 'required|in:image,text,mixed',
    ]);

    // Handle image upload if present
    $imageUrl = null;
    if ($request->hasFile('image')) {
        $imageUrl = $request->file('image')->store('notifications', 'public');
    }

    // Get the name of the logged-in user
    $createdBy = auth()->user()->name;  // Assuming `name` is a column in the `users` table

    // Create the notification record
    $notification = TNotification::create([
        'jamiat_id' => $validated['jamiat_id'],
        'title' => $validated['title'],
        'msg' => $validated['msg'],
        'image' => $imageUrl,
        'type' => $validated['type'],
        'created_by' => $createdBy,  // Set created_by to the logged-in user's name
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Notification added successfully',
        'data' => $notification
    ]);
}

    // Function to edit an existing notification
    public function edit(Request $request, $id)
{
    // Validate the request data
    $validated = $request->validate([
        'jamiat_id' => 'required|integer',
        'title' => 'required|string|max:255',
        'msg' => 'required|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'type' => 'required|in:image,text,mixed',
    ]);

    // Find the notification by ID
    $notification = TNotification::find($id);

    if (!$notification) {
        return response()->json([
            'success' => false,
            'message' => 'Notification not found',
        ], 404);
    }

    // Handle image upload if present
    if ($request->hasFile('image')) {
        // Delete old image if exists
        if ($notification->image && Storage::exists('public/' . $notification->image)) {
            Storage::delete('public/' . $notification->image);
        }

        // Store the new image
        $imageUrl = $request->file('image')->store('notifications', 'public');
        $notification->image = $imageUrl;
    }

    // Get the name of the logged-in user
    $createdBy = auth()->user()->name;  // Assuming `name` is a column in the `users` table

    // Update the notification record
    $notification->update([
        'jamiat_id' => $validated['jamiat_id'],
        'title' => $validated['title'],
        'msg' => $validated['msg'],
        'type' => $validated['type'],
        'created_by' => $createdBy,  // Set created_by to the logged-in user's name
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Notification updated successfully',
        'data' => $notification
    ]);
}

    // Function to view a specific notification by ID
    public function view($id)
    {
        // Find the notification by ID
        $notification = TNotification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }

    // Function to view all notifications
    public function viewAll()
    {
        // Get all notifications
        $notifications = TNotification::all();

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }
}