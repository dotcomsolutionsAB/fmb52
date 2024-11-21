<?php

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
            // Fetch all receipts where amount is not null and status is not 'cancelled'
            $receipts = DB::table('t_receipts')
                ->whereNotNull('amount')
                ->where('status', '<>', 'cancelled')
                ->get();

            foreach ($receipts as $receipt) {
                $familyId = $receipt->family_id;
                $year = $receipt->year;
                $amount = (float) preg_replace('/[^\d.]/', '', $receipt->amount);

                // Fetch or create the corresponding hub record
                $hub = DB::table('t_hub')
                    ->where('family_id', $familyId)
                    ->where('year', $year)
                    ->first();

                if ($hub) {
                    // Update the existing hub record
                    $paidAmount = $hub->paid_amount + $amount;
                    $dueAmount = max(0, $hub->hub_amount - $paidAmount);

                    DB::table('t_hub')
                        ->where('id', $hub->id)
                        ->update([
                            'paid_amount' => $paidAmount,
                            'due_amount' => $dueAmount,
                            'updated_at' => now(),
                        ]);
                } else {
                    // Create a new hub record if it does not exist
                    DB::table('t_hub')->insert([
                        'jamiat_id' => 1, // Assuming a default jamiat_id
                        'family_id' => $familyId,
                        'year' => $year,
                        'hub_amount' => 0, // Default hub_amount if not provided
                        'paid_amount' => $amount,
                        'due_amount' => 0 - $amount, // Calculate initial due_amount
                        'log_user' => 'system_cron', // User running the cron job
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
