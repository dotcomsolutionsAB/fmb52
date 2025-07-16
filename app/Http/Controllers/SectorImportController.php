<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Carbon\Carbon;
use App\Models\SectorModel; // Ensure your Sector model is set up correctly

class SectorImportController extends Controller
{
    public function importSectorData()
    {
        // Truncate the sector table to remove existing data
        //SectorModel::truncate();

        $csvUrl = public_path('storage/sector.csv'); // Path to your CSV file in the storage directory

        // Retrieve the CSV content from the file
        $csvContent = file_get_contents($csvUrl);

        // Create a CSV reader instance
        $csv = Reader::createFromString($csvContent);
        $csv->setHeaderOffset(0); // Set header offset

        // Retrieve records from the CSV
        $sectorRecords = $csv->getRecords();
        $batchSize = 100; // Number of records to process at a time
        $batchData = [];

        foreach ($sectorRecords as $sector) {
            $batchData[] = [
                'jamiat_id' => 1, // Hard-coded as per your requirement
                'name' => $sector['sector'],
                'notes' => 'Secretary: ' . $sector['secretary'] . ', Mobile: ' . $sector['mobile'] . ', Email: ' . $sector['email'],
                'log_user' => $sector['log_user'],
                'created_at' => Carbon::parse($sector['log_date'])->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
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

            // Insert batch into sector table
            SectorModel::insert($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error inserting batch: ' . $e->getMessage()], 500);
        }
    }
}
