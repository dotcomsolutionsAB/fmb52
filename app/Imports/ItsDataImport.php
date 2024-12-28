<?php
namespace App\Imports;

use App\Models\ItsModel;
use Illuminate\Support\Facades\Auth;
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
        // Map Excel column names to database column names
        $mappedRow = [
            'jamiat_id' => $this->jamiat_id,
            'its' => $row['ITS_ID'] ?? $row['its_id'] ?? null, // Handle multiple possible names
            'hof_its' => $row['HOF_ID'] ?? $row['hof_id'] ?? null,
            'its_family_id' => $row['Family_ID'] ?? $row['family_id'] ?? null,
            'name' => $row['Full_Name'] ?? $row['full_name'] ?? null,
            'email' => $row['Email'] ?? $row['email'] ?? null,
            'mobile' => $row['Mobile'] ?? $row['mobile'] ?? null,
            'title' => $row['Title'] ?? $row['title'] ?? null,
            'mumeneen_type' => strtolower($row['HOF_FM_TYPE'] ?? $row['hof_fm_type'] ?? ''),
            'gender' => strtolower($row['Gender'] ?? $row['gender'] ?? ''),
            'age' => $row['Age'] ?? $row['age'] ?? null,
            'sector' => $row['Sector'] ?? $row['sector'] ?? null,
            'sub_sector' => $row['Sub_Sector'] ?? $row['sub_sector'] ?? null,
            'name_arabic' => $row['Full_Name_Arabic'] ?? $row['full_name_arabic'] ?? null,
            'address' => $row['Address'] ?? $row['address'] ?? null,
            'whatsapp_mobile' => $row['WhatsApp_No'] ?? $row['whatsapp_no'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    
       
        return new ItsModel($mappedRow);
    }
}