<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Carbon\Carbon;
use App\Models\SubSectorModel; // Ensure your SubSector model is set up correctly

class SubSectorImportController extends Controller
{
    public function importSubSectorData()
    {
        // Truncate the sub_sector table to remove existing data
        SubSectorModel::truncate();

        $csvUrl = public_path('storage/sub_sector.csv'); // Path to your sub-sector CSV file

        // Retrieve the CSV content
        $csvContent = file_get_contents($csvUrl);

        // Create a CSV reader instance
        $csv = Reader::createFromString($csvContent);
        $csv->setDelimiter(';'); // Set the delimiter to semicolon
        $csv->setHeaderOffset(0); // Set header offset

        // Retrieve records from the CSV
        $subSectorRecords = $csv->getRecords();
        $batchSize = 100; // Number of records to process at a time
        $batchData = [];

        foreach ($subSectorRecords as $record) {
            $batchData[] = [
                'jamiat_id' => 1, // Hard-coded as per your requirement
                'sector' => $record['sector'],
                'name' => $record['sub_sector'], // Mapping 'sub_sector' to 'name'
                'notes' => 'Incharge: ' . $record['incharge'] . ', Folio: ' . $record['folio'] . ', Mobile: ' . $record['mobile'] . ', Email: ' . $record['email'],
                'log_user' => $record['log_user'],
                'created_at' => Carbon::parse($record['log_date'])->toDateTimeString(),
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

        return response()->json(['message' => 'Sub-sector CSV import completed successfully, and existing data was truncated.'], 200);
    }

    // Helper function to insert data in batches
    private function insertBatch($data)
    {
        try {
            // Disable query log for performance improvement
            DB::connection()->disableQueryLog();

            // Insert batch into sub_sector table
            SubSectorModel::insert($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error inserting batch: ' . $e->getMessage()], 500);
        }
    }
}
