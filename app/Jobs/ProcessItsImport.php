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

class ProcessItsImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $jamiat_id;

    /**
     * Create a new job instance.
     *
     * @param string $filePath
     * @param int $jamiat_id
     */
    public function __construct($filePath, $jamiat_id)
    {
        $this->filePath = $filePath;
        $this->jamiat_id = $jamiat_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', '1024M');
set_time_limit(0);
        try {
            // You can check existence of data here if needed
            // For example, fetch from DB if data exists, and decide import accordingly
            
            Excel::import(new ItsDataImport(), $this->filePath);
            Log::info("ITS data imported for Jamiat ID: {$this->jamiat_id}");

            Excel::import(new SectorSubsectorImport(), $this->filePath);
            Log::info("Sectors and Subsectors imported for Jamiat ID: {$this->jamiat_id}");

            Excel::import(new UserImport($this->jamiat_id), $this->filePath);
            Log::info("Users imported for Jamiat ID: {$this->jamiat_id}");

        } catch (\Exception $e) {
            Log::error("Error during import process: " . $e->getMessage());
            // Optionally: notify admin or user about failure
        }
    }
}