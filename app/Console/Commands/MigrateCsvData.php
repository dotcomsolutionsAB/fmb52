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
    $csvFilePath = public_path('storage/Kolkata_ITS.csv');

    if (!file_exists($csvFilePath)) {
        $this->error("CSV file not found at: {$csvFilePath}");
        return;
    }

    $fileHandle = fopen($csvFilePath, 'r');
    if (!$fileHandle) {
        $this->error('Failed to open the CSV file.');
        return;
    }

    $header = fgetcsv($fileHandle);
    if (!$header) {
        $this->error('The CSV file is empty or has no header.');
        fclose($fileHandle);
        return;
    }

    $updatedCount = 0;

    while (($row = fgetcsv($fileHandle)) !== false) {
        $rowData = array_combine($header, $row);

        if (!$rowData) {
            $this->error('Invalid row data encountered. Skipping.');
            continue;
        }

        $user = User::where('its', $rowData['ITS_ID'])->first();

        if ($user) {
            $user->email = $rowData['Email'] ?? $user->email;
            $user->its_family_id = $rowData['Family_ID'] ?? $user->its_family_id;
            $user->gender = $rowData['Gender'] ?? $user->gender;
            $user->age = is_numeric($rowData['Age']) ? (int) $rowData['Age'] : $user->age;
            $user->building = $rowData['Address'] ?? $user->building;

            $user->save();

            $this->info("Updated user ID {$user->id}: " . json_encode([
                'email' => $user->email,
                'its_family_id' => $user->its_family_id,
                'gender' => $user->gender,
                'age' => $user->age,
                'building' => $user->building,
            ]));

            $updatedCount++;
        } else {
            $this->info("User with ITS ID {$rowData['ITS_ID']} not found. Skipping.");
        }
    }

    fclose($fileHandle);

    $this->info("Successfully updated {$updatedCount} user records from the CSV file.");
}
}