<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\BuildingModel;
use App\Models\HubModel;
use App\Models\SectorModel;
use App\Models\SubSectorModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMigrationBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $families;

    /**
     * Create a new job instance.
     */
    public function __construct(array $families)
    {
        $this->families = $families;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $users = [];
        $buildings = [];
        $hubs = [];

        // Fetch sector and sub-sector mappings in bulk
        $sectors = SectorModel::pluck('id', 'name')->toArray();
        $subSectors = SubSectorModel::pluck('id', 'name')->toArray();

        foreach ($this->families as $family) {
            $buildingId = null;
            $address = $family['address'] ?? [];

            // Save building data
            if (!empty($address['address_2'])) {
                $buildings[] = [
                    'jamiat_id' => 1,
                    'name' => $address['address_2'],
                    'address_lime_1' => $address['address_1'] ?? null,
                    'address_lime_2' => $address['address_2'] ?? null,
                    'city' => $address['city'] ?? null,
                    'pincode' => $address['pincode'] ?? null,
                    'lattitude' => $address['latitude'] ?? null,
                    'longtitude' => $address['longitude'] ?? null,
                ];
                $buildingId = count($buildings);
            }

            foreach ($family['members'] as $member) {
                $users[] = [
                    'name' => $member['name'],
                    'email' => $member['email'],
                    'password' => bcrypt('default_password'),
                    'jamiat_id' => 1,
                    'family_id' => $family['family_id'],
                    'title' => $member['title'],
                    'its' => substr($member['its'], 0, 8),
                    'hof_its' => $member['hof_id'],
                    'its_family_id' => $member['family_its_id'],
                    'mumeneen_type' => $member['type'],
                    'mobile' => (strlen($member['mobile']) <= 15) ? $member['mobile'] : null,
                    'gender' => $member['gender'],
                    'folio_no' => $family['folio_no'],
                    'sector_id' => $sectors[strtoupper($family['sector'])] ?? null,
                    'sub_sector_id' => $subSectors[strtoupper($family['sub_sector'])] ?? null,
                    'status' => $family['status'] == 1 ? 'in_active' : 'active',
                    'role' => 'mumeneen',
                    'building_id' => $buildingId,
                ];
            }

            foreach ($family['hub_array'] as $hubEntry) {
                $hubs[] = [
                    'family_id' => $family['family_id'],
                    'year' => $hubEntry['year'],
                    'jamiat_id' => 1,
                    'thali_status' => $family['is_taking_thali'],
                    'hub_amount' => is_numeric($hubEntry['hub']) ? $hubEntry['hub'] : 0,
                    'paid_amount' => 0,
                    'due_amount' => is_numeric($hubEntry['hub']) ? $hubEntry['hub'] : 0,
                    'log_user' => 'system_migration'
                ];
            }
        }

        // Bulk insert using transactions
        DB::transaction(function () use ($users, $buildings, $hubs) {
            if (!empty($buildings)) BuildingModel::insert($buildings);
            if (!empty($users)) User::insert($users);
            if (!empty($hubs)) HubModel::insert($hubs);
        });

        Log::info("Batch of " . count($users) . " users migrated successfully.");
    }
}
