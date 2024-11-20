<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\BuildingModel;
use App\Models\HubModel;

class MigrateData extends Command
{
    // The name and signature of the console command
    protected $signature = 'migrate:data';

    // The console command description
    protected $description = 'Migrate data from the API to the database in batches';

    // Execute the console command
    public function handle()
    {
        // Step 1: Truncate existing data
        User::where('role', 'mumeneen')->where('jamiat_id', 1)->delete();
        BuildingModel::where('jamiat_id', 1)->delete();
        HubModel::where('jamiat_id', 1)->delete();

        // Step 2: Fetch data from the API
        $url = 'https://www.faizkolkata.com/assets/custom/migrate/laravel/mumeneen.php';
        $response = Http::get($url);

        if ($response->failed()) {
            $this->error('Failed to fetch data from the API');
            return;
        }

        $families = $response->json();

        // Step 3: Process data in batches
        $batchSize = 100;
        $chunks = array_chunk($families, $batchSize);

        foreach ($chunks as $familyBatch) {
            foreach ($familyBatch as $family) {
                $buildingId = null;
                $address = $family['address'] ?? [];

                // Step 3a: Save or update building data if present
                if (!empty($address)) {
                    $building = BuildingModel::updateOrCreate(
                        ['name' => $address['address_2']],
                        [
                            'jamiat_id' => 1,
                            'name' => $address['address_2'],
                            'address_lime_1' => $address['address_1'] ?? null,
                            'address_lime_2' => $address['address_2'] ?? null,
                            'city' => $address['city'] ?? null,
                            'pincode' => $address['pincode'] ?? null,
                            'state' => null,
                            'lattitude' => $address['latitude'] ?? null,
                            'longtitude' => $address['longitude'] ?? null,
                            'landmark' => null,
                        ]
                    );
                    $buildingId = $building->id;
                }

                // Step 3b: Loop through each family member and save in User model
                foreach ($family['members'] as $member) {
                    $gender = (strtolower($member['gender']) === 'male' || strtolower($member['gender']) === 'female') ? strtolower($member['gender']) : null;
                    $title = ($member['title'] === 'Shaikh' || strtolower($member['title']) === 'Mulla') ? $member['title'] : null;

                    User::updateOrCreate(
                        ['its' => $member['its']],
                        [
                            'name' => $member['name'],
                            'email' => $member['email'],
                            'password' => bcrypt('default_password'),
                            'jamiat_id' => 1,
                            'family_id' => $family['family_id'],
                            'title' => $title,
                            'its' => substr($member['its'], 0, 8),
                            'hof_its' => $member['hof_id'],
                            'its_family_id' => $member['family_its_id'],
                            'mumeneen_type' => $member['type'],
                            'mobile' => (strlen($member['mobile']) <= 15) ? $member['mobile'] : null,
                            'gender' => $gender,
                            'folio_no' => $family['folio_no'],
                            'sector' => $family['sector'],
                            'sub_sector' => $family['sub_sector'],
                            'thali_status' => in_array($family['is_taking_thali'], ['taking', 'not_taking', 'once_a_week', 'joint']) ? $family['is_taking_thali'] : null,
                            'status' => $family['status'],
                            'username' => strtolower(str_replace(' ', '', substr($member['its'], 0, 8))),
                            'role' => 'mumeneen',
                            'building_id' => $buildingId
                        ]
                    );
                }

                // Step 3c: Save or update hub data for each year
                foreach ($family['hub_array'] as $hubEntry) {
                    HubModel::updateOrCreate(
                        [
                            'family_id' => $family['family_id'],
                            'year' => $hubEntry['year']
                        ],
                        [
                            'jamiat_id' => 1,
                            'hub_amount' => is_numeric($hubEntry['hub']) ? $hubEntry['hub'] : 0,
                            'paid_amount' => 0,
                            'due_amount' => is_numeric($hubEntry['hub']) ? $hubEntry['hub'] : 0,
                            'log_user' => 'system_migration'
                        ]
                    );
                }
            }

            // Pause to avoid overloading the server
            usleep(500000); // Sleep for 0.5 seconds
        }

        $this->info('Data migration completed successfully.');
    }
}
