<?php

namespace App\Imports;

use App\Models\SectorModel;
use App\Models\SubSectorModel;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class SectorSubsectorImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $jamiat_id = auth()->user()->jamiat_id;

        if (!$jamiat_id) {
            Log::error('Jamiat ID is missing for the authenticated user.');
            return null;
        }

        if (empty($row['sector'])) {
            Log::warning('Skipping row due to missing Sector: ', $row);
            return null;
        }

        try {
            // Fetch or Create the Sector
            $sector = SectorModel::firstOrCreate(
                ['jamiat_id' => $jamiat_id, 'name' => trim($row['sector'])],
                [
                    'notes' => 'Added via Excel Import',
                    'log_user' => auth()->user()->username ?? 'system',
                    'updated_at' => now(),
                ]
            );

            if (!$sector->id) {
                Log::error('Failed to retrieve sector_id for sector: ' . $row['sector']);
                return null;
            }

            // Fetch sector_id
            $sector_id = $sector->id;

            // Check and Create Sub_Sector
            if (!empty($row['sub_sector'])) {
                SubSectorModel::firstOrCreate(
                    [
                        'jamiat_id' => $jamiat_id,
                        'sector_id' => $sector_id, // Use retrieved sector_id
                        'name' => trim($row['sub_sector']),
                    ],
                    [
                        'notes' => 'Added via Excel Import',
                        'log_user' => auth()->user()->username ?? 'system',
                        'updated_at' => now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error('Error processing row: ' . $e->getMessage(), $row);
        }
    }

    public function rules(): array
    {
        return [
            'sector' => 'required|string',
            'sub_sector' => 'nullable|string',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'sector.required' => 'Sector name is required.',
        ];
    }
}