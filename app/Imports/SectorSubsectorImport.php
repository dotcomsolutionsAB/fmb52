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
    protected $jamiat_id;

    public function __construct($jamiat_id)
    {
        $this->jamiat_id = $jamiat_id;
    }

    public function model(array $row)
    {
        // Skip rows where sector_name is missing
        if (empty($row['sector_name'])) {
            Log::warning('Skipping row due to missing sector name: ', $row);
            return null;
        }

        // Update or create the sector
        $sector = SectorModel::updateOrCreate(
            ['jamiat_id' => $this->jamiat_id, 'name' => $row['sector_name']],
            [
                'notes' => $row['sector_notes'] ?? 'Added by Excel upload',
                'log_user' => auth()->user()->username ?? 'system',
                'updated_at' => now(),
            ]
        );

        // Update or create the subsector if provided
        if (!empty($row['subsector_name'])) {
            SubSectorModel::updateOrCreate(
                ['jamiat_id' => $this->jamiat_id, 'sector_id' => $sector->id, 'name' => $row['subsector_name']],
                [
                    'notes' => $row['subsector_notes'] ?? 'Added by Excel upload',
                    'log_user' => auth()->user()->username ?? 'system',
                    'updated_at' => now(),
                ]
            );
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