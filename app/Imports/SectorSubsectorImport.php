<?php

namespace App\Imports;

use App\Models\SectorModel;
use App\Models\SubSectorModel;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SectorSubsectorImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // Fetch the authenticated user's jamiat_id
        $jamiat_id = auth()->user()->jamiat_id;

        if (!$jamiat_id) {
            Log::error('Jamiat ID is missing for the authenticated user.');
            return null;
        }

        // Skip rows where Sector is missing
        if (empty($row['sector'])) {
            Log::warning('Skipping row due to missing Sector: ', $row);
            return null;
        }

        try {
            // Update or create the Sector
            $sector = SectorModel::firstOrCreate(
                ['jamiat_id' => $jamiat_id, 'name' => $row['sector']],
                [
                    'notes' => 'Added via Excel Import',
                    'log_user' => auth()->user()->username ?? 'system',
                    'updated_at' => now(),
                ]
            );

            // Update or create the Sub_Sector if provided
            if (!empty($row['sub_sector'])) {
                SubSectorModel::firstOrCreate(
                    ['jamiat_id' => $jamiat_id, 'sector_id' => $sector->id, 'name' => $row['sub_sector']],
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
            'sector' => 'nullable|string', // Allow skipping rows without Sector
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