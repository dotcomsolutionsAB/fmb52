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
        if (empty($row['sector_name'])) {
            Log::info('Skipping row due to missing sector name: ', $row);
            return null;
        }

        $sector = SectorModel::updateOrCreate(
            ['jamiat_id' => $this->jamiat_id, 'name' => $row['sector_name']],
            [
                'notes' => $row['sector_notes'] ?? 'Added by Excel upload',
                'log_user' => auth()->user()->username,
                'updated_at' => now(),
            ]
        );

        if (!empty($row['subsector_name'])) {
            SubSectorModel::updateOrCreate(
                ['jamiat_id' => $this->jamiat_id, 'sector_id' => $sector->id, 'name' => $row['subsector_name']],
                [
                    'notes' => $row['subsector_notes'] ?? 'Added by Excel upload',
                    'log_user' => auth()->user()->username,
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function rules(): array
    {
        return [
            'sector_name' => 'required|string',
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