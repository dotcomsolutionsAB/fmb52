<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateHubTable extends Command
{
    protected $signature = 'hub:update';
    protected $description = 'Update t_hub table dynamically based on receipts: due and overdue calculation';

    public function handle()
    {
        try {
            $currentYear = '1446-1447';

            // Step 1: Fetch all active family IDs (non-null, non-empty)
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

            // Step 2: Reset hub values for active families
            DB::table('t_hub')
                ->whereIn('family_id', $activeFamilyIds)
                ->update([
                    'paid_amount' => 0,
                    'due_amount' => 0,
                    'overdue' => 0,
                    'updated_at' => now(),
                ]);

            // Step 3: Fetch and group receipts by family_id
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
                if (!isset($receiptsGrouped[$family_id])) {
                    continue; // skip if no receipts
                }

                $familyReceipts = $receiptsGrouped[$family_id];
                $totalPaid = $familyReceipts->sum('total_paid');
                $paidLeft = $totalPaid;
                $accumulatedOverdue = 0;

                // Step 5: Fetch hub entries for this family
                $hubRecords = DB::table('t_hub')
                    ->where('family_id', $family_id)
                    ->orderBy('year')
                    ->get();

                foreach ($hubRecords as $hub) {
    $year = $hub->year;
    $hubAmount = $hub->hub_amount;

    // Apply payment from the remaining balance
    $applied = min($paidLeft, $hubAmount);
    $paidLeft -= $applied;

    if ($year < $currentYear) {
        $yearOverdue = max(0, $hubAmount - $applied);
        $accumulatedOverdue += $yearOverdue;

        DB::table('t_hub')->where('id', $hub->id)->update([
            'paid_amount' => $applied,
            'due_amount' => 0,
            'overdue' => 0,
            'updated_at' => now(),
        ]);
    } elseif ($year == $currentYear) {
        $due = max(0, $hubAmount - $applied);

        DB::table('t_hub')->where('id', $hub->id)->update([
            'paid_amount' => $applied,
            'due_amount' => $due,
            'overdue' => $accumulatedOverdue,
            'updated_at' => now(),
        ]);
    }
    // skip future years
}
            }

            $this->info('✅ Hub table updated successfully with current logic.');
        } catch (\Exception $e) {
            $this->error('❌ Error while updating hub: ' . $e->getMessage());
        }
    }
}