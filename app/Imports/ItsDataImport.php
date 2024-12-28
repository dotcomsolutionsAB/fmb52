<?php
namespace App\Imports;

use App\Models\ItsModel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ItsDataImport implements ToModel, WithHeadingRow
{
    protected $jamiat_id;

    public function __construct($jamiat_id)
    {
        $this->jamiat_id = $jamiat_id;
    }

    public function model(array $row)
    {
        // Log the row data for debugging
        \Log::info('Processing row: ', $row);

        // Handle missing ITS_ID gracefully
        if (!isset($row['ITS_ID'])) {
            throw new \Exception('Missing ITS_ID in row: ' . json_encode($row));
        }

        return ItsModel::updateOrCreate(
            ['its' => $row['ITS_ID']],
            [
                'jamiat_id' => $this->jamiat_id,
                'hof_its' => $row['HOF_ID'] ?? null,
                'its_family_id' => $row['Family_ID'] ?? null,
                'name' => $row['Full_Name'] ?? null,
                'email' => $row['Email'] ?? null,
                'mobile' => $row['Mobile'] ?? null,
                'title' => $row['Title'] ?? null,
                'mumeneen_type' => strtolower($row['HOF_FM_TYPE'] ?? ''),
                'gender' => strtolower($row['Gender'] ?? ''),
                'age' => $row['Age'] ?? null,
                'sector' => $row['Sector'] ?? null,
                'sub_sector' => $row['Sub_Sector'] ?? null,
                'name_arabic' => $row['Full_Name_Arabic'] ?? null,
                'address' => $row['Address'] ?? null,
                'whatsapp_mobile' => $row['WhatsApp_No'] ?? null,
                'updated_at' => now(),
            ]
        );
    }
}