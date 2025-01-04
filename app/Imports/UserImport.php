<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Sector;
use App\Models\SubSector;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UserImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $jamiat_id;

    public function __construct($jamiat_id)
    {
        $this->jamiat_id = $jamiat_id;
    }

    public function model(array $row)
    {
        if (empty($row['its_id']) || empty($row['full_name'])) {
            Log::info('Skipping row due to missing ITS or Full Name: ', $row);
            return null;
        }

        // Generate unique Family ID if missing
        $family_id = $row['family_id'] ?? $this->generateUniqueFamilyId();

        // Match or log sector and subsector
        $sector_id = $this->getSectorId($row['sector'] ?? null);
        $sub_sector_id = $this->getSubSectorId($sector_id, $row['sub_sector'] ?? null);

        return User::updateOrCreate(
            ['its' => $row['its_id']],
            [
                'username' => $row['its_id'],
                'role' => 'mumeneen',
                'name' => $row['full_name'],
                'jamiat_id' => $this->jamiat_id,
                'hof_its' => $row['hof_id'] ?? $row['its_id'], // Default HOF_ITS to ITS if missing
                'its_family_id' => $row['hof_id'] ?? null,
                'family_id' => $family_id,
                'email' => $row['email'] ?? 'noemail@example.com',
                'mobile' => $row['mobile'] ?? null,
                'gender' => strtolower($row['gender'] ?? 'unknown'),
                'age' => $row['age'] ?? null,
                'address' => $row['building'] ?? $row['address'] ?? 'N/A',
                'sector_id' => $sector_id,
                'sub_sector_id' => $sub_sector_id,
                'password' => Hash::make($row['its_id']), // Default password as ITS number
                'created_at' => now(),
                'updated_at' => now(),
                'log_user' => auth()->user()->username,
            ]
        );
    }

    private function generateUniqueFamilyId()
    {
        do {
            $family_id = rand(1000000000, 9999999999); // Generate 10-digit random number
        } while (User::where('family_id', $family_id)->exists());

        return $family_id;
    }

    private function getSectorId($sector_name)
    {
        if (!$sector_name) {
            Log::info('Missing sector name for a row');
            return null;
        }

        $sector = Sector::where('jamiat_id', $this->jamiat_id)
                        ->where('name', $sector_name)
                        ->first();

        if (!$sector) {
            Log::info("Sector not found: {$sector_name}");
            return null;
        }

        return $sector->id;
    }

    private function getSubSectorId($sector_id, $sub_sector_name)
    {
        if (!$sector_id || !$sub_sector_name) {
            Log::info('Missing sector ID or subsector name for a row');
            return null;
        }

        $sub_sector = SubSector::where('jamiat_id', $this->jamiat_id)
                               ->where('sector_id', $sector_id)
                               ->where('name', $sub_sector_name)
                               ->first();

        if (!$sub_sector) {
            Log::info("Subsector not found: {$sub_sector_name} in sector ID {$sector_id}");
            return null;
        }

        return $sub_sector->id;
    }

    public function rules(): array
    {
        return [
            'its_id' => 'required|string',
            'full_name' => 'required|string',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'its_id.required' => 'ITS number is required.',
            'full_name.required' => 'Full Name is required.',
        ];
    }
}