<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\HubController;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;


use App\Models\UploadModel;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use App\Models\CounterModel;
use App\Models\AdvanceReceiptModel;
use App\Models\ExpenseModel;
use App\Models\PaymentsModel;
use App\Models\ReceiptsModel;
use App\Models\User;
use App\Models\WhatsAppQueue;
use App\Models\WhatsappQueueModel;
use Auth;
use App\Models\CurrencyModel;

use function PHPUnit\Framework\throwException;

class ReceiptsController extends Controller
{
     public function register_receipts(Request $request)
    {
        DB::beginTransaction();
    
        try {
            // Fetch jamiat_id from the logged-in user
            if($request->input('jamiat_id')){
                $jamiat_id=$request->input('jamiat_id');
                 $loguser= 'System Cron';
            }
            else{
            $user = auth()->user();
            $jamiat_id = $user->jamiat_id;
            $loguser=$user->name;
            }
    
            // Fetch the counter for receipt generation
            $counter = DB::table('t_counter')
                ->where('jamiat_id', $jamiat_id)
                ->where('type', 'receipt')
                ->first();
    
            if (!$counter) {
                return response()->json(['message' => 'Counter not found for receipts!'], 400);
            }
            
             do {
            // Generate a random 16-character string
            $uniqueKey = Str::random(16);  // Generates a 16-character random string

            // Check if the unique key already exists in the database
        } while (ReceiptsModel::where('hashed_id', $uniqueKey)->exists());

            // Generate receipt number using prefix, value, and postfix
            $receipt_no = $counter->prefix . $counter->value . $counter->postfix;
            $formatted_receipt_no = str_replace('/', '_', $receipt_no);
    
            // Validate the incoming request
            $validatedData = $request->validate([
                'family_id' => 'required|string|max:10',
                'name' => 'required|string|max:100',
                'amount' => 'required|numeric',
                'mode' => 'required|in:cheque,cash,neft',
                'bank_name' => 'nullable|string|max:100',
                'cheque_no' => 'nullable|string|size:6',
                'cheque_date' => 'nullable|date',
                'transaction_id' => 'nullable|string|max:100',
                'transaction_date' => 'nullable|date',
                'comments' => 'nullable|string',
            ]);
    
            // Initialize the remaining amount for distribution
            $totalAmount = $request->input('amount');
            $mode = $request->input('mode');
            $maximumReceivable = ($mode === 'cash' && $totalAmount > 10000) ? 10000 : $totalAmount; // Logic for cash > 10K
            $remainingAmount = $totalAmount;
            $status='paid';
            if($mode == 'cash')
            {
                $status= 'pending';
            }
            
            $receipts = [];
    
            // Loop through family members and distribute the amount
            $get_family_member = User::select('name', 'its')
                ->where('family_id', $request->input('family_id'))
                ->orderBy('mumeneen_type', 'ASC')
                ->get();
    
            if ($get_family_member->isEmpty()) {
                return response()->json(['message' => 'Failed to find family members!'], 400);
            }

            $hof_details = User::select('*')
                ->where('family_id', $request->input('family_id'))
                ->where('mumeneen_type', 'HOF')
                ->first();
    
            foreach ($get_family_member as $member) {
                $amountForMember = min($remainingAmount, $maximumReceivable);
    
                // Register receipt for each family member
                $register_receipt = ReceiptsModel::create([
                    'jamiat_id' => $jamiat_id,
                    'hashed_id'=>$uniqueKey,
                    'family_id' => $validatedData['family_id'],
                    'receipt_no' => $receipt_no,
                    'date' => now()->timezone('Asia/Kolkata')->toDateString(),
                    'its' => $member->its,
                    'folio_no' => $hof_details->folio_no,
                    'name' => $member->name,
                    'sector_id' => $hof_details->sector_id,
                    'sub_sector_id' => $hof_details->sub_sector_id,
                    'amount' => $amountForMember,
                    'mode' => $validatedData['mode'],
                    'bank_name' => $validatedData['bank_name'],
                    'cheque_no' => $validatedData['cheque_no'],
                    'cheque_date' => $validatedData['cheque_date'],
                    'transaction_id' => $validatedData['transaction_id'],
                    'transaction_date' => $validatedData['transaction_date'],
                    'year' => '1446-1447',
                    'comments' => $validatedData['comments'],
                    'status' => $status,
                    'cancellation_reason' => null,
                    'collected_by' => '',
                    'log_user' => $loguser,
                    'attachment' => null,
                    'payment_id' => null,
                ]);
    
                $receipts[] = $register_receipt;
                
                // If the mode is cheque, neft, or upi, create a payment entry
                if (in_array($register_receipt->mode, ['cheque', 'neft'])) {
                    $receiptIds = $request->input('receipt_ids') ?: [$register_receipt->id]; // For cheque, neft, upi
                    
    
                    try {
                        // Create payment entry
                        $paymentDataArray = $register_receipt->toArray();
                        $newRequest = new Request($paymentDataArray);
                       $paymentsController = new PaymentsController();
                        $paymentResponse = $paymentsController->register_payments($newRequest);          
    
                        // If payment creation is successful, update the payment_id in t_receipts
                        $paymentData = $paymentResponse->getData(true);
    
                        if ($paymentData['message'] === 'Payment created successfully!') {
                            // Access payment_id from the 'data' key
                            $paymentId = $paymentData['data']['id'];
    
                            // Update the payment_id in t_receipts
                            DB::table('t_receipts')
                                ->whereIn('id', $receiptIds)
                                ->update(['payment_id' => $paymentId]); // Update payment_id
                        } else {
                            throw new \Exception('Payment registration failed');
                        }
    
                    } catch (\Exception $e) {
                        // Rollback receipt creation if payment fails
                        $register_receipt->delete();
                        DB::rollBack(); // Rollback all the changes
    
                        // Return a detailed error response
                        return response()->json([
                            'message' => 'Payment creation failed, receipt has been rolled back.',
                            'error' => $e->getMessage(),
                            'stack' => $e->getTraceAsString(),
                        ], 500);
                    }
                }
    
                // Add WhatsApp queue entry
              
    
                // Call the receipt_print API to generate the PDF
                $pdfController = new PDFController();
               

                $pdfContent = $pdfController->generateReceiptPdfContent($register_receipt->hashed_id);

                if ($pdfContent && strlen($pdfContent) > 100) {
                    $directory = public_path("storage/{$jamiat_id}/receipts");
                    if (!file_exists($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    $pdfPath = "{$directory}/{$formatted_receipt_no}.pdf";
                    file_put_contents($pdfPath, $pdfContent);
                    $accountscontroller= new AccountsController();
                    $accountscontroller->addToWhatsAppQueue($register_receipt, $formatted_receipt_no);

                    // Log success
                    DB::table('mylogs')->insert([
                        'message' => "PDF generated and saved successfully for receipt {$formatted_receipt_no} at {$pdfPath}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    // Log failure
                    DB::table('mylogs')->insert([
                        'message' => "PDF generation failed or empty for receipt {$formatted_receipt_no}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $remainingAmount -= $amountForMember;
    
                if ($remainingAmount <= 0) {
                    break;
                }
            }

            $hubController = new HubController();
            $hubresponse = $hubController->updateFamilyHub($register_receipt->family_id);

    
            // Increment counter value after successful receipt creation
            DB::table('t_counter')
                ->where('jamiat_id', $jamiat_id)
                ->where('type', 'receipt')
                ->increment('value');
    
            // Handle advance receipt if remaining amount exists
            if ($remainingAmount > 0) {
                $get_hof_member = User::where('mumeneen_type', 'HOF')
                    ->where('family_id', $request->input('family_id'))
                    ->first();
    
                $dataForAdvanceReceipt = [
                    'jamiat_id' => $jamiat_id,
                    'family_id' => $validatedData['family_id'],
                    'name' => $get_hof_member->name,
                    'amount' => $remainingAmount,
                    'sector_id' => $hof_details->sector_id,
                    'sub_sector_id' => $hof_details->sub_sector_id,
                ];
    
                $newRequestAdvanceReceipt = new Request($dataForAdvanceReceipt);
                $advanceReceiptResponse = $this->register_advance_receipt($newRequestAdvanceReceipt);
    
                if ($advanceReceiptResponse->getStatusCode() !== 201) {
                    return response()->json(['message' => 'Failed to create Advance Receipt!'], 400);
                }
            }
    
            // Commit transaction after all receipts are created
            DB::commit();
    
            return response()->json([
                'message' => 'Receipt created successfully!',
                'receipts' => $receipts,
                'hub _ update'=>$hubresponse
            ], 201);
        } catch (\Exception $e) {
            // Rollback all database changes if something goes wrong
            DB::rollBack();
    
            // Log the error and return a detailed error response
            return response()->json([
                'message' => 'Failed to create receipt!',
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ], 500);
        }
    }

    // view
    public function all_receipts(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $userSectorAccess = json_decode($user->sector_access_id, true);
        $userSubSectorAccess = json_decode($user->sub_sector_access_id, true);

        if (empty($userSectorAccess) || empty($userSubSectorAccess)) {
            return response()->json(['message' => 'No access to any sectors or sub-sectors.'], 403);
        }

        // Optional filters
        $year = $request->input('year');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $status=$request->input('status');
        $mode=$request->input('mode');


        $query = ReceiptsModel::select(
                't_receipts.id','t_receipts.hashed_id', 't_receipts.jamiat_id', 't_receipts.family_id', 't_receipts.receipt_no',
                't_receipts.date', 't_receipts.its', 't_receipts.folio_no', 't_receipts.name',
                't_receipts.sector_id', 't_receipts.sub_sector_id', 't_receipts.amount', 't_receipts.mode',
                't_receipts.bank_name', 't_receipts.cheque_no', 't_receipts.cheque_date', 't_receipts.transaction_id', 't_receipts.transaction_date',
                't_receipts.year', 't_receipts.comments', 't_receipts.status', 't_receipts.cancellation_reason',
                't_receipts.collected_by', 't_receipts.log_user', 't_receipts.attachment', 't_receipts.payment_id',
                'users.name as user_name', 'users.photo_id',
                't_uploads.file_url as photo_url'
            )
            ->leftJoin('users', 't_receipts.its', '=', 'users.username') // if `users.username` is used as ITS
            ->leftJoin('t_uploads', 'users.photo_id', '=', 't_uploads.id')
            ->whereIn('t_receipts.sector_id', $userSectorAccess)
            ->whereIn('t_receipts.sub_sector_id', $userSubSectorAccess);

        if ($year) {
            $query->where('t_receipts.year', $year);
        }

        if ($dateFrom && $dateTo) {
            $query->whereBetween('t_receipts.date', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $query->where('t_receipts.date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->where('t_receipts.date', '<=', $dateTo);
        }
        if($mode)
        $query->where('t_receipts.mode','like',$mode);
        
         if($status)
        $query->where('t_receipts.status','like',$status);

        $get_all_receipts = $query->orderBy('t_receipts.date', 'desc')->get();

        return $get_all_receipts->isNotEmpty()
            ? response()->json(['message' => 'Receipts fetched successfully!', 'data' => $get_all_receipts], 200)
            : response()->json(['message' => 'No receipts found!'], 404);
    }

    public function getReceiptsByFamilyIds(Request $request)
    {
        // Validate and retrieve the family_id array from the request
        $family_ids = $request->input('family_ids');

        if (empty($family_ids) || !is_array($family_ids)) {
            return response()->json(['message' => 'Invalid or missing family_ids.'], 400);
        }

        // Fetch receipts matching the provided family_id values
        $get_receipts = ReceiptsModel::select(
            'id','hashed_id','jamiat_id', 'family_id', 'receipt_no', 'date', 'its', 'folio_no', 'name',
            'sector_id', 'sub_sector_id', 'amount', 'mode', 'bank_name', 'cheque_no', 'cheque_date', 'transaction_id', 'transaction_date', 'year', 'comments', 'status', 
            'cancellation_reason', 'collected_by', 'log_user', 'attachment', 'payment_id'
        )
        ->whereIn('family_id', $family_ids)
        ->orderBy('id', 'desc')
        ->get();

        return $get_receipts->isNotEmpty()
            ? response()->json(['message' => 'Receipts fetched successfully!', 'data' => $get_receipts], 200)
            : response()->json(['message' => 'No receipts found for the provided family IDs.'], 404);
    }
    // update
    public function update_receipts(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // Find existing receipt
            $existingReceipt = ReceiptsModel::find($id);
            if (!$existingReceipt) {
                return response()->json(['message' => 'Receipt not found!'], 404);
            }

            // Validate incoming request (you can customize validations as needed)
            $validatedData = $request->validate([
                'jamiat_id' => 'nullable|integer',
                'family_id' => 'required|string|max:10',
                'its' => 'nullable|string|max:8',
                'folio_no' => 'nullable|string|max:20',
                'name' => 'required|string|max:100',
            
                'amount' => 'required|numeric',
                'mode' => 'required|in:cheque,cash,neft,upi',
                'bank_name' => 'nullable|string|max:100',
                'cheque_no' => 'nullable|string|size:6',
                'cheque_date' => 'nullable|date',
                'transaction_id' => 'nullable|string|max:100',
                'transaction_date' => 'nullable|date',
                'year' => 'required|string|max:10',
                'comments' => 'nullable|string',
                'status' => 'required|in:pending,cancelled,paid',
                'cancellation_reason' => 'nullable|string',
                'collected_by' => 'nullable|string|max:100',
                'attachment' => 'nullable|integer',
                'payment_id' => 'nullable|integer', // payment_id can be nullable initially
            ]);

            // Update the receipt record
            $existingReceipt->update($validatedData);

            // Handle payment entry creation or update if mode is cheque or neft or upi
            if (in_array($validatedData['mode'], ['cheque', 'neft', 'upi'])) {
                // Prepare payment data array from validated data (map what is required)
                $paymentDataArray = [
                    
                    'family_id' => $validatedData['family_id'],
                
                    'name' => $validatedData['name'],
                    'its' => $validatedData['its'],
                    
                    'year' => $validatedData['year'],
                    'mode' => $validatedData['mode'],
                    'date' => $validatedData['date'],
                    'bank_name' => $validatedData['bank_name'] ?? null,
                    'cheque_no' => $validatedData['cheque_no'] ?? null,
                    'cheque_date' => $validatedData['cheque_date'] ?? null,
                    'transaction_id' => $validatedData['transaction_id'] ?? null,
                    'transaction_date' => $validatedData['transaction_date'] ?? null,
                    'amount' => $validatedData['amount'],
                    'comments' => $validatedData['comments'] ?? null,
                    'status' => $validatedData['status'],
                    'cancellation_reason' => $validatedData['cancellation_reason'] ?? null,
                    'collected_by' => $validatedData['collected_by'] ?? null,
                    'log_user' => Auth()->user()->name,
                ];

                // Instantiate PaymentsController
                $paymentsController = new PaymentsController();

                if ($existingReceipt->payment_id) {
                    // Update existing payment
                    $paymentDataArray['id'] = $existingReceipt->payment_id;
                    $paymentRequest = new Request($paymentDataArray);
                    $paymentResponse = $paymentsController->update_payments($paymentRequest, $existingReceipt->payment_id);
                } else {
                    // Create new payment
                    $paymentRequest = new Request($paymentDataArray);
                    $paymentResponse = $paymentsController->register_payments($paymentRequest);
                }

                $paymentData = $paymentResponse->getData(true);

                if (isset($paymentData['data']['id'])) {
                    $paymentId = $paymentData['data']['id'];

                    // Update payment_id in receipt if not set or changed
                    if ($existingReceipt->payment_id != $paymentId) {
                        $existingReceipt->payment_id = $paymentId;
                        $existingReceipt->save();
                    }
                } else {
                    throw new \Exception('Payment creation or update failed');
                }
            }

            // Add WhatsApp queue entry
            $accountsController = new AccountsController();
            $accountsController->addToWhatsAppQueue($existingReceipt, str_replace('/', '_', $existingReceipt->receipt_no));

            // Update hub for this family immediately after receipt update
            $hubController = new HubController();
            $hubResponse = $hubController->updateFamilyHub($existingReceipt->family_id);

            DB::commit();

            return response()->json([
                'message' => 'Receipt updated successfully!',
                'receipt' => $existingReceipt,
                'hub_update' => $hubResponse,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update receipt!',
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function show($id) 
    {
        $receipt = ReceiptsModel::findOrFail($id);
        return response()->json($receipt);
    }

    // delete
    public function delete_receipts($id)
    {
        $delete_receipt = ReceiptsModel::where('id', $id)->delete();

        return $delete_receipt
            ? response()->json(['message' => 'Receipt deleted successfully!'], 200)
            : response()->json(['message' => 'Receipt not found'], 404);
    }

    public function cancelReceipt(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find the receipt by ID
        $receipt = ReceiptsModel::find($id);
        if (!$receipt) {
            return response()->json(['message' => 'Receipt not found'], 404);
        }

        // Update status to cancelled
        $receipt->status = 'cancelled';
        $receipt->cancellation_reason = $request->input('reason', null); // Optional cancellation reason
        $receipt->updated_at = now();
        $receipt->save();

        // Optionally, call update hub for this family to recalculate dues
        $hubController = new HubController();
        $hubController->updateFamilyHub($receipt->family_id);

        return response()->json([
            'message' => 'Receipt cancelled successfully',
            'receipt' => $receipt
        ], 200);
    }

    public function register_advance_receipt(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'family_id' => 'required|string|max:10',
            'name' => 'required|string|max:100',
            'amount' => 'required|numeric',
            'sector_id' => 'required|integer',
            'sub_sector_id' => 'required|integer',
        ]);

        $register_advance_receipt = AdvanceReceiptModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'name' => $request->input('name'),
            'amount' => $request->input('amount'),
            'sector_id' => $request->input('sector_id'),
            'sub_sector_id' => $request->input('sub_sector_id'),
        ]);

        unset($register_advance_receipt['id'], $register_advance_receipt['created_at'], $register_advance_receipt['updated_at']);

        return $register_advance_receipt
            ? response()->json(['message' => 'Advance Receipt created successfully!', 'data' => $register_advance_receipt], 201)
            : response()->json(['message' => 'Failed to create Advance Receipt!'], 400);
    }

    // view
    public function all_advance_receipt()
    {
        $get_all_advance_receipts = AdvanceReceiptModel::select('jamiat_id', 'family_id', 'name', 'amount', 'sector', 'sub_sector')->get();

        return $get_all_advance_receipts->isNotEmpty()
            ? response()->json(['message' => 'Advance Receipts fetched successfully!', 'data' => $get_all_advance_receipts], 200)
            : response()->json(['message' => 'No Advance Receipts found!'], 404);
    }

    // update
    public function update_advance_receipt(Request $request, $id)
    {
        $get_advance_receipt = AdvanceReceiptModel::find($id);

        if (!$get_advance_receipt) {
            return response()->json(['message' => 'Advance Receipt not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'family_id' => 'required|string|max:10',
            'name' => 'required|string|max:100',
            'amount' => 'required|numeric',
            'sector' => 'required|integer',
            'sub_sector' => 'required|integer',
        ]);

        $update_advance_receipt = $get_advance_receipt->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'name' => $request->input('name'),
            'amount' => $request->input('amount'),
            'sector' => $request->input('sector'),
            'sub_sector' => $request->input('sub_sector'),
        ]);

        return ($update_advance_receipt == 1)
            ? response()->json(['message' => 'Advance Receipt updated successfully!', 'data' => $update_advance_receipt], 200)
            : response()->json(['No changes detected!'], 304);
    }

    // delete
    public function delete_advance_receipt($id)
    {
        $delete_advance_receipt = AdvanceReceiptModel::where('id', $id)->delete();

        return $delete_advance_receipt
            ? response()->json(['message' => 'Advance Receipt deleted successfully!'], 200)
            : response()->json(['message' => 'Advance Receipt not found!'], 404);
    }

    public function getPendingCashReceipts(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'mode' => 'required|in:cheque,cash,neft,upi', // Ensure mode is present and valid
            'sector' => 'nullable|integer',  // Sector filter as ID (optional)
            'sub_sector' => 'nullable|integer',  // Sub-sector filter as ID (optional)
        ]);

        // Build the base query
        if ($validatedData['mode'] == 'cash') {
            $query = DB::table('t_receipts')
                ->where('mode', 'cash')
                ->where('status', 'pending');
        } else {
            $query = DB::table('t_receipts')
                ->where('status', 'pending');
        }

        // Filter by sector if provided and valid
        if ($request->has('sector') && !empty($request->sector)) {
            $sectorId = (int) $request->sector;
            $sectorExists = DB::table('t_sector')->where('id', $sectorId)->exists();
            if ($sectorExists) {
                $query->where('sector_id', $sectorId);
            } else {
                return response()->json(['message' => 'Invalid sector ID'], 400);
            }
        }

        // Filter by sub-sector if provided and valid
        if ($request->has('sub_sector') && !empty($request->sub_sector)) {
            $subSectorId = (int) $request->sub_sector;
            $subSectorExists = DB::table('t_sub_sector')->where('id', $subSectorId)->exists();
            if ($subSectorExists) {
                $query->where('sub_sector_id', $subSectorId);
            } else {
                return response()->json(['message' => 'Invalid sub-sector ID'], 400);
            }
        }

        // Fetch receipts limited to 250 ordered by date descending
        $cashReceipts = $query->orderBy('date', 'desc')
            ->limit(250)
            ->get(['id', 'receipt_no', 'name', 'amount']);

        if ($cashReceipts->isEmpty()) {
            return response()->json(['message' => 'No pending cash receipts found.'], 404);
        }

        // Add custom key for each receipt as "receipt_no - name - amount"
        $cashReceipts->transform(function ($receipt) {
            $receipt->key = "{$receipt->receipt_no} - {$receipt->name} - {$receipt->amount}";
            return $receipt;
        });

        return response()->json([
            'message' => 'Pending cash receipts fetched successfully!',
            'data' => $cashReceipts,
        ], 200);
    }
    
    public function process_advance_receipts()
{
    DB::beginTransaction();

    try {
        // Fetch all advance receipts
        $advanceReceipts = AdvanceReceiptModel::all();

        if ($advanceReceipts->isEmpty()) {
            return response()->json(['message' => 'No advance receipts to process.'], 200);
        }

        $processedReceipts = [];

        foreach ($advanceReceipts as $advance) {
            // Build request data for register_receipts
            $receiptRequestData = [
                'jamiat_id'=>$advance->jamiat_id,
                'family_id' => $advance->family_id,
                'name' => $advance->name,
                'amount' => $advance->amount,
                'mode' => 'cash', // Always cash
                'bank_name' => null,
                'cheque_no' => null,
                'cheque_date' => null,
                'transaction_id' => null,
                'transaction_date' => null,
                'comments' => 'Created from Advance Receipt #' . $advance->id,
            ];

            // Create a fake Request object and call register_receipts
            $request = new Request($receiptRequestData);
            $response = $this->register_receipts($request);

            if ($response->getStatusCode() === 201) {
                // On success, delete advance receipt entry
                $advance->delete();
                $processedReceipts[] = $advance->id;
            } else {
                // Log failure (optional)
                DB::table('mylogs')->insert([
                    'message' => "Failed to convert Advance Receipt ID {$advance->id}". $response,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                throw new \Exception("Failed to register receipt for Advance Receipt ID {$response}");
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Advance receipts processed successfully!',
            'processed_ids' => $processedReceipts,
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Error processing advance receipts!',
            'error' => $e->getMessage(),
            'stack' => $e->getTraceAsString(),
        ], 500);
    }
}
}