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

            // Step 1: Reset paid and due amounts
            DB::table('t_hub')
                ->update([
                    'paid_amount' => 0,
                    'due_amount' => DB::raw('hub_amount + IFNULL(overdue, 0)'),
                    'updated_at' => now(),
                ]);

            // Step 2: Sum paid amounts by family and year
            $receiptSums = DB::table('t_receipts')
                ->select('family_id', 'year', DB::raw('SUM(amount) as total_paid'))
                ->whereNotNull('amount')
                ->where('status', '<>', 'cancelled')
                ->groupBy('family_id', 'year')
                ->get();

            // Step 3: Update paid and due amounts
            foreach ($receiptSums as $receiptSum) {
                DB::table('t_hub')
                    ->where('family_id', $receiptSum->family_id)
                    ->where('year', $receiptSum->year)
                    ->update([
                        'paid_amount' => DB::raw("paid_amount + {$receiptSum->total_paid}"),
                        'due_amount' => DB::raw("GREATEST(0, hub_amount + IFNULL(overdue, 0) - (paid_amount + {$receiptSum->total_paid}))"),
                        'updated_at' => now(),
                    ]);
            }

            // Step 4: Insert new hub entries if they don’t exist
            $existingRecords = DB::table('t_hub')
                ->select('family_id', 'year', 'overdue')
                ->get()
                ->keyBy(fn ($r) => $r->family_id . ':' . $r->year);

            $newHubs = [];
            foreach ($receiptSums as $receiptSum) {
                $key = $receiptSum->family_id . ':' . $receiptSum->year;
                if (!isset($existingRecords[$key])) {
                    $overdue = strcmp($receiptSum->year, $currentYear) < 0 ? max(0, 0 - $receiptSum->total_paid) : 0;
                    $newHubs[] = [
                        'jamiat_id' => 1,
                        'family_id' => $receiptSum->family_id,
                        'year' => $receiptSum->year,
                        'hub_amount' => 0,
                        'paid_amount' => $receiptSum->total_paid,
                        'overdue' => $overdue,
                        'due_amount' => $overdue,
                        'log_user' => 'system_cron',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($newHubs)) {
                DB::table('t_hub')->insert($newHubs);
            }

            // Step 5: Compute carry-forward overdue into 1446-1447
            $previousDue = DB::table('t_hub')
                ->select('family_id', DB::raw('SUM(hub_amount + IFNULL(overdue, 0) - paid_amount) AS total_overdue'))
                ->where('year', '<', $currentYear)
                ->groupBy('family_id')
                ->having('total_overdue', '>', 0)
                ->get();

            $existing1446 = DB::table('t_hub')
                ->where('year', $currentYear)
                ->pluck('year', 'family_id')
                ->toArray();

            foreach ($previousDue as $entry) {
                if (isset($existing1446[$entry->family_id])) {
                    DB::table('t_hub')
                        ->where('family_id', $entry->family_id)
                        ->where('year', $currentYear)
                        ->where(function ($q) {
                            $q->whereNull('overdue')->orWhere('overdue', 0);
                        })
                        ->update([
                            'overdue' => $entry->total_overdue,
                            'due_amount' => DB::raw("hub_amount + {$entry->total_overdue} - paid_amount"),
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('t_hub')->insert([
                        'jamiat_id' => 1,
                        'family_id' => $entry->family_id,
                        'year' => $currentYear,
                        'hub_amount' => 0,
                        'paid_amount' => 0,
                        'overdue' => $entry->total_overdue,
                        'due_amount' => $entry->total_overdue,
                        'log_user' => 'system_cron',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $this->info('Hub table updated successfully.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
