<?php

namespace App\Jobs;

use App\Helpers\CustomLogger;
use Illuminate\Bus\Queueable;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ItsDataImport;
use App\Imports\SectorSubsectorImport;
use App\Imports\UserImport;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class ProcessItsImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;
    protected int $jamiat_id;

    /**
     * Create a new job instance.
     *
     * @param string $filePath
     * @param int $jamiat_id
     */
    public function __construct(string $filePath, int $jamiat_id)
    {
        $this->filePath = $filePath;
        $this->jamiat_id = $jamiat_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
   public function handle(): void
{
    // Increase PHP limits for heavy import processing
    ini_set('memory_limit', '2048M');
    set_time_limit(0);

    CustomLogger::log("Starting import job for Jamiat ID: {$this->jamiat_id}");

    try {
        // Check and import ITS data if missing
        $itsExists = DB::table('t_its_data')->where('jamiat_id', $this->jamiat_id)->exists();
        if (!$itsExists) {
            Excel::import(new ItsDataImport($this->jamiat_id), $this->filePath);
            CustomLogger::log("ITS data imported for Jamiat ID: {$this->jamiat_id}");
        } else {
            CustomLogger::log("Skipping ITS import: data already exists for Jamiat ID: {$this->jamiat_id}");
        }

        // Check and import sectors and subsectors if missing
        $sectorExists = DB::table('t_sector')->where('jamiat_id', $this->jamiat_id)->exists();
        if (!$sectorExists) {
            Excel::import(new SectorSubsectorImport($this->jamiat_id), $this->filePath);
            CustomLogger::log("Sectors and Subsectors imported for Jamiat ID: {$this->jamiat_id}");
        } else {
            CustomLogger::log("Skipping Sector/Subsector import: data already exists for Jamiat ID: {$this->jamiat_id}");
        }

        // Check and import users with role 'mumeneen' if none exist
        $userExists = DB::table('users')
            ->where('jamiat_id', $this->jamiat_id)
            ->where('role', 'mumeneen')
            ->exists();

        if (!$userExists) {
            Excel::import(new UserImport($this->jamiat_id, 'system_import'), $this->filePath);
            CustomLogger::log("Users imported for Jamiat ID: {$this->jamiat_id}");
        } else {
            CustomLogger::log("Skipping User import: users with role 'mumeneen' already exist for Jamiat ID: {$this->jamiat_id}");
        }

        CustomLogger::log("Import job completed successfully for Jamiat ID: {$this->jamiat_id}");

    } catch (\Throwable $e) {
        CustomLogger::log("Import job failed for Jamiat ID: {$this->jamiat_id}. Error: " . $e->getMessage());

        // Optionally re-throw or handle error
        throw $e;
    }
}
}