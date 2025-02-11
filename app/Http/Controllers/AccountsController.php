<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

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
            'sector' => 'required|string',
            'sub_sector' => 'required|string',
        ]);

        $register_advance_receipt = AdvanceReceiptModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'name' => $request->input('name'),
            'amount' => $request->input('amount'),
            'sector' => $request->input('sector'),
            'sub_sector' => $request->input('sub_sector'),
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

    // create
    public function register_expense(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'voucher_no' => 'required|integer',
            'year' => 'required|string|max:10',
            'name' => 'required|string',
            'date' => 'required|date',
            'cheque_no' => 'nullable|string',
            'description' => 'nullable|string',
            'log_user' => 'required|string',
            'attachment' => 'required|string', // Assuming this is a file path or a URL.
        ]);

        $register_expense = ExpenseModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'voucher_no' => $request->input('voucher_no'),
            'year' => $request->input('year'),
            'name' => $request->input('name'),
            'date' => $request->input('date'),
            'cheque_no' => $request->input('cheque_no'),
            'description' => $request->input('description'),
            'log_user' => $request->input('log_user'),
            'attachment' => $request->input('attachment'),
        ]);

        unset($register_expense['id'], $register_expense['created_at'], $register_expense['updated_at']);

        return $register_expense
            ? response()->json(['message' => 'Expense created successfully!', 'data' => $register_expense], 201)
            : response()->json(['message' => 'Failed to create expense!'], 400);
    }

    // view
    public function all_expense()
    {
        $get_all_expenses = ExpenseModel::select('jamiat_id', 'voucher_no', 'year', 'name', 'date', 'cheque_no', 'description', 'log_user', 'attachment')->get();

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
            'cheque_no' => 'nullable|string',
            'description' => 'nullable|string',
            'log_user' => 'required|string',
            'attachment' => 'required|string',
        ]);

        $update_expense = $get_expense->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'voucher_no' => $request->input('voucher_no'),
            'year' => $request->input('year'),
            'name' => $request->input('name'),
            'date' => $request->input('date'),
            'cheque_no' => $request->input('cheque_no'),
            'description' => $request->input('description'),
            'log_user' => $request->input('log_user'),
            'attachment' => $request->input('attachment'),
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
            'payment_no' => 'required|string|unique:t_payments,payment_no|max:100',
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
            'cheque_no' => 'nullable|string|max:50',
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

        $register_payment = PaymentsModel::create([
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
            'amount' => $maximum_receivable_amount,
            'comments' => $request->input('comments'),
            'status' => $request->input('status'),
            'cancellation_reason' => $request->input('cancellation_reason'),
            'log_user' => $request->input('log_user'),
            'attachment' => $request->input('attachment'),
        ]);

        unset($register_payment['id'], $register_payment['created_at'], $register_payment['updated_at']);

        return $register_payment
            ? response()->json(['message' => 'Payment created successfully!', 'data' => $register_payment], 201)
            : response()->json(['message' => 'Failed to create payment!'], 400);
    }

    // view
    public function all_payments()
    {
        $get_all_payments = PaymentsModel::select('payment_no', 'jamiat_id', 'family_id', 'folio_no', 'name', 'its', 'sector', 'sub_sector', 'year', 'mode', 'date', 'bank_name', 'cheque_no', 'cheque_date', 'ifsc_code', 'transaction_id', 'transaction_date', 'amount', 'comments', 'status', 'cancellation_reason', 'log_user', 'attachment')->get();

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
            'cheque_no' => 'nullable|string|max:50',
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

    // Generate receipt number using prefix, value, and postfix
    $receipt_no = $counter->prefix . $counter->value . '/' . $counter->postfix;
    $formatted_receipt_no = str_replace('/', '_', $receipt_no);

    $validatedData = $request->validate([
        'family_id' => 'required|string|max:10',
        'date' => 'required|date',
        'its' => 'nullable|string|max:8',
        'folio_no' => 'nullable|string|max:20',
        'name' => 'required|string|max:100',
        'sector' => 'nullable|string|max:100',
        'sub_sector' => 'nullable|string|max:100',
        'amount' => 'required|numeric',
        'mode' => 'required|in:cheque,cash,neft,upi',
        'bank_name' => 'nullable|string|max:100',
        'cheque_no' => 'nullable|string|max:50',
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
        'payment_id' => 'nullable|integer',
    ]);

    $totalAmount = $request->input('amount');
    $mode = $request->input('mode');
    $maximumReceivable = ($mode === 'cash' && $totalAmount > 10000) ? 1000 : $totalAmount; // Logic for cash > 10K
    $remainingAmount = $totalAmount;

    $receipts = [];

    // Loop through family members and distribute the amount
    $get_family_member = User::select('name', 'its')
        ->where('family_id', $request->input('family_id'))
        ->get();

    if (count($get_family_member) < 1) {
        return response()->json(['message' => 'Sorry, failed to get users!'], 400);
    }

    foreach ($get_family_member as $member) {
        $amountsForMembers = min($remainingAmount, $maximumReceivable);

        $register_receipt = ReceiptsModel::create([
            'jamiat_id' => $jamiat_id,
            'family_id' => $validatedData['family_id'],
            'receipt_no' => $receipt_no,
            'date' => $validatedData['date'],
            'its' => $member->its,
            'folio_no' => $validatedData['folio_no'],
            'name' => $member->name,
            'sector' => $validatedData['sector'],
            'sub_sector' => $validatedData['sub_sector'],
            'amount' => $amountsForMembers,
            'mode' => $validatedData['mode'],
            'bank_name' => $validatedData['bank_name'],
            'cheque_no' => $validatedData['cheque_no'],
            'cheque_date' => $validatedData['cheque_date'],
            'ifsc_code' => $validatedData['ifsc_code'],
            'transaction_id' => $validatedData['transaction_id'],
            'transaction_date' => $validatedData['transaction_date'],
            'year' => $validatedData['year'],
            'comments' => $validatedData['comments'],
            'status' => $validatedData['status'],
            'cancellation_reason' => $validatedData['cancellation_reason'],
            'collected_by' => $validatedData['collected_by'],
            'log_user' => $validatedData['log_user'],
            'attachment' => $validatedData['attachment'],
            'payment_id' => $validatedData['payment_id'],
        ]);

        $receipts[] = $register_receipt;

        // Add WhatsApp queue entry
       

        // Call the receipt_print API to generate the PDF
        $pdfResponse = Http::get("http://api.fmb52.com/api/receipt_print/{$register_receipt->id}");

        if ($pdfResponse->successful()) {
            // Save the PDF in the public directory
            $pdfPath = "storage/{$jamiat_id}/receipts/{$formatted_receipt_no}.pdf";
            $publicPath = public_path($pdfPath);
            file_put_contents($publicPath, $pdfResponse->body());
        }
        $this->addToWhatsAppQueue($register_receipt,$pdfPath);

        $remainingAmount -= $amountsForMembers;

        if ($remainingAmount <= 0) {
            break;
        }
    }

    // Increment counter value after successful receipt creation
    DB::table('t_counter')
        ->where('jamiat_id', $jamiat_id)
        ->where('type', 'receipt')
        ->increment('value');

    // Handle advance receipt if remaining amount exists
    if ($remainingAmount > 0) {
        $get_hof_member = User::whereColumn('its', 'hof_its')
            ->where('family_id', $request->input('family_id'))
            ->first();

        $dataForAdvanceReceipt = [
            'jamiat_id' => $jamiat_id,
            'family_id' => $validatedData['family_id'],
            'name' => $get_hof_member->name,
            'amount' => $remainingAmount,
            'sector' => $validatedData['sector'],
            'sub_sector' => $validatedData['sub_sector'],
        ];

        $newRequestAdvanceReceipt = new Request($dataForAdvanceReceipt);
        $advanceReceiptResponse = $this->register_advance_receipt($newRequestAdvanceReceipt);

        if ($advanceReceiptResponse->getStatusCode() !== 201) {
            return response()->json(['message' => 'Failed to create Advance Receipt!'], 400);
        }
    }

    return response()->json([
        'message' => 'Receipt created successfully!',
        'receipts' => $receipts,
    ], 201);
}


    // view
    public function all_receipts(Request $request)
    {
        $user = Auth::user();
    
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.  11'], 403);
        }
    
        // Get user's accessible sector and sub-sector IDs
        $userSectorAccess = json_decode($user->sector_access_id, true); // Get user's sector access as an array
        $userSubSectorAccess = json_decode($user->sub_sector_access_id, true); // Get user's sub-sector access as an array
    
        if (empty($userSectorAccess) || empty($userSubSectorAccess)) {
            return response()->json([
                'message' => 'No access to any sectors or sub-sectors.',
            ], 403);
        }
    
        $get_all_receipts = ReceiptsModel::select(
            't_receipts.id', 't_receipts.jamiat_id', 't_receipts.family_id', 't_receipts.receipt_no',
            't_receipts.date', 't_receipts.its', 't_receipts.folio_no', 't_receipts.name',
            't_receipts.sector_id', 't_receipts.sub_sector_id', 't_receipts.amount', 't_receipts.mode',
            't_receipts.bank_name', 't_receipts.cheque_no', 't_receipts.cheque_date',
            't_receipts.ifsc_code', 't_receipts.transaction_id', 't_receipts.transaction_date',
            't_receipts.year', 't_receipts.comments', 't_receipts.status', 't_receipts.cancellation_reason',
            't_receipts.collected_by', 't_receipts.log_user', 't_receipts.attachment', 't_receipts.payment_id',
            'users.name as user_name', 'users.photo_id'
        )
        ->leftJoin('users', 't_receipts.its', '=', 'users.its') // Match `its` fields
        ->whereIn('t_receipts.sector_id', $userSectorAccess) // Filter by accessible sectors
        ->whereIn('t_receipts.sub_sector_id', $userSubSectorAccess) // Filter by accessible sub-sectors
        ->with([
            'user.photo:id,file_url' // Load only the `photo` URL
        ])
        ->orderBy('t_receipts.id', 'desc') // Order by `t_receipts.id` in descending order
        ->get();
    
        // Simplify the response to include only the photo URL
        $get_all_receipts->each(function ($receipt) {
            $receipt->photo_url = $receipt->user && $receipt->user->photo ? $receipt->user->photo->file_url : null;
            unset($receipt->user); // Remove the full user object
        });
    
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
            'sector' => 'nullable|string|max:100',
            'sub_sector' => 'nullable|string|max:100',
            'amount' => 'required|numeric',
            'mode' => 'required|in:cheque,cash,neft,upi',
            'bank_name' => 'nullable|string|max:100',
            'cheque_no' => 'nullable|string|max:50',
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
            'sector' => $request->input('sector'),
            'sub_sector' => $request->input('sub_sector'),
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
        $filePrefix = 'https://api.fmb52.com/';
        
        // Generate the full URL for the PDF
        $fullPdfUrl = $filePrefix . $pdfUrl;
    
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
}
