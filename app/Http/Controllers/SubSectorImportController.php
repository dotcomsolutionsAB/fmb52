<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Carbon\Carbon;
use App\Models\SubSectorModel;
use App\Models\SectorModel;

class SubSectorImportController extends Controller
{
    public function importSubSectorData()
    {
        // Path to your sub-sector CSV file
        $csvPath = public_path('storage/t_sub_sector.csv');

        if (!file_exists($csvPath)) {
            return response()->json(['error' => 'CSV file not found at ' . $csvPath], 404);
        }

        // Retrieve the CSV content
        $csvContent = file_get_contents($csvPath);

        // Create a CSV reader instance
        $csv = Reader::createFromString($csvContent);
        $csv->setHeaderOffset(0); // Use the first row as header

        $records = $csv->getRecords();
        $createdCount = 0;
        $errors = [];

        foreach ($records as $index => $record) {
            $sectorName = trim($record['sector']);

            // Find sector ID by sector name
            $sector = SectorModel::where('name', $sectorName)->first();

            if (!$sector) {
                $errors[] = "Row " . ($index + 1) . ": Sector '$sectorName' not found.";
                continue;
            }

            // Insert into sub_sector table
            SubSectorModel::create([
                'jamiat_id' => 1,
                'sector_id' => $sector->id,
                'name' => $record['name'],
                'notes' => $record['notes'] ?? null,
                'log_user' => $record['log_user'] ?? 'system',
                'created_at' => $record['created_at'] ?? now(),
                'updated_at' => $record['updated_at'] ?? now(),
            ]);

            $createdCount++;
        }

        return response()->json([
            'message' => "Sub-sector import completed. $createdCount rows inserted.",
            'errors' => $errors
        ]);
    }
}