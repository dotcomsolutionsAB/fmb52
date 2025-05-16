<!-- 

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateHubTable extends Command
{
    // The name and signature of the console command
    protected $signature = 'hub:update';

    // The console command description
    protected $description = 'Update the hub table with paid and due amounts from receipts';

    // Execute the console command
    public function handle()
    {
        try {
            // Step 1: Reset paid_amount and due_amount for all families and years
            DB::table('t_hub')
                ->update([
                    'paid_amount' => 0,
                    'due_amount' => DB::raw('hub_amount'),
                    'updated_at' => now(),
                ]);
    
            // Step 2: Calculate total paid amounts grouped by family_id and year
            $receiptSums = DB::table('t_receipts')
                ->select('family_id', 'year', DB::raw('SUM(amount) as total_paid'))
                ->whereNotNull('amount')
                ->where('status', '<>', 'cancelled')
                ->groupBy('family_id', 'year')
                ->get();
    
            // Step 3: Update the hub table in batches
            foreach ($receiptSums as $receiptSum) {
                $familyId = $receiptSum->family_id;
                $year = $receiptSum->year;
                $totalPaid = $receiptSum->total_paid;
    
                DB::table('t_hub')
                    ->where('family_id', $familyId)
                    ->where('year', $year)
                    ->update([
                        'paid_amount' => DB::raw("paid_amount + $totalPaid"),
                        'due_amount' => DB::raw("GREATEST(0, hub_amount - paid_amount)"),
                        'updated_at' => now(),
                    ]);
            }
    
            // Step 4: Insert new records for families and years not in the hub table
            $existingRecords = DB::table('t_hub')
                ->select('family_id', 'year')
                ->get()
                ->keyBy(fn ($record) => $record->family_id . ':' . $record->year);
    
            $newHubs = [];
            foreach ($receiptSums as $receiptSum) {
                $key = $receiptSum->family_id . ':' . $receiptSum->year;
                if (!isset($existingRecords[$key])) {
                    $newHubs[] = [
                        'jamiat_id' => 1, // Assuming a default jamiat_id
                        'family_id' => $receiptSum->family_id,
                        'year' => $receiptSum->year,
                        'hub_amount' => 0,
                        'paid_amount' => $receiptSum->total_paid,
                        'due_amount' => max(0, 0 - $receiptSum->total_paid),
                        'log_user' => 'system_cron',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
    
            if (!empty($newHubs)) {
                DB::table('t_hub')->insert($newHubs);
            }
    
            $this->info('Hub table updated successfully.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
} -->

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateHubTable extends Command
{
    protected $signature = 'hub:update';
    protected $description = 'Update the hub table with paid, due, and overdue amounts from receipts';

    public function handle()
    {
        try {
            $currentYear = '1446-1447';

            // Step 1: Reset paid_amount and due_amount for all families and years
            DB::table('t_hub')
                ->update([
                    'paid_amount' => 0,
                    'due_amount' => DB::raw('hub_amount + IFNULL(overdue, 0)'),
                    'updated_at' => now(),
                ]);

            // Step 2: Calculate total paid amounts grouped by family_id and year
            $receiptSums = DB::table('t_receipts')
                ->select('family_id', 'year', DB::raw('SUM(amount) as total_paid'))
                ->whereNotNull('amount')
                ->where('status', '<>', 'cancelled')
                ->groupBy('family_id', 'year')
                ->get();

            // Step 3: Update the hub table in batches
            foreach ($receiptSums as $receiptSum) {
                $familyId = $receiptSum->family_id;
                $year = $receiptSum->year;
                $totalPaid = $receiptSum->total_paid;

                DB::table('t_hub')
                    ->where('family_id', $familyId)
                    ->where('year', $year)
                    ->update([
                        'paid_amount' => DB::raw("paid_amount + $totalPaid"),
                        'due_amount' => DB::raw("GREATEST(0, hub_amount + IFNULL(overdue, 0) - paid_amount)"),
                        'updated_at' => now(),
                    ]);
            }

            // Step 4: Insert new records for families and years not in the hub table
            $existingRecords = DB::table('t_hub')
                ->select('family_id', 'year', 'overdue')
                ->get()
                ->keyBy(fn ($record) => $record->family_id . ':' . $record->year);

            $newHubs = [];
            foreach ($receiptSums as $receiptSum) {
                $key = $receiptSum->family_id . ':' . $receiptSum->year;
                if (!isset($existingRecords[$key])) {
                    $isOverdueYear = strcmp($receiptSum->year, $currentYear) < 0;
                    $overdueAmount = $isOverdueYear ? max(0, 0 - $receiptSum->total_paid) : 0;

                    $newHubs[] = [
                        'jamiat_id' => 1,
                        'family_id' => $receiptSum->family_id,
                        'year' => $receiptSum->year,
                        'hub_amount' => 0,
                        'paid_amount' => $receiptSum->total_paid,
                        'overdue' => $overdueAmount,
                        'due_amount' => $overdueAmount,
                        'log_user' => 'system_cron',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($newHubs)) {
                DB::table('t_hub')->insert($newHubs);
            }

            $this->info('Hub table updated successfully.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}

