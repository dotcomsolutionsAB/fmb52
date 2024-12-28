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
        return new ItsModel([
            'jamiat_id' => $this->jamiat_id,
            'its' => $row['ITS_ID'],
            'hof_its' => $row['HOF_ID'],
            'its_family_id' => $row['Family_ID'],
            'name' => $row['Full_Name'],
            'email' => $row['Email'],
            'mobile' => $row['Mobile'],
            'title' => $row['Title'],
            'mumeneen_type' => strtolower($row['HOF_FM_TYPE']), // Ensure consistency
            'gender' => strtolower($row['Gender']), // Ensure consistency
            'age' => $row['Age'],
            'sector' => $row['Sector'],
            'sub_sector' => $row['Sub_Sector'],
            'name_arabic' => $row['Full_Name_Arabic'],
            'address' => $row['Address'],
            'whatsapp_mobile' => $row['WhatsApp_No'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}