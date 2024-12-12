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
    // User::where('role', 'mumeneen')->where('jamiat_id', 1)->delete();
        //BuildingModel::where('jamiat_id', 1)->delete();
    //HubModel::where('jamiat_id', 1)->delete();

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

    $buildingsData = [];
    $usersData = [];
    $hubsData = [];

    foreach ($families as $family) {
        $address = $family['address'] ?? [];
        $members = $family['members'] ?? [];
        $hubArray = $family['hub_array'] ?? [];

        // Collect building data
        if (!empty($address)) {
            $buildingsData[] = [
                'jamiat_id' => 1,
                'name' => $address['address_2'] ?? 'Unknown',
                'address_lime_1' => $address['address_1'] ?? null,
                'address_lime_2' => $address['address_2'] ?? null,
                'city' => $address['city'] ?? null,
                'pincode' => $address['pincode'] ?? null,
                'state' => null,
                'lattitude' => $address['latitude'] ?? null,
                'longtitude' => $address['longitude'] ?? null,
                'landmark' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Collect user data
        foreach ($members as $member) {
            $gender = (strtolower($member['gender']) === 'male' || strtolower($member['gender']) === 'female') ? strtolower($member['gender']) : null;
            $title = ($member['title'] === 'Shaikh' || strtolower($member['title']) === 'Mulla') ? $member['title'] : null;

            // Map status and thali status
            $statusMap = [
                '0' => 'active',
                '1' => 'in_active',
            ];
            $thaliStatusMap = [
                1 => 'taking',
                2 => 'not_taking',
                3 => 'once_a_week',
                9 => 'joint',
                0 => 'other_centre',
            ];

            $statusString = $statusMap[$family['status']] ?? 'active';
            $thaliStatusString = $thaliStatusMap[$family['is_taking_thali']] ?? 'not_taking';

            $usersData[] = [
                'its' => trim($member['its']),
                'name' => $member['name'],
                'email' => $member['email'],
                'password' => bcrypt('default_password'),
                'jamiat_id' => 1,
                'family_id' => $family['family_id'],
                'title' => $title,
                'hof_its' => $member['hof_id'],
                'its_family_id' => $member['family_its_id'],
                'mumeneen_type' => $member['type'],
                'mobile' => (strlen($member['mobile']) <= 15) ? $member['mobile'] : null,
                'gender' => $gender,
                'folio_no' => $family['folio_no'],
                'sector' => $family['sector'],
                'sub_sector' => $family['sub_sector'],
                'thali_status' => $thaliStatusString,
                'status' => $statusString,
                'username' => strtolower(str_replace(' ', '', substr($member['its'], 0, 8))),
                'role' => 'mumeneen',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Collect hub data
        foreach ($hubArray as $hubEntry) {
            HubModel::updateOrCreate(
                [
                    'family_id' => $family['family_id'],
                    'year' => $hubEntry['year'], // Unique keys
                ],
                [
                    'jamiat_id' => 1,
                    'hub_amount' => is_numeric($hubEntry['hub']) ? $hubEntry['hub'] : 0,
                    'paid_amount' => DB::raw('GREATEST(paid_amount, 0)'), // Retain existing value
                    'due_amount' => DB::raw('GREATEST(hub_amount - paid_amount, 0)'), // Calculate due amount
                    'log_user' => 'system_migration1',
                    'updated_at' => now(),
                ]
            );
        }
        
        // Perform bulk operation with upsert
       
    }

    // Perform bulk operations
    if (!empty($buildingsData)) {
        BuildingModel::upsert($buildingsData, ['name'], ['updated_at']);
        $totalProcessed += count($buildingsData);
    }

    if (!empty($usersData)) {
        User::upsert($usersData, ['its'], ['updated_at']);
        $totalProcessed += count($usersData);
    }

    if (!empty($hubsData)) {
        HubModel::upsert($hubsData, ['family_id', 'year'], ['updated_at']);
        $totalProcessed += count($hubsData);
    }

    return $totalProcessed;
}
}
