<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\ReceiptsModel;
use App\Models\PaymentsModel;
use App\Imports\ItsDataImport;
use App\Imports\SectorSubsectorImport;
use App\Imports\UserImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessItsImport;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class CSVImportController extends Controller
{
    public function importDataFromUrl()
    {
        // URLs of the CSV files
        $receiptCsvUrl = public_path('storage/Receipts.csv'); // Replace with actual URL
        $paymentCsvUrl = public_path('storage/Payments.csv'); // Replace with actual URL

        try {
            // Fetch sector and sub-sector mappings
            $sectorMapping = DB::table('t_sector')->pluck('id', 'name')->toArray();
            $subSectorMapping = DB::table('t_sub_sector')
                ->select('id', 'name', 'sector_id')
                ->get()
                ->mapWithKeys(function ($item) {
                    return ["{$item->sector_id}:{$item->name}" => $item->id]; // Use sector_id instead of sector name
                })
                ->toArray();


            // Process Receipt CSV
            $this->processReceiptCSV($receiptCsvUrl, $sectorMapping, $subSectorMapping);

            // Process Payment CSV
            $this->processPaymentCSV($paymentCsvUrl, $sectorMapping, $subSectorMapping);

            return response()->json(['message' => 'Data import from URLs completed successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function processReceiptCSV($url, $sectorMapping, $subSectorMapping)
    {
        // Clear existing data in the receipt table
        //ReceiptsModel::truncate();

        // Fetch the CSV content from the URL
        $csvContent = file_get_contents($url);
        if ($csvContent === false) {
            throw new \Exception("Failed to fetch the CSV content from the URL: $url");
        }

        // Read and parse the CSV
        $csv = Reader::createFromString($csvContent);
        $csv->setHeaderOffset(0);

        // Get records and initialize batch variables
        $receiptRecords = $csv->getRecords();
        $batchSize = 100;
        $batchData = [];

        foreach ($receiptRecords as $record) {
            // Map sector and sub-sector IDs
            $sectorId = $sectorMapping[$record['sector']] ?? null;
            $subSectorId = $subSectorMapping["{$sectorId}:{$record['sub_sector']}"] ?? null;


            // Determine status using the old fields
            $statusFlag = (int) $record['status']; // 0 = active, 1 = cancelled
            $paymentStatus = (int) $record['payment_status']; // 0 = pending, 1 = paid

            // Merge status and payment_status into the new enum status
            if ($statusFlag === 1) {
                $status = 'cancelled'; // If status is 1, set to 'cancelled'
            } else {
                $status = $paymentStatus === 1 ? 'paid' : 'pending'; // Otherwise, use payment_status
            }

            // Convert type to mode in lowercase
            $mode = strtolower($record['type']);

            // Ensure mode is one of the enum values
            $allowedModes = ['cheque', 'cash', 'neft', 'upi'];
            if (!in_array($mode, $allowedModes)) {
                $mode = 'cash'; // Default to 'cash' if not in the allowed modes
            }

            // Prepare receipt data
            $batchData[] = [
                'jamiat_id' => 1,
                'family_id' => $record['family_id'],
                'receipt_no' => $record['rno'],
                'date' => $this->validateAndFormatDate($record['date']),
                'folio_no' => $record['folio'],
                'name' => $record['name'],
                'its' => $record['its'],
                'sector_id' => $sectorId, // Mapped sector ID
                'sub_sector_id' => $subSectorId, // Mapped sub-sector ID
                'mode' => $mode, // Updated field
                'amount' => preg_replace('/[^\d.]/', '', $record['amount']),
                'year' => $record['year'],
                'comments' => $record['comments'],
                'status' => $status,
                'cancellation_reason' => $status === 'cancelled' ? $record['reason'] : null,
                'log_user' => $record['log_user'],
                'created_at' => $record['log_date'],
            ];

            if (count($batchData) >= $batchSize) {
                $this->insertBatch(ReceiptsModel::class, $batchData);
                $batchData = [];
            }
        }

        if (count($batchData) > 0) {
            $this->insertBatch(ReceiptsModel::class, $batchData);
        }
    }

    private function processPaymentCSV($url, $sectorMapping, $subSectorMapping)
    {
        // Clear existing data in the payment table
        //PaymentsModel::truncate();

        // Fetch the CSV content from the URL
        $csvContent = file_get_contents($url);
        if ($csvContent === false) {
            throw new \Exception("Failed to fetch the CSV content from the URL: $url");
        }

        // Read and parse the CSV
        $csv = Reader::createFromString($csvContent);
        $csv->setHeaderOffset(0);
        $counter = 0;

        // Get records and initialize batch variables
        $paymentRecords = $csv->getRecords();
        $batchSize = 100;
        $batchData = [];

        foreach ($paymentRecords as $record) {
            // Map sector and sub-sector IDs
            $sectorId = $sectorMapping[$record['sector']] ?? null;
            $subSectorId = $subSectorMapping["{$sectorId}:{$record['sub_sector']}"] ?? null;


            // Format payment_no as "P_counter_(date)"
            $formattedDate = $this->validateAndFormatDate($record['date']);
            $paymentNo = "P_{$counter}_{$formattedDate}";
            $counter++;

            // Prepare payment data
            $batchData[] = [
                'jamiat_id' => 1,
                'family_id' => $record['family_id'],
                'folio_no' => $record['folio'],
                'name' => $record['name'],
                'its' => $record['its'],
                'sector_id' => $sectorId, // Mapped sector ID
                'sub_sector_id' => $subSectorId, // Mapped sub-sector ID
                'year' => $record['year'],
                'mode' => strtolower($record['mode']),
                'date' => $formattedDate,
                'bank_name' => $record['bank_name'] ?? null,
                'cheque_no' => $record['cheque_num'] ?? null,
                'cheque_date' => null,
                'ifsc_code' => $record['ifsc'] ?? null,
                'amount' => preg_replace('/[^\d.]/', '', $record['amount']),
                'comments' => $record['comments'] ?? null,
                'status' => 'pending',
                'cancellation_reason' => null,
                'log_user' => $record['log_user'],
                'attachment' => null,
                'payment_no' => $paymentNo,
            ];

            if (count($batchData) >= $batchSize) {
                $this->insertBatch(PaymentsModel::class, $batchData);
                $batchData = [];
            }
        }

        if (count($batchData) > 0) {
            $this->insertBatch(PaymentsModel::class, $batchData);
        }
    }

    private function insertBatch($model, $data)
    {
        try {
            if (!is_array($data) || empty($data) || !is_array(reset($data))) {
                throw new \Exception("Invalid data format for batch insert. Expected an array of arrays.");
            }

            DB::connection()->disableQueryLog();
            $model::insert($data);
        } catch (\Exception $e) {
            throw new \Exception("Error inserting batch: " . $e->getMessage());
        }
    }

    private function validateAndFormatDate($date)
    {
        if (empty($date) || $date === '-0001-11-30' || $date === '0000-00-00') {
            return '2021-12-12'; // Default to a valid date
        }

        try {
            $formattedDate = (new \DateTime($date))->format('Y-m-d');
            return $formattedDate;
        } catch (\Exception $e) {
            return '2021-12-12'; // Default valid date if parsing fails
        }
    }

   

    
    public function uploadExcel(Request $request)
    {
        ini_set('max_execution_time', 600);  // 5 minutes or more
ini_set('memory_limit', '2048M');    // you already set this, can increase if needed
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);
    
        try {
            ini_set('memory_limit', '1024M');

            // Get jamiat_id from the authenticated user
            $jamiat_id = auth()->user()->jamiat_id;
    
            if (!$jamiat_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jamiat ID is required and missing for the authenticated user.',
                ], 400);
            }
    
            // Check if `t_sector`, `t_sub_sector`, and `t_its_data` tables have data for the given Jamiat ID
            $sectorExists = DB::table('t_sector')->where('jamiat_id', $jamiat_id)->exists();
            $userExists = DB::table('users')->where('jamiat_id', $jamiat_id)->where('role', 'mumeneen')->exists();
            $itsExists = DB::table('t_its_data')->where('jamiat_id', $jamiat_id)->exists();

            if (!$itsExists) {
                Excel::import(new ItsDataImport(), $request->file('file'));
                Log::info("ITS data imported for Jamiat ID: {$jamiat_id}");
            } else {
                Log::info("ITS data already exists for Jamiat ID: {$jamiat_id}. Import skipped.");
            }
    
            // Import Sectors and Subsectors only if they don't already exist
            if (!$sectorExists) {
                Excel::import(new SectorSubsectorImport(), $request->file('file'));
                Log::info("Sectors and Subsectors imported for Jamiat ID: {$jamiat_id}");
            } else {
                Log::info("Sectors and Subsectors already exist for Jamiat ID: {$jamiat_id}. Import skipped.");
            }
    
            // Import Users only if no mumeneen users exist
            if (!$userExists) {
                Excel::import(new UserImport($jamiat_id), $request->file('file'));
                Log::info("Users imported for Jamiat ID: {$jamiat_id}");
            } else {
                Log::info("Users with role 'mumeneen' already exist for Jamiat ID: {$jamiat_id}. Import skipped.");
            }
    
            
            return response()->json([
                'success' => true,
                'message' => 'Import process completed.',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error during import process: " . $e->getMessage());
    
            return response()->json([
                'success' => false,
                'message' => 'Error during import process: ' . $e->getMessage(),
            ], 500);
        }
    }



// public function uploadExcel(Request $request)
// {
//     $request->validate([
//         'file' => 'required|mimes:xlsx,xls',
//     ]);

//     // Get Jamiat ID from authenticated user
//     $jamiat_id = auth()->user()->jamiat_id;
//     if (!$jamiat_id) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Jamiat ID is required and missing for the authenticated user.',
//         ], 400);
//     }

//     try {
//         // Store the uploaded file (local storage or s3, etc)
//         $file = $request->file('file');
//         $filePath = $file->store('imports');

//         // Dispatch job to process import asynchronously
//         ProcessItsImport::dispatch($filePath, 1);

//         return response()->json([
//             'success' => true,
//             'message' => 'Import started. You will be notified when the process completes.',
//         ], 200);

//     } catch (\Exception $e) {
//         \Log::error('Failed to dispatch import job: ' . $e->getMessage());
//         return response()->json([
//             'success' => false,
//             'message' => 'Failed to start import process: ' . $e->getMessage(),
//         ], 500);
//     }
// }
    public function deleteByJamiatId($jamiatId)
    {
        try {
            // Check if any record exists for the given Jamiat ID
            $existsInItsData = DB::table('t_its_data')->where('jamiat_id', $jamiatId)->exists();
            $existsInSectors = DB::table('t_sector')->where('jamiat_id', $jamiatId)->exists();
            $existsInUsers = DB::table('users')->where('jamiat_id', $jamiatId)->exists();
    
            if (!$existsInItsData && !$existsInSectors && !$existsInUsers) {
                return response()->json([
                    'success' => false,
                    'message' => "No records found for Jamiat ID: {$jamiatId}.",
                ], 404);
            }
    
            // Start a transaction for safe deletion
            DB::beginTransaction();
    
            // Delete from users table
            DB::table('users')->where('jamiat_id', $jamiatId)->delete();
    
            // Delete from t_sub_sector table
            DB::table('t_sub_sector')->where('jamiat_id', $jamiatId)->delete();
    
            // Delete from t_sector table
            DB::table('t_sector')->where('jamiat_id', $jamiatId)->delete();
    
            // Delete from t_its_data table
            DB::table('t_its_data')->where('jamiat_id', $jamiatId)->delete();
    
            DB::table('t_hub')->where('jamiat_id', $jamiatId)->delete();
            
    
            // Commit the transaction
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => "All records for Jamiat ID: {$jamiatId} have been deleted successfully.",
            ], 200);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();
    
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting records: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function importExpensesFromCSV(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:csv,txt',
    ]);

    try {
        $jamiat_id = auth()->user()->jamiat_id ?? 1;

        $file = $request->file('file');
        $data = array_map('str_getcsv', file($file->getRealPath()));

        // Assume first row is the header
        $header = array_map('trim', $data[0]);
        unset($data[0]);

        $insertData = [];

        foreach ($data as $row) {
            $row = array_combine($header, $row);

            // Convert date format to Y-m-d
            $convertedDate = Carbon::createFromFormat('n/j/y', $row['date'])->format('Y-m-d');
            $logDate = Carbon::createFromFormat('n/j/y', $row['log_date'])->format('Y-m-d');

            $insertData[] = [
                'jamiat_id'   => $jamiat_id,
                'voucher_no'  => $row['expense_no'],
                'year'        => $row['year'],
                'name'        => $row['paid_to'],
                'date'        => $convertedDate,
                'amount'      => $row['amount'],
                'cheque_no'   => $row['cheque_no'],
                'description' => $row['description'],
                'log_user'    => $row['log_user'],
                'created_at'  => $logDate,
                'updated_at'  => now(),
            ];
        }

        DB::table('t_expense')->insert($insertData);

        return response()->json([
            'message' => 'Expenses imported successfully.',
            'count' => count($insertData)
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to import expenses.',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
