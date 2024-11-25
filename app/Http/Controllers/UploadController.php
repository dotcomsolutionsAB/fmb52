<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UploadModel;

class UploadController extends Controller
{
    //
    // public function upload(Request $request)
    // {
    //     $request->validate([
    //         'files' => 'required',
    //         'files.*' => 'file|mimes:jpeg,png,jpg,pdf|max:2048',
    //         'file_name' => 'required|string',
    //         'type' => 'required|string',
    //         'jamiat_id' => 'required|string',
    //         'family_id' => 'required|string',
    //     ]);

    //     foreach ($files as $file) {
    //         // Access the 'originalName' property of the UploadedFile object
    //         $originalFileName = $file->getClientOriginalName(); // This will return 'Random_Turtle.jpg'
            
    //         dd($originalFileName);  // This will show "Random_Turtle.jpg"
    //     }
    //     dd($request->all(), $request->file('files'));

    //     $uploadFiles = $request->file('files');

    //     if (!is_array($uploadFiles)) {
    //         $uploadFiles = [$uploadFiles]; // Wrap single file into an array
    //     }
    //     dd($uploadFiles);
    //     if (!is_array($uploadFiles) || empty($uploadFiles)) {
    //         return response()->json(['message' => 'No files uploaded.'], 422);
    //     }
        
    //     if (!$request->has('file_name')) {
    //         return response()->json(['message' => 'Either user_id or inventory_id is required.'], 422);
    //     }

    //     $uploadFiles = $request->file('files');
    //     $uploadedIds = [];

    //      // Handle both single and multiple files
    //     if (!is_array($uploadFiles)) {
    //         $uploadFiles = [$uploadFiles]; // Wrap single file into an array
    //     }

    //     foreach ($uploadFiles as $image) {
    //          // Prepare directory path
    //         $directoryPath = "uploads/{$request->input('type')}";
    //         $fullPath = storage_path("app/public/{$directoryPath}");

    //         // Create directory if it doesn't exist
    //         if (!file_exists($fullPath)) {
    //             mkdir($fullPath, 0755, true);
    //         }
    //         // $userFileName = "{$request->input('file_name')}." . $image->getClientOriginalExtension();

    //         // $filePath = $image->storeAs("/uploads/{$request->input('type')}", $userFileName,'public');

    //          // Generate user-defined file name
    //         $userFileName = "{$request->input('file_name')}." . $image->getClientOriginalExtension();

    //         // Save file to the specified directory
    //         $filePath = $image->storeAs($directoryPath, $userFileName, 'public');

    //         // Generate public URL for the file
    //         $publicUrl = asset("storage/{$filePath}");


    //         $upload = UploadModel::create([
    //             'jamiat_id' => $request->input('jamiat_id'),
    //             'family_id' => $request->input('family_id'),
    //             'file_ext' => $image->getClientOriginalExtension(),
    //             'file_url' => $publicUrl,
    //             'file_size' => $image->getSize(),
    //         ]);

    //         dd($upload);

    //         $uploadedIds[] = $upload->id;
    //     }

    //     dd("mm");

    //     return response()->json(['message' => 'Files uploaded successfully', 'upload_ids' => $uploadedIds], 201);
    // }

    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array|max:50', // Validate 'files' as an array with a maximum of 50 items
            'files.*' => 'file|mimes:jpeg,png,jpg,pdf|max:2048', // Validate each file
            'file_name' => 'required|string',
            'type' => 'required|string',
            'jamiat_id' => 'required|string',
            'family_id' => 'required|string',
        ]);

        // Retrieve the uploaded files
        $uploadFiles = $request->file('files');

        // Validate that the number of files does not exceed 50
        if (count($uploadFiles) > 50) {
            return response()->json(['message' => 'You can upload a maximum of 50 files at a time.'], 422);
        }

        $uploadedIds = [];

        foreach ($uploadFiles as $index => $file) {
            // Generate user-defined file name with an index to avoid overwriting
            $userFileName = "{$request->input('file_name')}_{$index}." . $file->getClientOriginalExtension();

            // Directory path for storing the files
            $directoryPath = "uploads/{$request->input('type')}";
            $fullPath = storage_path("app/public/{$directoryPath}");

            // Create the directory if it doesn't exist
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Save file to the specified directory
            $filePath = $file->storeAs($directoryPath, $userFileName, 'public');

            // Generate public URL for the file
            $publicUrl = asset("storage/{$filePath}");

            // Save file details to the database
            $upload = UploadModel::create([
                'jamiat_id' => $request->input('jamiat_id'),
                'family_id' => $request->input('family_id'),
                'file_ext' => $file->getClientOriginalExtension(),
                'file_url' => $publicUrl,
                'file_size' => $file->getSize(),
            ]);

            // Add the uploaded file ID to the list
            $uploadedIds[] = $upload->id;
        }

        // Return a success response
        return response()->json([
            'message' => 'Files uploaded successfully',
            'upload_ids' => $uploadedIds,
        ], 201);
    }

}
