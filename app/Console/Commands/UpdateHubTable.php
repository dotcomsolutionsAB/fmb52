<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateHubTable extends Command
{
    protected $signature = 'hub:update';
    protected $description = 'Update t_hub table with paid, due, and overdue based on receipts and rules';

    public function handle()
    {
        try {
            $currentYear = '1446-1447';

            // Step 1: Fetch all active family IDs with valid family_id
            $activeFamilyIds = DB::table('users')
                ->where('status', 'active')
                ->whereNotNull('family_id')
                ->where('family_id', '!=', '')
                ->pluck('family_id')
                ->unique()
                ->toArray();

            if (empty($activeFamilyIds)) {
                $this->warn('No active families found.');
                return;
            }

            // Step 2: Reset paid_amount and due_amount (overdue stays as-is)
            DB::table('t_hub')
                ->whereIn('family_id', $activeFamilyIds)
                ->update([
                    'paid_amount' => 0,
                    'due_amount' => 0,
                    'updated_at' => now(),
                ]);

            // Step 3: Fetch receipts grouped by family and year
            $receiptsGrouped = DB::table('t_receipts')
                ->whereIn('family_id', $activeFamilyIds)
                ->whereNotNull('amount')
                ->where('status', '<>', 'cancelled')
                ->select('family_id', 'year', DB::raw('SUM(amount) as total_paid'))
                ->groupBy('family_id', 'year')
                ->orderBy('year')
                ->get()
                ->groupBy('family_id');

            // Step 4: Process each family
            foreach ($activeFamilyIds as $family_id) {
                $familyReceipts = $receiptsGrouped[$family_id] ?? collect();
                
                // Step 4a: Calculate total overdue from previous years (based on hub entries)
                $previousHubEntries = DB::table('t_hub')
                    ->where('family_id', $family_id)
                    ->where('year', '<', $currentYear)
                    ->get();

                $totalOverdue = 0;
                foreach ($previousHubEntries as $hubEntry) {
                    $paidForYear = $familyReceipts->where('year', $hubEntry->year)->sum('total_paid') ?? 0;
                    $dueForYear = max(0, $hubEntry->hub_amount - $paidForYear);
                    $totalOverdue += $dueForYear;

                    // Update past year hub paid_amount and due_amount, overdue = 0
                    DB::table('t_hub')->where('id', $hubEntry->id)->update([
                        'paid_amount' => $paidForYear,
                        'due_amount' => $dueForYear,
                        'overdue' => 0,
                        'updated_at' => now(),
                    ]);
                }

                // Step 4b: Handle current year hub entry
                $currentHub = DB::table('t_hub')
                    ->where('family_id', $family_id)
                    ->where('year', $currentYear)
                    ->first();

                $currentYearPaid = $familyReceipts->where('year', $currentYear)->sum('total_paid') ?? 0;
                if ($currentHub) {
                    $dueCurrentYear = max(0, $currentHub->hub_amount + $totalOverdue - $currentYearPaid);

                    DB::table('t_hub')->where('id', $currentHub->id)->update([
                        'paid_amount' => $currentYearPaid,
                        'due_amount' => $dueCurrentYear,
                        'overdue' => $totalOverdue,
                        'updated_at' => now(),
                    ]);
                } else {
                    // Insert current year hub entry if missing
                    DB::table('t_hub')->insert([
                        'jamiat_id' => 1, // Adjust if needed dynamically
                        'family_id' => $family_id,
                        'year' => $currentYear,
                        'hub_amount' => 0,
                        'paid_amount' => $currentYearPaid,
                        'due_amount' => max(0, 0 + $totalOverdue - $currentYearPaid),
                        'overdue' => $totalOverdue,
                        'log_user' => 'system_cron',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $this->info('Hub table updated successfully with new due/overdue logic.');
        } catch (\Exception $e) {
            $this->error('Error updating hub: ' . $e->getMessage());
        }
    }
}