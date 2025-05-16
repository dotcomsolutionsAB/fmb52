<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UploadModel;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;


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
        $role = Auth::user()->role;

        if($role == 'superadmin')
        {
            $request->validate([
                'jamiat_id' => 'required|numeric',
            ]);

            $jamiat_id = $request->file('jamiat_id');
        }

        else {
            $jamiat_id = Auth::user()->jamiat_id;
        }

        $request->validate([
            'files' => 'required|array|max:50', // Validate 'files' as an array with a maximum of 50 items
            'files.*' => 'file|mimes:jpeg,png,jpg,pdf|max:2048', // Validate each file
            'file_name' => 'required|string',
            'type' => 'required|string',
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
            $directoryPath = "uploads/$jamiat_id/{$request->input('type')}";
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
                'file_name' => $request->input('file_name'),
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

    // fetch and store records `uploads`
    public function fetch_uploads(Request $request)
    {
        try {
            $role = Auth::user()->role;

            // Validate inputs based on the user's role
            if ($role == 'superadmin') {
                $request->validate([
                    'jamiat_id' => 'required|numeric',
                ]);

                $jamiat_id = $request->input('jamiat_id');
            } else {
                $jamiat_id = Auth::user()->jamiat_id;
            }

            // Validate the rest of the inputs
            $request->validate([
                'type' => 'required|string',
                'family_id' => 'nullable|numeric',
            ]);

            // Define the directory path for fetching files
            $fetchImagePath = storage_path("app/public/uploads/$jamiat_id/{$request->input('type')}");

            // Check if the directory exists
            if (!file_exists($fetchImagePath)) {
                return response()->json(['message' => 'Directory does not exist.'], 404);
            }

            // Fetch files from the directory
            $files = scandir($fetchImagePath);
            $uploadedRecords = [];
            $skippedFiles = [];

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue; // Skip system files
                }

                $filePath = "$fetchImagePath/$file";

                if (is_file($filePath)) {
                    try {
                        // Extract file details
                        $fileSize = filesize($filePath);
                        $fileExt = pathinfo($file, PATHINFO_EXTENSION);
                        $fileName = pathinfo($file, PATHINFO_FILENAME);
                        $publicUrl = asset("storage/uploads/$jamiat_id/{$request->input('type')}/$file");

                        // Check if the file already exists in the database
                        $existingRecord = UploadModel::where('jamiat_id', $jamiat_id)
                            ->where('file_name', $fileName)
                            ->where('file_ext', $fileExt)
                            ->where('file_url', $publicUrl)
                            ->first();

                        if ($existingRecord) {
                            $skippedFiles[] = $fileName; // Track skipped files
                            continue; // Skip processing this file
                        }

                        // Store file details in the database
                        $record = UploadModel::create([
                            'jamiat_id' => $jamiat_id,
                            'family_id' => $request->input('family_id'),
                            'file_name' => $fileName,
                            'file_ext' => $fileExt,
                            'file_url' => $publicUrl,
                            'file_size' => $fileSize,
                        ]);

                        $uploadedRecords[] = $record;
                    } catch (\Exception $e) {
                        // Log the error and skip the problematic file
                        \Log::error("Failed to process file: {$filePath}. Error: {$e->getMessage()}");
                    }
                }
            }

            if (empty($uploadedRecords) && empty($skippedFiles)) {
                return response()->json(['message' => 'No valid files found or processed.'], 404);
            }

            unset($uploadedRecords['id'], $uploadedRecords['created_at'], $uploadedRecords['updated_at']);
            
            return response()->json([
                'message' => 'Files processed successfully.',
                'uploaded' => $uploadedRecords,
                'uploaded_count' => count($uploadedRecords),
                'skipped' => $skippedFiles,
                'skipped_count' => count($skippedFiles)
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json(['message' => 'Validation error.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Handle general exceptions
            \Log::error("An error occurred: {$e->getMessage()}");
            return response()->json(['message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    // store photoid

public function store_photo(Request $request)
{
    try {
        $role = Auth::user()->role;

        if ($role === 'superadmin') {
            $request->validate(['jamiat_id' => 'required|numeric']);
            $jamiat_id = $request->input('jamiat_id');
        } else {
            $jamiat_id = Auth::user()->jamiat_id;
        }

        $placeholder = UploadModel::where('file_name', 'placeholder')->first();
        if (!$placeholder) {
            return response()->json(['message' => 'Placeholder image not found.'], 404);
        }

        // ✅ Only select users with photo_id = null or placeholder ID
        $users = User::select('id', 'its')
            ->where(function ($query) use ($placeholder) {
                $query->whereNull('photo_id')
                      ->orWhere('photo_id', $placeholder->id);
            })
            ->get();

        foreach ($users as $user) {
            try {
                $upload = UploadModel::where('file_name', $user->its)
                    ->where('jamiat_id', $jamiat_id)
                    ->first();

                if (!$upload) {
                    $remoteUrl = "https://talabulilm.com/mumin_images/{$user->its}.png";
                    $fileName = "{$user->its}.png";
                    $folder = "uploads/{$jamiat_id}/photoid";
                    $path = "{$folder}/{$fileName}";

                    if (!Storage::disk('public')->exists($folder)) {
                        Storage::disk('public')->makeDirectory($folder);
                    }

                    $image = @file_get_contents($remoteUrl);

                    if ($image !== false) {
                        Storage::disk('public')->put($path, $image);

                        $upload = UploadModel::create([
                            'jamiat_id'   => $jamiat_id,
                            'family_id'   => null,
                            'file_name'   => $user->its,
                            'file_ext'    => 'png',
                            'file_url'    => asset("storage/{$path}"),
                            'file_size'   => Storage::disk('public')->size($path),
                        ]);
                    }
                }

                // ✅ Update user with new upload ID or fallback to placeholder
                User::where('id', $user->id)->update([
                    'photo_id' => $upload ? $upload->id : $placeholder->id
                ]);

            } catch (\Exception $e) {
                \Log::error("Error updating photo_id for user {$user->its}: {$e->getMessage()}");
            }
        }

        return response()->json([
            'message' => 'Photo IDs updated successfully where photo_id was null or placeholder.',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['message' => 'Validation error.', 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        \Log::error("Unexpected error in store_photo: {$e->getMessage()}");
        return response()->json(['message' => 'Unexpected error occurred.'], 500);
    }
}

}
