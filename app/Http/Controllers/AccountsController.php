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

class AccountsController extends Controller
{
    public function user()
    {
        return $this->belongsTo(User::class, 'jamiat_id', 'jamiat_id');
    }
    //
    // create
    public function register_counter(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'sector' => 'nullable|integer',
            'type' => 'required|string|max:50',
            'year' => 'required|string|max:10',
            'value' => 'required|integer',
        ]);

        $register_counter = CounterModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'sector' => $request->input('sector'),
            'type' => $request->input('type'),
            'year' => $request->input('year'),
            'value' => $request->input('value'),
        ]);

        unset($register_counter['id'], $register_counter['created_at'], $register_counter['updated_at']);

        return $register_counter
            ? response()->json(['message' => 'Counter record created successfully!', 'data' => $register_counter], 201)
            : response()->json(['message' => 'Failed to create counter record!'], 400);
    }

    // view
    public function all_counter()
    {
        $get_all_counters = CounterModel::select('jamiat_id', 'sector', 'type', 'year', 'value')->get();

        return $get_all_counters->isNotEmpty()
            ? response()->json(['message' => 'Counter records fetched successfully!', 'data' => $get_all_counters], 200)
            : response()->json(['message' => 'No counter records found!'], 404);
    }

    // update
    public function update_counter(Request $request, $id)
    {
        $get_counter = CounterModel::find($id);

        if (!$get_counter) {
            return response()->json(['message' => 'Counter record not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'sector' => 'nullable|integer',
            'type' => 'required|string|max:50',
            'year' => 'required|string|max:10',
            'value' => 'required|integer',
        ]);

        $update_counter_record = $get_counter->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'sector' => $request->input('sector'),
            'type' => $request->input('type'),
            'year' => $request->input('year'),
            'value' => $request->input('value'),
        ]);

        return ($update_counter_record == 1)
            ? response()->json(['message' => 'Counter record updated successfully!', 'data' => $update_counter_record], 200)
            : response()->json(['No changes detected!'], 304);
    }

    // delete
    public function delete_counter($id)
    {
        $delete_counter = CounterModel::where('id', $id)->delete();

        return $delete_counter
            ? response()->json(['message' => 'Counter record deleted successfully!'], 200)
            : response()->json(['message' => 'Counter record not found!'], 404);
    }

    // create
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



    public function register_expense(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'nullable|integer',
            'year' => 'required|string|max:10',
            'name' => 'required|string',
            'date' => 'required|date',
            'amount' => 'required|integer',
            'cheque_no' => 'nullable|string|size:6',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        DB::beginTransaction();
        try {
            // 1. Fetch & increment voucher number from t_counter
            $counter = DB::table('t_counter')
                ->where('type', 'Expense')
                ->where('jamiat_id', Auth()->user()->jamiat_id)
                ->where('year', $request->input('year'))
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                return response()->json(['message' => 'Voucher counter not configured.'], 400);
            }

            $voucherNumber = $counter->prefix.($counter->value + 1).$counter->postfix;

            DB::table('t_counter')
                ->where('id', $counter->id)
                ->update(['value' => $counter->value + 1]);

            // 2. Store attachment

            $file = $request->file('attachment');
            if($file){
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('uploads/expenses', $fileName, 'public');

            $upload = UploadModel::create([
                'jamiat_id'  => Auth()->user()->jamiat_id,
                'file_name' => $fileName,
                'file_ext'  => $file->getClientOriginalExtension(),
                'file_url'  => Storage::url($filePath),
                'file_size' => $file->getSize(),
                'type'      => 'feedback', // or 'profile' based on your use case
            ]);
        }

            // 3. Register expense
            $register_expense = ExpenseModel::create([
                'jamiat_id' => Auth()->user()->jamiat_id,
                'voucher_no' => $voucherNumber,
                'year' => $request->input('year'),
                'name' => $request->input('name'),
                'date' => $request->input('date'),
                'amount'=>$request->input('amount'),
                'cheque_no' => $request->input('cheque_no'),
                'description' => $request->input('description'),
                'log_user' => auth()->user()->name,
                'attachment' => $upload->id??null,
            ]);

            DB::commit();

            unset($register_expense['id'], $register_expense['created_at'], $register_expense['updated_at']);

            return response()->json([
                'message' => 'Expense created successfully!',
                'data' => $register_expense
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create expense!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // view

    public function all_expense()
    {
        $get_all_expenses = DB::table('t_expense')
            ->leftJoin('t_uploads', 't_uploads.id', '=', 't_expense.attachment')
            ->select(
                't_expense.id',
                't_expense.jamiat_id',
                't_expense.voucher_no',
                't_expense.year',
                't_expense.name',
                't_expense.date',
                't_expense.amount',
                't_expense.cheque_no',
                't_expense.description',
                't_expense.log_user',
                't_uploads.file_url as attachment_url'
            )
            ->get();
    
        return $get_all_expenses->isNotEmpty()
            ? response()->json(['message' => 'Expenses fetched successfully!', 'data' => $get_all_expenses], 200)
            : response()->json(['message' => 'No expenses found!'], 404);
    }
    // update
    public function update_expense(Request $request, $id)
    {
        $get_expense = ExpenseModel::find($id);

        if (!$get_expense) {
            return response()->json(['message' => 'Expense not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'voucher_no' => 'required|integer',
            'year' => 'required|string|max:10',
            'name' => 'required|string',
            'date' => 'required|date',
            'amount' => 'required|integer',
            'cheque_no' => 'nullable|string|size:6',
            'description' => 'nullable|string',
            'log_user' => 'required|string',
           
        ]);

        $update_expense = $get_expense->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'voucher_no' => $request->input('voucher_no'),
            'year' => $request->input('year'),
            'name' => $request->input('name'),
            'date' => $request->input('date'),
            'amount' => $request->input('amount'),
            'cheque_no' => $request->input('cheque_no'),
            'description' => $request->input('description'),
            'log_user' => $request->input('log_user'),
            
        ]);

        return ($update_expense == 1)
            ? response()->json(['message' => 'Expense updated successfully!', 'data' => $update_expense], 200)
            : response()->json(['No changes detected!'], 304);
    }

    // delete
    public function delete_expense($id)
    {
        $delete_expense = ExpenseModel::where('id', $id)->delete();

        return $delete_expense
            ? response()->json(['message' => 'Expense deleted successfully!'], 200)
            : response()->json(['message' => 'Expense not found!'], 404);
    }

    // create
    public function register_payments(Request $request)
    {
        $validatedData = $request->validate([
            'date' => 'required|date', // Multiple receipt IDs for cash or single receipt for others
            'receipt_ids' => 'required|array', // Multiple receipt IDs for cash or single receipt for others
            'amount' => 'required|numeric',
            'year' => 'required|string',
            'remarks' => 'nullable|string|max:255', // For remarks on cash payments
        ]);
        $jamiat_id = Auth()->user()->jamiat_id;

        
        
        DB::beginTransaction();
        try {
            // Generate payment number: P_<counter>_<YYYYMMDD>
            $counter = DB::table('t_payments')->count() + 1;
            $today = \Carbon\Carbon::parse($request->date ?? now())->format('Y-m-d');
            $paymentNo = "P_{$counter}_{$today}";

            // Handle attachment upload (if exists)
            $uploadId = null;
            // if ($request->hasFile('attachment')) {
            //     $file = $request->file('attachment');
            //     $fileName = time() . '_' . $file->getClientOriginalName();
            //     $filePath = $file->storeAs('uploads/payments', $fileName, 'public');

            //     $upload = \App\Models\UploadModel::create([
            //         'jamiat_id'  => $jamiat_id,
            //         'file_name'  => $fileName,
            //         'file_ext'   => $file->getClientOriginalExtension(),
            //         'file_url'   => Storage::url($filePath),
            //         'file_size'  => $file->getSize(),
            //         'type'       => 'feedback',
            //     ]);
            //     $uploadId = $upload->id;
            // }

            // Map sector and sub-sector (optional fallback)
            $sectorId=$request->sector_id??null;
            $subSectorId=$request->sub_sector_id??null;
        
            // Prepare payment data based on the mode
            $paymentData = [
                'payment_no'         => $paymentNo,
                'jamiat_id'          => $jamiat_id,
                'family_id'          => $request->family_id ?? null,
                'name'               => $request->name ?? "Cash Deposited",
                'its'                => $request->its ?? null,
                'sector_id'          => $sectorId,
                'sub_sector_id'      => $subSectorId,
                'year'               => $request->year,
                'mode'               => $request->mode??"cash",
                'date'               => $request->date,
                'amount'             => $request->amount,
                'comments'           => $request->remarks ?? null,
                'status'             => 'pending',
                'log_user'           => Auth()->user()->name,
                'attachment'         => $uploadId,
            ];

            // For cash payments, we will set a few different fields
            if ($request->mode == 'cash') {
                $receiptIds = $request->input('receipt_ids');

                // Sum up the total amount from the receipts
                $totalAmount = 0;
                foreach ($receiptIds as $receiptId) {
                    $receipt = ReceiptsModel::find($receiptId);
                    if ($receipt) {
                        $totalAmount += $receipt->amount;
                        $sectorId = $receipt->sector_id;
                        $subSectorId = $receipt->sub_sector_id;
                    }
                }

                // Ensure that the total amount of receipts matches the provided payment amount
                if ($totalAmount !== $request->amount) {
                    throw new \Exception("Total amount of receipts does not match the cash payment amount.");
                }

                // Set name as "cash" and use the sector of the receipts
                $paymentData['name'] = 'Cash Deposited';  // Name for cash payment
                $paymentData['sector_id'] = $sectorId; 
                $paymentData['sub_sector_id'] = $subSectorId; // Use the sector from receipts
                $paymentData['amount'] = $totalAmount;  // Use the total of all receipts
            }

            // Register the payment
            $register_payment = PaymentsModel::create($paymentData);

            // Handle cash receipts, if applicable
            if ($request->mode == 'cash') {
                // Update all receipt records with the payment_id for cash payments
                DB::table('t_receipts')
                    ->whereIn('id', $receiptIds)
                    ->update(['payment_id' => $register_payment->id]);
            } 

            DB::commit();

            return response()->json([
                
                'message' => 'Payment created successfully!',
                'data'    => $register_payment
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create payment!',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    // view
    public function all_payments(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Decode access control
        $userSectorAccess = json_decode($user->sector_access_id, true);
        $userSubSectorAccess = json_decode($user->sub_sector_access_id, true);

        if (empty($userSectorAccess) || empty($userSubSectorAccess)) {
            return response()->json([
                'message' => 'No access to any sectors or sub-sectors.',
            ], 403);
        }

        // Optional filters
        $year = $request->input('year');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = PaymentsModel::select(
                't_payments.id', 't_payments.payment_no', 't_payments.jamiat_id', 't_payments.family_id',
                 't_payments.name', 't_payments.its', 't_payments.sector_id', 't_payments.sub_sector_id',
                't_payments.year', 't_payments.mode', 't_payments.date',  't_payments.amount',
                't_payments.comments', 't_payments.status', 't_payments.cancellation_reason',
                't_payments.log_user', 't_payments.attachment',
                'users.name as user_name', 'users.photo_id',
                't_uploads.file_url as photo_url'
            )
            ->leftJoin('users', 't_payments.its', '=', 'users.username')
            ->leftJoin('t_uploads', 'users.photo_id', '=', 't_uploads.id')
            ->where(function ($query) use ($userSectorAccess) {
    $query->whereIn('t_payments.sector_id', $userSectorAccess)
          ->orWhereNull('t_payments.sector_id');
})
->where(function ($query) use ($userSubSectorAccess) {
    $query->whereIn('t_payments.sub_sector_id', $userSubSectorAccess)
          ->orWhereNull('t_payments.sub_sector_id');
});

        // Apply year filter if provided
        if ($year) {
            $query->where('t_payments.year', $year);
        }

        // Apply date range filter if both provided
        if ($dateFrom && $dateTo) {
            $query->whereBetween('t_payments.date', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $query->where('t_payments.date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->where('t_payments.date', '<=', $dateTo);
        }

        $get_all_payments = $query->orderBy('t_payments.date', 'desc')->get();

        return $get_all_payments->isNotEmpty()
            ? response()->json(['message' => 'Payments fetched successfully!', 'data' => $get_all_payments], 200)
            : response()->json(['message' => 'No payments found!'], 404);
    }

    // update
    public function update_payments(Request $request, $id)
    {
        $get_payment = PaymentsModel::find($id);

        if (!$get_payment) {
            return response()->json(['message' => 'Payment not found!'], 404);
        }

        $request->validate([
            'payment_no' => 'required|string|max:100|unique:t_payments,payment_no,' . $id,
            'jamiat_id' => 'required|string|max:10',
            'family_id' => 'required|string|max:10',
            'folio_no' => 'nullable|string|max:20',
            'name' => 'required|string|max:100',
            'its' => 'required|string|max:8',
            'sector' => 'nullable|string|max:100',
            'sub_sector' => 'nullable|string|max:100',
            'year' => 'required|string|max:10',
            'mode' => 'required|in:cheque,cash,neft,upi',
            'date' => 'required|date',
            'bank_name' => 'nullable|string|max:100',
            'cheque_no' => 'nullable|string|size:6',
            'cheque_date' => 'nullable|date',
            'ifsc_code' => 'nullable|string|max:11',
            'transaction_id' => 'nullable|string|max:100',
            'transaction_date' => 'nullable|date',
            'amount' => 'required|numeric',
            'comments' => 'nullable|string',
            'status' => 'required|in:pending,cancelled,approved',
            'cancellation_reason' => 'nullable|string',
            'log_user' => 'required|string|max:100',
            'attachment' => 'nullable|integer',
        ]);

        // $update_payment = $get_payment->update($request->all());
        $update_payment = $get_payment->update([
            'payment_no' => $request->input('payment_no'),
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'folio_no' => $request->input('folio_no'),
            'name' => $request->input('name'),
            'its' => $request->input('its'),
            'sector' => $request->input('sector'),
            'sub_sector' => $request->input('sub_sector'),
            'year' => $request->input('year'),
            'mode' => $request->input('mode'),
            'date' => $request->input('date'),
            'bank_name' => $request->input('bank_name'),
            'cheque_no' => $request->input('cheque_no'),
            'cheque_date' => $request->input('cheque_date'),
            'ifsc_code' => $request->input('ifsc_code'),
            'transaction_id' => $request->input('transaction_id'),
            'transaction_date' => $request->input('transaction_date'),
            'amount' => $request->input('amount'),
            'comments' => $request->input('comments'),
            'status' => $request->input('status'),
            'cancellation_reason' => $request->input('cancellation_reason'),
            'log_user' => $request->input('log_user'),
            'attachment' => $request->input('attachment'),
        ]);

        return ($update_payment == 1)
            ? response()->json(['message' => 'Payment updated successfully!', 'data' => $update_payment], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_payments($id)
    {
        $delete_payment = PaymentsModel::where('id', $id)->delete();

        return $delete_payment
            ? response()->json(['message' => 'Payment deleted successfully!'], 200)
            : response()->json(['message' => 'Payment not found'], 404);
    }

    // create
    public function register_receipts(Request $request)
    {
        DB::beginTransaction();
    
        try {
            // Fetch jamiat_id from the logged-in user
            $user = auth()->user();
            $jamiat_id = $user->jamiat_id;
    
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
                    'log_user' => $user->name,
                    'attachment' => null,
                    'payment_id' => null,
                ]);
    
                $receipts[] = $register_receipt;
                
                // If the mode is cheque, neft, or upi, create a payment entry
                if (in_array($register_receipt->mode, ['cheque', 'neft'])) {
                    $receiptIds = $request->input('receipt_ids') ?: [$register_receipt->id]; // For cheque, neft, upi
                    
    
                    try {
                        // Create payment entry
                        $paymentResponse = $this->register_payments( $request);
    
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
                $pdfController = new \App\Http\Controllers\PDFController();
               

      $pdfContent = $pdfController->generateReceiptPdfContent($register_receipt->hashed_id);

if ($pdfContent && strlen($pdfContent) > 100) {
    $directory = public_path("storage/{$jamiat_id}/receipts");
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
    $pdfPath = "{$directory}/{$formatted_receipt_no}.pdf";
    file_put_contents($pdfPath, $pdfContent);
      $this->addToWhatsAppQueue($register_receipt, $formatted_receipt_no);

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
            'id','jamiat_id', 'family_id', 'receipt_no', 'date', 'its', 'folio_no', 'name',
            'sector_id', 'sub_sector_id', 'amount', 'mode', 'bank_name', 'cheque_no', 'cheque_date',
            'ifsc_code', 'transaction_id', 'transaction_date', 'year', 'comments', 'status', 
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
        $get_receipt = ReceiptsModel::find($id);

        if (!$get_receipt) {
            return response()->json(['message' => 'Receipt not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'family_id' => 'required|string|max:10',
            'receipt_no' => 'required|string|max:100',
            'date' => 'required|date',
            'its' => 'required|string|max:8',
            'folio_no' => 'nullable|string|max:20',
            'name' => 'required|string|max:100',
            'sector_id' => 'nullable|string|max:100',
            'sub_sector_id' => 'nullable|string|max:100',
            'amount' => 'required|numeric',
            'mode' => 'required|in:cheque,cash,neft,upi',
            'bank_name' => 'nullable|string|max:100',
            'cheque_no' => 'nullable|string|size:6',
            'cheque_date' => 'nullable|date',
            'ifsc_code' => 'nullable|string|max:11',
            'transaction_id' => 'nullable|string|max:100',
            'transaction_date' => 'nullable|date',
            'year' => 'required|string|max:10',
            'comments' => 'nullable|string',
            'status' => 'required|in:pending,cancelled,paid',
            'cancellation_reason' => 'nullable|string',
            'collected_by' => 'nullable|string|max:100',
            'log_user' => 'required|string|max:100',
            'attachment' => 'nullable|integer',
            'payment_id' => 'required|integer',
        ]);

        // $update_receipt_record = $get_receipt->update($request->all());
        $update_receipt_record = $get_receipt->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'receipt_no' => $request->input('receipt_no'),
            'date' => $request->input('date'),
            'its' => $request->input('its'),
            'folio_no' => $request->input('folio_no'),
            'name' => $request->input('name'),
            'sector_id' => $request->input('sector_id'),
            'sub_sector_id' => $request->input('sub_sector_id'),
            'amount' => $request->input('amount'),
            'mode' => $request->input('mode'),
            'bank_name' => $request->input('bank_name'),
            'cheque_no' => $request->input('cheque_no'),
            'cheque_date' => $request->input('cheque_date'),
            'ifsc_code' => $request->input('ifsc_code'),
            'transaction_id' => $request->input('transaction_id'),
            'transaction_date' => $request->input('transaction_date'),
            'year' => $request->input('year'),
            'comments' => $request->input('comments'),
            'status' => $request->input('status'),
            'cancellation_reason' => $request->input('cancellation_reason'),
            'collected_by' => $request->input('collected_by'),
            'log_user' => $request->input('log_user'),
            'attachment' => $request->input('attachment'),
            'payment_id' => $request->input('payment_id'),
        ]);
    

        return ($update_receipt_record == 1)
            ? response()->json(['message' => 'Receipt updated successfully!', 'data' => $update_receipt_record], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_receipts($id)
    {
        $delete_receipt = ReceiptsModel::where('id', $id)->delete();

        return $delete_receipt
            ? response()->json(['message' => 'Receipt deleted successfully!'], 200)
            : response()->json(['message' => 'Receipt not found'], 404);
    }

    protected function addToWhatsAppQueue($receipt, $pdfUrl)
    {
        // Define the prefix for the full file URL
        $filePrefix = 'https://api.fmb52.com/storage/1/receipts/';
        
        // Generate the full URL for the PDF
        $fullPdfUrl = $filePrefix . $pdfUrl.'pdf';
    
        // Fetch the name from the t_jamiat table based on jamiat_id
        $jamiatName = DB::table('t_jamiat')
            ->where('id', Auth::user()->jamiat_id)
            ->value('name'); // Fetch only the name
    
        // Prepare WhatsApp template content
        $templateContent = [
            'name' => 'fmb_receipt_create',
            'language' => ['code' => 'en'],
            'components' => [
                [
                    'type' => 'header',
                    'parameters' => [
                        [
                            'type' => 'document',
                            'document' => [
                                'link' => $fullPdfUrl, // Add the full PDF URL
                                'filename' => "{$receipt->receipt_no}.pdf", // Set the filename
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $receipt->name], // {{1}} - Contributor's Name
                        ['type' => 'text', 'text' => number_format($receipt->amount, 2)], // {{2}} - Contribution Amount
                        ['type' => 'text', 'text' => $jamiatName], // {{3}} - Receipt Number
                    ],
                ],
            ],
        ];
    
        // Get the authenticated user
        $user = Auth::user();
    
        // Insert into WhatsApp queue table
        WhatsappQueueModel::create([
            'jamiat_id' => $user->jamiat_id,
            'group_id' => 'receipt_' . uniqid(),
            'callback_data' => 'receipt_' . $receipt->receipt_no,
            'recipient_type' => 'individual',
            'to' => '917439515253', // Use the mobile number of the recipient
            'template_name' => 'fmb_receipt_created',
            'content' => json_encode($templateContent), // Encode the content as JSON
            'file_url' => $fullPdfUrl, // Attach the full PDF URL
            'status' => 0, // Pending
            'log_user' => $jamiatName, // Log the name fetched from t_jamiat
            'created_at' => now(), // Current timestamp for creation
            'updated_at' => now(), // Current timestamp for updates
        ]);

          WhatsappQueueModel::create([
            'jamiat_id' => $user->jamiat_id,
            'group_id' => 'receipt_' . uniqid(),
            'callback_data' => 'receipt_' . $receipt->receipt_no,
            'recipient_type' => 'individual',
            'to' => '918961043773', // Use the mobile number of the recipient
            'template_name' => 'fmb_receipt_created',
            'content' => json_encode($templateContent), // Encode the content as JSON
            'file_url' => $fullPdfUrl, // Attach the full PDF URL
            'status' => 0, // Pending
            'log_user' => $jamiatName, // Log the name fetched from t_jamiat
            'created_at' => now(), // Current timestamp for creation
            'updated_at' => now(), // Current timestamp for updates
        ]);
    }
    public function fetchCurrencies(Request $request)
    {
        // Fetch all currencies, optionally filter by country_name or currency_code
        $currencies = CurrencyModel::query();

        // Apply filters if provided
        if ($request->has('country_name')) {
            $currencies->where('country_name', 'LIKE', '%' . $request->country_name . '%');
        }

        if ($request->has('currency_code')) {
            $currencies->where('currency_code', $request->currency_code);
        }

        // Paginate or fetch all currencies
        $result = $currencies->get();

        return response()->json([
            'message' => 'Currencies fetched successfully',
            'data' => $result,
        ], 200);
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

    public function changePaymentStatus(Request $request)
    {
        // Validate incoming data
        $validatedData = $request->validate([
            'payment_id' => 'required|integer|exists:t_payments,id',  // Ensure payment ID exists
            'status' => 'required|in:pending,approved,cancelled',  // Valid statuses
        ]);

        // Fetch the payment record
        $payment = DB::table('t_payments')->where('id', $validatedData['payment_id'])->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found!'], 404);
        }

        // Update the payment status
        $updatedStatus = DB::table('t_payments')
            ->where('id', $validatedData['payment_id'])
            ->update([
                'status' => $validatedData['status'],
                'updated_at' => now(),  // Update timestamp
            ]);

        if ($updatedStatus) {
            return response()->json(['message' => 'Payment status updated successfully!'], 200);
        }

        return response()->json(['message' => 'Failed to update payment status.'], 500);
    }
  
}
