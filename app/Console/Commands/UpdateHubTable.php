<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateHubTable extends Command
{
    protected $signature = 'hub:update';
    protected $description = 'Update t_hub paid, overdue, and due amounts correctly per new rules';

    public function handle()
    {
        try {
            $currentYear = '1446-1447';

            // STEP 1: Clear paid/due/overdue for active users
            DB::table('t_hub')
                ->whereIn('family_id', function ($query) {
                    $query->select('family_id')->from('users')->where('status', 'active');
                })
                ->update([
                    'paid_amount' => 0,
                    'due_amount' => 0,
                    'overdue' => 0,
                    'updated_at' => now(),
                ]);

            // STEP 2: Get all active family_ids
            $activeFamilies = DB::table('users')
                ->where('status', 'active')
                ->pluck('family_id')
                ->unique()
                ->toArray();

            // STEP 3: Get all receipts for those families, grouped by year
            $allReceipts = DB::table('t_receipts')
                ->whereIn('family_id', $activeFamilies)
                ->where('status', '<>', 'cancelled')
                ->whereNotNull('amount')
                ->select('family_id', 'year', DB::raw('SUM(amount) as total_paid'))
                ->groupBy('family_id', 'year')
                ->orderBy('year')
                ->get();

            // STEP 4: Group receipts by family_id
            $receiptsByFamily = $allReceipts->groupBy('family_id');

            foreach ($receiptsByFamily as $family_id => $receipts) {
                $totalPaid = $receipts->sum('total_paid');
                $paidLeft = $totalPaid;

                // STEP 5: Get all hub records for the family, ordered by year
                $hubRecords = DB::table('t_hub')
                    ->where('family_id', $family_id)
                    ->orderBy('year')
                    ->get();

                $updates = [];

                foreach ($hubRecords as $hub) {
                    $hubYear = $hub->year;
                    $hubAmt = $hub->hub_amount;

                    // Paid for this year (from receipts)
                    $paidForYear = $receipts->where('year', $hubYear)->sum('total_paid');

                    // Apply overdue first (only before current year)
                    if ($hubYear < $currentYear) {
                        $overdue = max(0, $hubAmt - $paidForYear);
                        $updates[] = [
                            'id' => $hub->id,
                            'paid_amount' => $paidForYear,
                            'overdue' => $overdue,
                            'due_amount' => 0,
                        ];
                    } elseif ($hubYear == $currentYear) {
                        // Use remaining paid balance
                        $appliedToCurrent = min($paidLeft, $hubAmt);
                        $due = max(0, $hubAmt - $appliedToCurrent);

                        $updates[] = [
                            'id' => $hub->id,
                            'paid_amount' => $appliedToCurrent,
                            'overdue' => 0,
                            'due_amount' => $due,
                        ];

                        // Deduct applied amount from paidLeft
                        $paidLeft -= $appliedToCurrent;
                    } else {
                        // future years – just leave them
                        continue;
                    }
                }

                // STEP 6: Apply the updates to DB
                foreach ($updates as $update) {
                    DB::table('t_hub')
                        ->where('id', $update['id'])
                        ->update([
                            'paid_amount' => $update['paid_amount'],
                            'overdue' => $update['overdue'],
                            'due_amount' => $update['due_amount'],
                            'updated_at' => now(),
                        ]);
                }
            }

            $this->info('Hub table recalculated successfully per latest logic.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}