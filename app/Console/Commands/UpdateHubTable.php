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

            // Step 1: Fetch all active family IDs
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

            // Step 2: Reset all hub data for active users
            DB::table('t_hub')
                ->whereIn('family_id', $activeFamilyIds)
                ->update([
                    'paid_amount' => 0,
                    'due_amount' => 0,
                    'overdue' => 0,
                    'updated_at' => now(),
                ]);

            // Step 3: Fetch all receipts (grouped by family & year)
            $receipts = DB::table('t_receipts')
                ->whereIn('family_id', $activeFamilyIds)
                ->whereNotNull('amount')
                ->where('status', '<>', 'cancelled')
                ->select('family_id', 'year', DB::raw('SUM(amount) as total_paid'))
                ->groupBy('family_id', 'year')
                ->orderBy('year')
                ->get()
                ->groupBy('family_id');

            // Step 4: Loop through each family
            foreach ($activeFamilyIds as $family_id) {
                $totalPaid = $receipts[$family_id]->sum('total_paid') ?? 0;
                $paidLeft = $totalPaid;
                $overdue = 0;

                // Get all hub entries for this family
                $hubs = DB::table('t_hub')
                    ->where('family_id', $family_id)
                    ->orderBy('year')
                    ->get();

                foreach ($hubs as $hub) {
                    $year = $hub->year;
                    $hubAmt = $hub->hub_amount;
                    $paidForYear = $receipts[$family_id]->where('year', $year)->sum('total_paid') ?? 0;

                    if ($year < $currentYear) {
                        // Past year - calculate overdue
                        $yearOverdue = max(0, $hubAmt - $paidForYear);
                        $overdue += $yearOverdue;

                        DB::table('t_hub')->where('id', $hub->id)->update([
                            'paid_amount' => $paidForYear,
                            'due_amount' => 0,
                            'overdue' => 0,
                            'updated_at' => now(),
                        ]);
                    } elseif ($year == $currentYear) {
                        // Current year
                        $applicablePaid = min($paidLeft, $hubAmt);
                        $due = max(0, $hubAmt - $applicablePaid);

                        DB::table('t_hub')->where('id', $hub->id)->update([
                            'paid_amount' => $applicablePaid,
                            'due_amount' => $due,
                            'overdue' => $overdue,
                            'updated_at' => now(),
                        ]);

                        $paidLeft -= $applicablePaid;
                    }
                    // For future years, skip
                }
            }

            $this->info('Hub table updated successfully with dynamic overdue and current year due amounts.');
        } catch (\Exception $e) {
            $this->error('Error while updating hub: ' . $e->getMessage());
        }
    }
}