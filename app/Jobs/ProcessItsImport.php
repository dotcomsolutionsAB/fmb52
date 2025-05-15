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

    protected $importJobId;

    public function __construct($importJobId)
    {
        $this->importJobId = $importJobId;
    }

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
{
    $importJob = ImportJob::find($this->importJobId);
    if (!$importJob) {
        \Log::error("Import job not found: {$this->importJobId}");
        return;
    }

    $jamiat_id = $importJob->jamiat_id;

    $importJob->update(['status' => 'processing']);

    try {
        Excel::import(new ItsDataImport(), $importJob->file_path);
        Excel::import(new SectorSubsectorImport(), $importJob->file_path);
        Excel::import(new UserImport($jamiat_id), $importJob->file_path);

        $importJob->update(['status' => 'completed', 'message' => 'Import completed successfully.']);
    } catch (\Exception $e) {
        \Log::error("Import job failed: " . $e->getMessage());
        $importJob->update([
            'status' => 'failed',
            'message' => $e->getMessage(),
        ]);
    }
}
}