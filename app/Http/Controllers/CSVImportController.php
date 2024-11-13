<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Statement;
// use App\Models\User;
use App\Models\ItsModel;
// use DB;

class CSVImportController extends Controller
{
    //
    // public function importUser()
    // {
    //     // set_time_limit(60000); // Increase to 5 minutes, adjust as needed
    //     // URL of the CSV file from Google Sheets
    //     $csvurl = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSM-hjx9inHhq2KdvGOC8xf1t4ZxWPKgP3nAIm72iWg5FuQ_uC6fpN130UVeVHjzzRLkNUT7r8q8681/pub?gid=0&single=true&output=csv';

    //     // Fetch the CSV content using file_get_contents
    //     $csvContent_user = file_get_contents($csvurl);

    //     if ($csvContent_user === false) {
    //         throw new \Exception("Failed to fetch the CSV content from the URL.");
    //     }

    //     // Fetch and parse the CSV
    //     $csv = Reader::createFromString($csvContent_user, 'r');

    //     // Set the header offset
    //     $csv->setHeaderOffset(0);

    //     $user_records = (new Statement())->process($csv);

    //     $insert_user = null;
    //     $update_user = null;

    //     foreach ($user_records as $user)
    //     {
    //         $get_user = User::where('mobile', $user['Mobile'])->first();

    //         // Handle potential empty values for email, family_id, and its
    //         // $user_email = NULL;
    //         $user_its = $user['ITS_ID'] !== '' ? $user['ITS_ID'] : 0;

    //         if ($get_user) {
    //             // If user exists, update it
    //             $update_user = $get_user->update([
    //                 'name' => $user['Name'],
    //                 // 'email' => $user_email,
    //                 'password' => bcrypt($user['Mobile']),
    //                 'family_id' => random_int(1000000000, 9999999999),
    //                 // 'title' => $user['title'],
    //                 'its' => $user_its,
    //                 'hof_its' => $user_its,
    //                 // 'family_its_id' => random_int(1000000000, 9999999999),
    //                 'mobile' => $user['Mobile'],
    //                 // 'address' => $user['address'],
    //                 // 'building' => $user['building'],
    //                 // 'flat_no' => $user['flat_no'],
    //                 // 'lattitude' => $user['lattitude'],
    //                 // 'longitude' => $user['longitude'],
    //                 'gender' => strtolower($user['Gender']),
    //                 // 'date_of_birth' => $user['date_of_birth'],
    //                 // 'folio_no' => $user['folio_no'],
    //                 // 'sector' => $user['sector'],
    //                 // 'sub_sector' => $user['sub_sector'],
    //                 // 'thali_status' => $user['thali_status'],
    //                 // 'status' => $user['status'],
    //             ]);
    //         }

    //         else {
    //             // If user does not exist, create a new one
    //             $insert_user = User::create([
    //                 'name' => $user['Name'],
    //                 // 'email' => $user_email,
    //                 'password' => bcrypt($user['Mobile']),
    //                 'family_id' => random_int(1000000000, 9999999999),
    //                 // 'title' => $user['title'],
    //                 'its' => $user_its,
    //                 'hof_its' => $user_its,
    //                 // 'family_its_id' => random_int(1000000000, 9999999999),
    //                 'mobile' => $user['Mobile'],
    //                 // 'address' => $user['address'],
    //                 // 'building' => $user['building'],
    //                 // 'flat_no' => $user['flat_no'],
    //                 // 'lattitude' => $user['lattitude'],
    //                 // 'longitude' => $user['longitude'],
    //                 'gender' => strtolower($user['Gender']),
    //                 // 'date_of_birth' => $user['date_of_birth'],
    //                 // 'folio_no' => $user['folio_no'],
    //                 // 'sector' => $user['sector'],
    //                 // 'sub_sector' => $user['sub_sector'],
    //                 // 'thali_status' => $user['thali_status'],
    //                 // 'status' => $user['status'],
    //             ]);
    //         }
    //     }

    //     if ($update_user == 1 || isset($insert_user)) {
    //         return response()->json(['message' => 'Users imported successfully!', 'success' => 'true'], 200);
    //     }
    //     else {
    //         return response()->json(['message' => 'Sorry, failed to imported successfully!', 'success' => 'false'], 400);
    //     }
    // }
    
     // Function to handle CSV import (create/update)
    //  public function importUser()
    //  {
    //     $csvurl = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSM-hjx9inHhq2KdvGOC8xf1t4ZxWPKgP3nAIm72iWg5FuQ_uC6fpN130UVeVHjzzRLkNUT7r8q8681/pub?gid=0&single=true&output=csv';
 
    //      // Retrieve the uploaded file
    //     //  $file = $request->file('csv_file');

    //      $csvContent_user = file_get_contents($csvurl);


    //      $csv = Reader::createFromString($csvContent_user, 'r');

    //     // Set the header offset
    //     $csv->setHeaderOffset(0);

    // //     $user_records = (new Statement())->process($csv);

    // //     $insert_user = null;
    // //     $update_user = null;
 
    //      // Open the CSV file
    //     //  $csv = Reader::createFromPath($file->getRealPath(), 'r');
    //     //  $csv->setHeaderOffset(0); // Assuming the CSV has a header row
 
    //      $user_records = $csv->getRecords();
    //      $batchSize = 1000; // Number of records to process at a time
    //      $batchData = [];
 
    //      foreach ($user_records as $user) {
    //          // Check if a user already exists by unique field, such as email
    //         //  $existingRecord = User::where('email', $user['email'])->first();
    //         $get_user = User::where('mobile', $user['Mobile'])->first();
 
