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
       // SubSectorModel::truncate();

        $csvUrl = public_path('storage/t_sub_sector.csv'); // Path to your sub-sector CSV file

        // Retrieve the CSV content
        $csvContent = file_get_contents($csvUrl);

        // Create a CSV reader instance
        $csv = Reader::createFromString($csvContent);
       // $csv->setDelimiter(';'); // Set the delimiter to semicolon
        $csv->setHeaderOffset(0); // Set header offset

        // Retrieve records from the CSV
        $subSectorRecords = $csv->getRecords();

        foreach ($subSectorRecords as $record) {
            SubSectorModel::create([
                'jamiat_id' => 1, // Hard-coded as per your requirement
                'sector' => $record['sector'],
                'name' => $record['name'], // Mapping 'sub_sector' to 'name'
                'notes' => $record['notes'],
                'log_user' => $record['log_user'],
                'created_at' => $record['created_at'],
                'updated_at' => $record['updated_at'],
            ]);
        }

        return response()->json(['message' => 'Sub-sector CSV import completed successfully, and existing data was truncated.'], 200);
    }
}
