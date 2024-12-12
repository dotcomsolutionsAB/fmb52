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
    protected $signature = 'migrate:data';
    protected $description = 'Migrate data from the API to the database in batches';

    public function handle()
    {
        $url = 'https://www.faizkolkata.com/assets/custom/migrate/laravel/mumeneen.php';
        $limit = 500;
        $offset = 0;

        // Fetch sector and sub-sector mappings
        $sectorMapping = DB::table('t_sector')->pluck('id', 'name')->toArray();
        $subSectorMapping = DB::table('t_sub_sector')
            ->select('id', 'name', 'sector')
            ->get()
            ->mapWithKeys(function ($item) {
                return ["{$item->sector}:{$item->name}" => $item->id];
            })
            ->toArray();

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

            $entriesProcessed = $this->processBatch($families, $sectorMapping, $subSectorMapping);
            $offset += $limit;

            $this->info("Batch completed: {$entriesProcessed} entries processed for offset $offset.");
        }

        $this->info('Data migration completed successfully.');
    }

    protected function processBatch(array $families, array $sectorMapping, array $subSectorMapping)
    {
        $totalProcessed = 0;
        $buildingsData = [];
        $usersData = [];
        $hubsData = [];

        foreach ($families as $family) {
            $address = $family['address'] ?? [];
            $members = $family['members'] ?? [];
            $hubArray = $family['hub_array'] ?? [];

            // Resolve sector and sub-sector IDs
            $sectorName = $family['sector'] ?? null;
            $subSectorName = $family['sub_sector'] ?? null;
            $sectorId = $sectorMapping[$sectorName] ?? null;
            $subSectorId = $subSectorMapping["{$sectorName}:{$subSectorName}"] ?? null;

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
                    'sector_id' => $sectorId, // Foreign key to t_sector
                    'sub_sector_id' => $subSectorId, // Foreign key to t_sub_sector
                    'thali_status' => $family['is_taking_thali'],
                    'status' => $family['status'],
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
                        'year' => $hubEntry['year'],
                    ],
                    [
                        'jamiat_id' => 1,
                        'hub_amount' => is_numeric($hubEntry['hub']) ? $hubEntry['hub'] : 0,
                        'paid_amount' => DB::raw('GREATEST(paid_amount, 0)'),
                        'due_amount' => DB::raw('GREATEST(hub_amount - paid_amount, 0)'),
                        'log_user' => 'system_migration1',
                        'updated_at' => now(),
                    ]
                );
            }
        }

        if (!empty($buildingsData)) {
            BuildingModel::upsert($buildingsData, ['name'], ['updated_at']);
            $totalProcessed += count($buildingsData);
        }

        if (!empty($usersData)) {
            User::upsert($usersData, ['its'], ['updated_at']);
            $totalProcessed += count($usersData);
        }

        return $totalProcessed;
    }
}