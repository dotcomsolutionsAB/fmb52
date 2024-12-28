
<?php
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
        // Check and update or create a record
        ItsModel::updateOrCreate(
            // Condition to find an existing record
            ['its' => $row['ITS_ID']],
            // Fields to update or insert
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