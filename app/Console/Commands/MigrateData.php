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

        // Step 2: Fetch data from the API in batches
        $url = 'https://www.faizkolkata.com/assets/custom/migrate/laravel/mumeneen.php';
        $limit = 500; // Batch size
        $offset = 0;

        while (true) {
            $response = Http::get($url, ['limit' => $limit, 'offset' => $offset]);

            if ($response->failed()) {
                $this->error("Failed to fetch data for offset $offset");
                break;
            }

            $families = $response->json()['data'] ?? [];

            if (empty($families)) {
                $this->info('No more data to process.');
                break;
            }

            $entriesProcessed = $this->processBatch($families);
            $offset += $limit;

            // Log the success message for the current batch
            $this->info("Batch completed: {$entriesProcessed} entries processed for offset $offset.");
        }

        $this->info('Data migration completed successfully.');
    }

    /**
     * Process a batch of families data and save to the database.
     *
     * @param array $families
     * @return int Total number of entries processed (inserted or updated)
     */
    protected function processBatch(array $families)
    {
        $totalProcessed = 0;

        foreach ($families as $family) {
            $buildingId = null;
            $address = $family['address'] ?? [];

            // Save or update building data if present
            if (!empty($address)) {
                $building = BuildingModel::updateOrCreate(
                    ['name' => $address['address_2'] ?? 'Unknown'],
                    [
                        'jamiat_id' => 1,
                        'name' => $address['address_2'] ?? null,
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

            // Safely handle members data
            $members = $family['members'] ?? [];
            foreach ($members as $member) {
                $gender = (strtolower($member['gender']) === 'male' || strtolower($member['gender']) === 'female') ? strtolower($member['gender']) : null;
                $title = ($member['title'] === 'Shaikh' || strtolower($member['title']) === 'Mulla') ? $member['title'] : null;


                $thaliStatusMap = [
                    1 => 'taking',
                    2 => 'not_taking',
                    3 => 'once_a_week',
                    9 => 'joint',
                    0 => 'other_centre',
                ];  
                $thaliStatusString = $thaliStatusMap[$family['is_taking_thali']] ?? 'not_taking'; // Default is 'not_taking'
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
                        'thali_status' =>$thaliStatusString,// Default is 'not_taking'
                        'status' => $family['status'],
                        'username' => strtolower(str_replace(' ', '', substr($member['its'], 0, 8))),
                        'role' => 'mumeneen',
                        'building_id' => $buildingId
                    ]
                );

                $totalProcessed++;
            }

            // Safely handle hub array data
            $hubArray = $family['hub_array'] ?? [];
            foreach ($hubArray as $hubEntry) {
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

                $totalProcessed++;
            }
        }

        return $totalProcessed;
    }
}
