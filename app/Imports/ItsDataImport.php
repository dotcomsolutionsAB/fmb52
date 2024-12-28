<?php

namespace App\Imports;

use App\Models\ItsModel;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ItsDataImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $jamiat_id;

    /**
     * Constructor to accept the jamiat_id.
     */
    public function __construct($jamiat_id)
    {
        $this->jamiat_id = $jamiat_id;
    }

    /**
     * Map each row to a model for database insertion.
     */
    public function model(array $row)
    {
        // Log row data for debugging
        Log::info('Processing row: ', $row);

        // Check for missing ITS_ID and handle gracefully
        if (!isset($row['its_id'])) {
            throw new \Exception('Missing its_id in row: ' . json_encode($row));
        }

        // Use updateOrCreate to update existing data or create a new entry
        return ItsModel::updateOrCreate(
            ['its' => $row['its_id']], // Match by ITS_ID
            [
                'jamiat_id' => $this->jamiat_id,
                'hof_its' => $row['hof_id'] ?? null,
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

    /**
     * Define validation rules for each row.
     */
    public function rules(): array
    {
        return [
            'its_id' => 'required', // Ensure its_id exists in every row
            'hof_id' => 'nullable',
            'family_id' => 'required', // Ensure family_id exists
            'full_name' => 'required', // Ensure full_name exists
            'email' => 'nullable|email', // Validate email format if provided
            'mobile' => 'nullable', // Mobile can be null
        ];
    }

    /**
     * Customize validation messages (optional).
     */
    public function customValidationMessages()
    {
        return [
            'its_id.required' => 'The ITS_ID field is required.',
            'family_id.required' => 'The Family_ID field is required.',
            'full_name.required' => 'The Full_Name field is required.',
            'email.email' => 'The Email field must be a valid email address.',
        ];
    }
}