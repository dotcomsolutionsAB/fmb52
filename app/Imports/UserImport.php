<?php

namespace App\Imports;

use App\Models\User;
use App\Models\SectorModel;
use App\Models\SubSectorModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, ShouldQueue
{
    protected $errors = [];


    public function model(array $row)

    {
        ini_set('max_execution_time', 600);  // 5 minutes or more
ini_set('memory_limit', '2048M');    // you already set this, can increase if needed
        $jamiat_id = auth()->user()->jamiat_id;

        if (!$jamiat_id) {
            $this->errors[] = "Jamiat ID is missing for the authenticated user.";
            return null;
        }

        if (empty($row['its_id']) || empty($row['full_name'])) {
            $this->errors[] = "Skipping row due to missing ITS ID or Full Name.";
            return null;
        }

        try {
            // Generate unique Family ID if missing
            $family_id = $row['family_id'] ?? $this->generateUniqueFamilyId();

            // Match sector and subsector IDs
            $sector_id = $this->getSectorId($row['sector'] ?? null, $jamiat_id);
            $sub_sector_id = $this->getSubSectorId($sector_id, $row['sub_sector'] ?? null, $jamiat_id);

            // Create a new user
            User::create([
                'username' => $row['its_id'],
                'role' => 'mumeneen',
                'name' => $row['full_name'],
                'jamiat_id' => $jamiat_id,
                'hof_its' => $row['hof_id'] ?? $row['its_id'], // Default HOF_ITS to ITS if missing
                'its_family_id' => $row['hof_id'] ?? null,
                'family_id' => $family_id,
                'email' => $row['email'] ?? 'noemail@example.com',
                'mobile' => $row['mobile'] ?? null,
                'gender' => strtolower($row['gender'] ?? 'unknown'),
                'age' => $row['age'] ?? null,
                'building' => $row['building'] ?? $row['address'] ?? 'N/A',
                'sector_id' => $sector_id,
                'sub_sector_id' => $sub_sector_id,
                'password' => Hash::make($row['its_id']), // Default password as ITS number
                'created_at' => now(),
                'updated_at' => now(),
                'log_user' => auth()->user()->username,
            ]);

            // Add a zero entry in the t_hub table for new users
            $this->addHubEntry($family_id, $jamiat_id);
             Log::info('Imported row', [
            'its_id' => $row['its_id'] ?? 'N/A',
            'full_name' => $row['full_name'] ?? 'N/A',
        ]);


        }  catch (\Exception $e) {
        // Log error with details
        Log::error('Failed to import row', [
            'its_id' => $row['its_id'] ?? 'N/A',
            'full_name' => $row['full_name'] ?? 'N/A',
            'error' => $e->getMessage(),
        ]);
        }
    }

    private function generateUniqueFamilyId()
    {
        do {
            $family_id = rand(1000000000, 9999999999); // Generate 10-digit random number
        } while (User::where('family_id', $family_id)->exists());

        return $family_id;
    }

    private function getSectorId($sector_name, $jamiat_id)
    {
        if (!$sector_name) {
            $this->errors[] = "Missing sector name for a row.";
            return null;
        }

        $sector = SectorModel::where('jamiat_id', $jamiat_id)
                             ->where('name', $sector_name)
                             ->first();

        if (!$sector) {
            $this->errors[] = "Sector not found: {$sector_name}.";
            return null;
        }

        return $sector->id;
    }

    private function getSubSectorId($sector_id, $sub_sector_name, $jamiat_id)
    {
        if (!$sector_id || !$sub_sector_name) {
            $this->errors[] = "Missing sector ID or subsector name for a row.";
            return null;
        }

        $sub_sector = SubSectorModel::where('jamiat_id', $jamiat_id)
                                    ->where('sector_id', $sector_id)
                                    ->where('name', $sub_sector_name)
                                    ->first();

        if (!$sub_sector) {
            $this->errors[] = "Subsector not found: {$sub_sector_name} in sector ID {$sector_id}.";
            return null;
        }

        return $sub_sector->id;
    }

    private function addHubEntry($family_id, $jamiat_id)
    {
        try {
            // Fetch the current year from the year table
            $currentYear = DB::table('year')
                ->where('jamiat_id', $jamiat_id)
                ->where('is_current', 1)
                ->value('year');
    
            if (!$currentYear) {
                $this->errors[] = "No current year found for Jamiat ID: {$jamiat_id}.";
                return;
            }
    
            // Add a new record to the t_hub table
            DB::table('t_hub')->insert([
                'jamiat_id' => $jamiat_id,
                'family_id' => $family_id,
                'year' => $currentYear,
                'hub_amount' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'log_user' => auth()->user()->username,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            $this->errors[] = "Failed to add hub entry for Family ID: {$family_id}. Error: " . $e->getMessage();
        }
    }

    public function rules(): array
    {
        return [
            'its_id' => 'required|integer',
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

    public function getErrors()
    {
        return $this->errors;
    }
     public function chunkSize(): int
    {
        return 100;  // adjust this number based on memory & performance
    }
}