    //         $user_its = $user['ITS_ID'] !== '' ? $user['ITS_ID'] : 0;
            
    //          if ($get_user) {
    //              // If the record exists, update it
    //              $get_user->update([
    //                 'name' => $user['Name'],
    //                 // 'email' => $user_email,
    //                 'password' => bcrypt($user['Mobile']),
    //                 'family_id' => random_int(1000000000, 9999999999),
    //                 // 'title' => $user['title'],
    //                 'its' => $user_its,
    //                 'hof_its' => $user_its,
    //                 // 'family_its_id' => random_int(1000000000, 9999999999),
    //                 'mobile' => $user['Mobile'],
    //                 // 'address' => $user['address'],
    //                 // 'building' => $user['building'],
    //                 // 'flat_no' => $user['flat_no'],
    //                 // 'lattitude' => $user['lattitude'],
    //                 // 'longitude' => $user['longitude'],
    //                 'gender' => strtolower($user['Gender']),
    //                 // 'date_of_birth' => $user['date_of_birth'],
    //                 // 'folio_no' => $user['folio_no'],
    //                 // 'sector' => $user['sector'],
    //                 // 'sub_sector' => $user['sub_sector'],
    //                 // 'thali_status' => $user['thali_status'],
    //                 // 'status' => $user['status'],

    //              ]);
    //          } else {
    //              // If it doesn't exist, add it to the batch for creation
    //              $batchData[] = [

    //                 'name' => $user['Name'],
    //                 // 'email' => $user_email,
    //                 'password' => bcrypt($user['Mobile']),
    //                 'family_id' => random_int(1000000000, 9999999999),
    //                 // 'title' => $user['title'],
    //                 'its' => $user_its,
    //                 'hof_its' => $user_its,
    //                 // 'family_its_id' => random_int(1000000000, 9999999999),
    //                 'mobile' => $user['Mobile'],
    //                 // 'address' => $user['address'],
    //                 // 'building' => $user['building'],
    //                 // 'flat_no' => $user['flat_no'],
    //                 // 'lattitude' => $user['lattitude'],
    //                 // 'longitude' => $user['longitude'],
    //                 'gender' => strtolower($user['Gender']),
    //                 // 'date_of_birth' => $user['date_of_birth'],
    //                 // 'folio_no' => $user['folio_no'],
    //                 // 'sector' => $user['sector'],
    //                 // 'sub_sector' => $user['sub_sector'],
    //                 // 'thali_status' => $user['thali_status'],
    //                 // 'status' => $user['status'],
    //              ];
 
    //              // Insert in batches of 1000 records
    //              if (count($batchData) == $batchSize) {
    //                  $this->insertBatch($batchData);
    //                  $batchData = []; // Clear batch after inserting
    //              }
    //          }
    //      }
 
    //      // Insert any remaining records after the loop
    //      if (count($batchData) > 0) {
    //          $this->insertBatch($batchData);
    //      }
 
    //      return response()->json(['message' => 'CSV import completed successfully.'], 200);
    //  }
 
     // Helper function to insert data in batches
    //  private function insertBatch($data)
    //  {
    //      try {
    //          // Disable query log for performance improvement
    //          DB::connection()->disableQueryLog();
             
    //          // Insert batch into users table
    //          User::insert($data);
    //      } catch (\Exception $e) {
    //          return response()->json(['error' => 'Error inserting batch: ' . $e->getMessage()], 500);
    //      }
    //  }

    // try csv
    public function importIts()
    {
        // Truncate the table to remove existing data
        ItsModel::truncate(); // Clears all existing records in the 'its' table

        $csvUrl = public_path('storage/KOLKATA_Mumineen_Database_26-Sep-24.csv');

        // Retrieve the CSV content from the URL
        $csvContent = file_get_contents($csvUrl);

        // Create a CSV reader instance from the content string
        $csv = Reader::createFromString($csvContent);
        $csv->setHeaderOffset(0); // Set header offset

        // Retrieve records from CSV
        $itsRecords = $csv->getRecords();
        $batchSize = 100; // Define batch size for processing
        $batchData = [];

        foreach ($itsRecords as $its) {
            $batchData[] = [
                'jamiat_id' => 1,
                'its'=>$its['ITS_ID'],
                'hof_its' => $its['HOF_ID'],
                'its_family_id' => $its['Family_ID'],
                'name' => $its['Full_Name'],
                'email' => $its['Email'],
                'mobile' => $its['Mobile'],
                'title' => $its['First_Prefix'],
                'mumeneen_type' => $its['HOF_FM_TYPE'],
                'gender' => $its['Gender'],
                'age' => $its['Age'],
                'sector' => $its['Sector'],
                'sub_sector' => $its['Sub_Sector'],
                'name_arabic' => $its['Full_Name_Arabic'],
                'address' => $its['Address'],
                'whatsapp_mobile' =>$its['WhatsApp_No']
            ];
    
            // Insert in batches of 100 records
            if (count($batchData) >= $batchSize) {
                $this->insertBatch($batchData);
                $batchData = []; // Clear batch after insertion
            }
        }
    
        // Insert any remaining records
        if (count($batchData) > 0) {
            $this->insertBatch($batchData);
        }
    
        return response()->json(['message' => 'CSV import completed successfully, and existing data was truncated.'], 200);    
    }

    // Helper function to insert data in batches
    private function insertBatch($data)
    {
        try {
            // Disable query log for performance improvement
            DB::connection()->disableQueryLog();

            // Insert batch into users table
            ItsModel::insert($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error inserting batch: ' . $e->getMessage()], 500);
        }
    }
}
