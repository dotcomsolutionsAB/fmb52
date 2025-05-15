<?php

namespace App\Jobs;

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
        // Increase limits for heavy processing
        ini_set('memory_limit', '2048M');
        set_time_limit(0);

        \Log::info("Starting import job for Jamiat ID: {$this->jamiat_id}");

        try {
            // Import ITS data only if not present
            $itsExists = DB::table('t_its_data')->where('jamiat_id', $this->jamiat_id)->exists();
            if (!$itsExists) {
                Excel::import(new ItsDataImport($this->jamiat_id), $this->filePath);
                \Log::info("ITS data imported for Jamiat ID: {$this->jamiat_id}");
            } else {
                \Log::info("Skipping ITS import: data already exists for Jamiat ID: {$this->jamiat_id}");
            }

            // Import sectors and subsectors only if not present
            $sectorExists = DB::table('t_sector')->where('jamiat_id', $this->jamiat_id)->exists();
            if (!$sectorExists) {
                Excel::import(new SectorSubsectorImport($this->jamiat_id), $this->filePath);
                \Log::info("Sectors and Subsectors imported for Jamiat ID: {$this->jamiat_id}");
            } else {
                \Log::info("Skipping Sector/Subsector import: data already exists for Jamiat ID: {$this->jamiat_id}");
            }

            // Import users only if none exist with role 'mumeneen'
            $userExists = DB::table('users')->where('jamiat_id', $this->jamiat_id)->where('role', 'mumeneen')->exists();
            if (!$userExists) {
                Excel::import(new UserImport($this->jamiat_id, 'system_import'), $this->filePath);
                \Log::info("Users imported for Jamiat ID: {$this->jamiat_id}");
            } else {
                \Log::info("Skipping User import: users with role 'mumeneen' already exist for Jamiat ID: {$this->jamiat_id}");
            }

            \Log::info("Import job completed successfully for Jamiat ID: {$this->jamiat_id}");

        } catch (\Throwable $e) {
            \Log::error("Import job failed for Jamiat ID: {$this->jamiat_id}. Error: " . $e->getMessage());

            // TODO: Optionally notify admin/user via email or notifications here

            // Re-throw or silently fail depending on your retry strategy
            throw $e;
        }
    }
}