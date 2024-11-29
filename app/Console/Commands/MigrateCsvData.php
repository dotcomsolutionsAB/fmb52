<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class MigrateCsvData extends Command
{
    // The name and signature of the console command
    protected $signature = 'migrate:csvdata';

    // The console command description
    protected $description = 'Update users table with data from a CSV file';

    // Execute the console command
    public function handle()
    {
        $csvFilePath  = public_path('storage/Kolkata_ITS.csv');
       // te this path to your actual CSV file location

        if (!file_exists($csvFilePath)) {
            $this->error("CSV file not found at: {$csvFilePath}");
            return;
        }

        // Open the CSV file
        $fileHandle = fopen($csvFilePath, 'r');
        if (!$fileHandle) {
            $this->error('Failed to open the CSV file.');
            return;
        }

        // Read the CSV header row
        $header = fgetcsv($fileHandle);
        if (!$header) {
            $this->error('The CSV file is empty or has no header.');
            fclose($fileHandle);
            return;
        }

        // Define mappings for the CSV columns to database columns
        $columnMappings = [
            'ITS_ID' => 'its',
            'Email' => 'email',
            'Family_ID' => 'its_family_id',
            'Gender' => 'gender',
            'Age' => 'age',
            'Address' => 'building', // Mapping to 'building' column
        ];

        $updatedCount = 0;

        // Process each row in the CSV
        while (($row = fgetcsv($fileHandle)) !== false) {
            $rowData = array_combine($header, $row);

            if (!$rowData) {
                $this->error('Invalid row data encountered. Skipping.');
                continue;
            }

            // Prepare the user data for update
            $userData = [];
            foreach ($columnMappings as $csvColumn => $dbColumn) {
                $userData[$dbColumn] = $rowData[$csvColumn] ?? null;
            }

            // Update or create the user record
            User::updateOrCreate(
                ['its' => $userData['its']], // Match by 'ITS_ID'
                $userData
            );

            $updatedCount++;
        }

        fclose($fileHandle);

        $this->info("Successfully updated {$updatedCount} user records from the CSV file.");
    }
}