<?php

namespace App\Imports;


use App\Models\ItsModel;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ItsDataImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $jamiat_id;

   

    public function model(array $row)
    {
        $jamiat_id = auth()->user()->jamiat_id;
    
        if (!$jamiat_id) {
            return response()->json([
                'success' => false,
                'message' => 'Jamiat ID is required and missing for the authenticated user.',
            ], 400);
        }
        // Skip rows where `its_id` or `hof_id` is missing
        if (empty($row['its_id']) || empty($row['hof_id'])) {
            Log::info('Skipping row due to missing ITS_ID or HOF_ID: ', $row);
            return null; // Skip this row
        }

        // Process valid rows
        return ItsModel::updateOrCreate(
            ['its' => $row['its_id']], // Match by ITS_ID
            [
                'jamiat_id' => $this->jamiat_id,
                'hof_its' => $row['hof_id'],
                'its_family_id' => $row['family_id'] ?? null,
                'name' => $row['full_name'] ?? null,
                'email' => $row['email'] ?? null,
                'mobile' => $row['mobile'] ?? null,
                'title' => $row['title'] ?? null,
                'mumeneen_type' => strtolower($row['hof_fm_type'] ?? ''),
                'gender' => strtolower($row['gender'] ?? ''),
                'age' => $row['age'] ?? null,
                'sector' => $row['sector'] ?? null,
                'sub_sector' => $row['sub_sector'] ?? null,
                'name_arabic' => $row['full_name_arabic'] ?? null,
                'address' => $row['address'] ?? null,
                'whatsapp_mobile' => $row['whatsapp_no'] ?? null,
                'updated_at' => now(),
            ]
        );
    }

    public function rules(): array
    {
        return [
            'its_id' => 'nullable', // ITS_ID is checked in logic, not validation
            'hof_id' => 'nullable', // HOF_ID is checked in logic, not validation
            'family_id' => 'nullable',
            'full_name' => 'nullable',
            'email' => 'nullable|email', // Validate email if provided
            'mobile' => 'nullable', // Mobile can be nullable
        ];
    }

    public function customValidationMessages()
    {
        return [
            'email.email' => 'The Email field must be a valid email address.',
        ];
    }
}