<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UploadModel;

class UploadController extends Controller
{
    //
    public function upload(Request $request)
    {
        $request->input([
            'files' => 'required',
            'files.*' => 'file|mimes:jpeg,png,jpg,pdf|max:2048',
            'file_name' => 'required|string',
            'type' => 'required|string',
            'jamiat_id' => 'required|string',
            'family_id' => 'required|string',
        ]);

        // Check if files are provided
        if (!$request->hasFile('files')) {
            return response()->json(['message' => 'No files uploaded.'], 422);
        }

        if (!$request->has('file_name')) {
            return response()->json(['message' => 'Either user_id or inventory_id is required.'], 422);
        }

        $uploadFiles = $request->file('files');
        $uploadedIds = [];

         // Handle both single and multiple files
        if (!is_array($uploadFiles)) {
            $uploadFiles = [$uploadFiles]; // Wrap single file into an array
        }

        foreach ($uploadFiles as $image) {

             // Prepare directory path
            $directoryPath = "uploads/{$request->input('type')}";
            $fullPath = storage_path("app/public/{$directoryPath}");

            // Create directory if it doesn't exist
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
            // $userFileName = "{$request->input('file_name')}." . $image->getClientOriginalExtension();

            // $filePath = $image->storeAs("/uploads/{$request->input('type')}", $userFileName,'public');

             // Generate user-defined file name
            $userFileName = "{$request->input('file_name')}." . $image->getClientOriginalExtension();

            // Save file to the specified directory
            $filePath = $image->storeAs($directoryPath, $userFileName, 'public');

            // Generate public URL for the file
            $publicUrl = asset("storage/{$filePath}");


            $upload = UploadModel::create([
                'jamiat_id' => $request->input('jamiat_id'),
                'family_id' => $request->input('family_id'),
                'file_ext' => $image->getClientOriginalExtension(),
                'file_url' => $publicUrl,
                'file_size' => $image->getSize(),
            ]);

            // dd($upload);

            $uploadedIds[] = $upload->id;
        }

        // dd("mm");

        return response()->json(['message' => 'Files uploaded successfully', 'upload_ids' => $uploadedIds], 201);
    }
}
