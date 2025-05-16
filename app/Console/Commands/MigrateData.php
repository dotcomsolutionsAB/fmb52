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
        $limit = 3000;
        $offset = 0;

        // Fetch sector and sub-sector mappings
     $sectorMapping = DB::table('t_sector')->pluck('id', 'name')->toArray();
$subSectorMapping = DB::table('t_sub_sector')
    ->select('id', 'name', 'sector_id')
    ->get()
    ->mapWithKeys(function ($item) {
        return ["{$item->sector_id}:{$item->name}" => $item->id];
    })
    ->toArray();

        while (true) {
            $response = Http::get($url, ['limit' => $limit, 'offset' => $offset]);

            if ($response->failed()) {
                $this->error("Failed to fetch data for offset $offset");
                break;
            }

            $responseData = $response->json();
          $families = is_array($responseData) ? $responseData : [];

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

    // Define status and thali status mappings
    $thaliStatusMapping = [
        1 => 'taking',
        2 => 'not_taking',
        3 => 'once_a_week',
        9 => 'joint',
        0 => 'other_centre',
    ];

    $statusMapping = [
        '0' => 'active',
        '1' => 'in_active',
    ];

    foreach ($families as $family) {
        $address = $family['address'] ?? [];
        $members = $family['members'] ?? [];
        $hubArray = $family['hub_array'] ?? [];

        // Resolve sector and sub-sector IDs
        $sectorName = $family['sector'] ?? null;
        $subSectorName = $family['sub_sector'] ?? null;
        $sectorId = $sectorMapping[$sectorName] ?? null;
        $subSectorKey = $sectorId ? "{$sectorId}:{$subSectorName}" : null;
        $subSectorId = $subSectorKey ? ($subSectorMapping[$subSectorKey] ?? null) : null;

        // Log missing sector or sub-sector
        if (!$sectorId || !$subSectorId) {
            DB::table('mylogs')->insert([
                'message' => "Invalid sector ({$sectorName}) or sub-sector ({$subSectorName}) for family {$family['family_id']}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            continue; // Skip this family to avoid saving users with null foreign keys
        }

        // Collect building data (unchanged)
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
            // Validate required fields
            $its = trim($member['its'] ?? '');
            if (empty($its) || empty($member['name'])) {
                DB::table('mylogs')->insert([
                    'message' => "Missing required fields (its: {$its}, name: {$member['name']}) for member in family {$family['family_id']}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                continue;
            }

            // Check for duplicate ITS in this batch
            if (in_array($its, array_column($usersData, 'its'))) {
                DB::table('mylogs')->insert([
                    'message' => "Duplicate ITS {$its} for family {$family['family_id']}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                continue;
            }

            $gender = (strtolower($member['gender']) === 'male' || strtolower($member['gender']) === 'female') ? strtolower($member['gender']) : null;
            $title = ($member['title'] === 'Shaikh' || strtolower($member['title']) === 'Mulla') ? $member['title'] : null;

            // Map status and thali status
            $statusString = $statusMapping[$family['status']] ?? 'active';
            $thaliStatusString = $thaliStatusMapping[$family['is_taking_thali']] ?? 'not_taking';

            $usersData[] = [
                'its' => $its,
                'name' => $member['name'],
                'email' => $member['email'] ?: null,
                'password' => bcrypt('default_password'),
                'jamiat_id' => 1,
                'family_id' => $family['family_id'],
                'title' => $title,
                'hof_its' => $member['hof_id'] ?: null,
                'its_family_id' => $member['family_its_id'] ?: null,
                'mumeneen_type' => $member['type'] ?: null,
                'mobile' => (strlen($member['mobile']) <= 15) ? $member['mobile'] : null,
                'gender' => $gender,
                'folio_no' => $family['folio_no'] ?: null,
                'sector_id' => $sectorId,
                'sub_sector_id' => $subSectorId,
                'thali_status' => $thaliStatusString,
                'status' => $statusString,
                'username' => strtolower(str_replace(' ', '', substr($its, 0, 8))),
                'role' => 'mumeneen',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Collect hub data (unchanged)
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

    // Save buildings (unchanged)
    if (!empty($buildingsData)) {
        try {
            BuildingModel::upsert($buildingsData, ['name'], ['updated_at']);
            $totalProcessed += count($buildingsData);
            $this->info("Inserted/Updated " . count($buildingsData) . " buildings");
        } catch (\Exception $e) {
            DB::table('mylogs')->insert([
                'message' => "Failed to upsert buildings: " . $e->getMessage(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // Save users
    if (!empty($usersData)) {
        try {
            $this->info("Attempting to upsert " . count($usersData) . " users");
            DB::transaction(function () use ($usersData) {
                User::upsert($usersData, ['its'], [
                    'name',
                    'email',
                    'password',
                    'jamiat_id',
                    'family_id',
                    'title',
                    'hof_its',
                    'its_family_id',
                    'mumeneen_type',
                    'mobile',
                    'gender',
                    'folio_no',
                    'sector_id',
                    'sub_sector_id',
                    'thali_status',
                    'status',
                    'username',
                    'role',
                    'updated_at',
                ]);
            });
            $totalProcessed += count($usersData);
            $this->info("Inserted/Updated " . count($usersData) . " users");
        } catch (\Exception $e) {
            DB::table('mylogs')->insert([
                'message' => "Failed to upsert users: " . $e->getMessage(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->error("Failed to upsert users: " . $e->getMessage());
        }
    } else {
        DB::table('mylogs')->insert([
            'message' => "No user data to upsert",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->warn("No user data to upsert");
    }

    return $totalProcessed;
}
}
