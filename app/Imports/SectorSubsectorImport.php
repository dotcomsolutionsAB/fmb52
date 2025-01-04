<?php

namespace App\Imports;

use App\Models\SectorModel;
use App\Models\SubSectorModel;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class SectorSubsectorImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $jamiat_id = auth()->user()->jamiat_id;

        if (!$jamiat_id) {
            Log::error('Jamiat ID is missing for the authenticated user.');
            return;
        }

        $uniquePairs = [];

        // Extract unique Sector and Sub_Sector pairs
        foreach ($rows as $row) {
            if (!empty($row['sector'])) {
                $sector = trim($row['sector']);
                $subsector = !empty($row['sub_sector']) ? trim($row['sub_sector']) : null;
                $uniquePairs["$sector|$subsector"] = [
                    'sector' => $sector,
                    'sub_sector' => $subsector,
                ];
            }
        }

        // Get all unique pairs
        $uniquePairs = array_values($uniquePairs);

        // Process Sectors
        $sectorMap = [];
        foreach ($uniquePairs as $pair) {
            if (!isset($sectorMap[$pair['sector']])) {
                $sector = SectorModel::firstOrCreate(
                    ['jamiat_id' => $jamiat_id, 'name' => $pair['sector']],
                    [
                        'notes' => 'Added via Excel Import',
                        'log_user' => auth()->user()->username ?? 'system',
                        'updated_at' => now(),
                    ]
                );
                $sectorMap[$pair['sector']] = $sector->id;
            }
        }

        // Process Sub_Sectors
        foreach ($uniquePairs as $pair) {
            if ($pair['sub_sector']) {
                SubSectorModel::firstOrCreate(
                    [
                        'jamiat_id' => $jamiat_id,
                        'sector_id' => $sectorMap[$pair['sector']],
                        'name' => $pair['sub_sector'],
                    ],
                    [
                        'notes' => 'Added via Excel Import',
                        'log_user' => auth()->user()->username ?? 'system',
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}