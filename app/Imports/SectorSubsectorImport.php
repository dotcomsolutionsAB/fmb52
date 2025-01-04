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
        $jamiat_id = auth()->user()->jamiat_id;
    
        if (!$jamiat_id) {
            return response()->json([
                'success' => false,
                'message' => 'Jamiat ID is required and missing for the authenticated user.',
            ], 400);
        }
        
        // Skip rows where sector_name is missing
        if (empty($row['sector'])) {
            return null;
        }

        try {
            // Update or create the sector
            $sector = SectorModel::firstOrCreate(
                ['jamiat_id' => $this->jamiat_id, 'name' => $row['Sector']],
                [
                    'notes' => $row['sector_notes'] ?? 'Added by Excel upload',
                    'log_user' => auth()->user()->username ?? 'system',
                    'updated_at' => now(),
                ]
            );

            // Update or create the subsector if provided
            if (!empty($row['Sub_Sector'])) {
                SubSectorModel::firstOrCreate(
                    ['jamiat_id' => $this->jamiat_id, 'sector_id' => $sector->id, 'name' => $row['Sub_Sector']],
                    [
                        'notes' => $row['subsector_notes'] ?? 'Added by Excel upload',
                        'log_user' => auth()->user()->username ?? 'system',
                        'updated_at' => now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error("Error processing row: " . $e->getMessage(), $row);
        }
    }

    public function rules(): array
    {
        return [
            'sector_name' => 'nullable|string', // Allow skipping rows without sector_name
            'subsector_name' => 'nullable|string',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'sector_name.required' => 'Sector name is required.',
        ];
    }
}