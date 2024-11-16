<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CounterModel;
use App\Models\AdvanceReceiptModel;
use App\Models\ExpenseModel;
use App\Models\PaymentsModel;
use App\Models\ReceiptsModel;
use App\Models\User;


class AccountsController extends Controller
{
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
            'jamiat_id' => 'required|string',
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
        $validatedData = $request->validate([
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
            'payment_id' => 'nullable|integer',
        ]);

        $get_family_member = User::select('name')
                                  ->where('family_id', $request->input('family_id'))
                                  ->get();
                                  

        if (count($get_family_member) < 1) {
            return response()->json(['message' => 'Sorry, failed to get users!'], 400);
        }

        $totalAmount = $request->input('amount');
        $maximumReceivable = 10000;
        $remainingAmount = $totalAmount;

        $receipts = []; 

        foreach ($get_family_member as $member) {
            $amountsForMembers = min($remainingAmount, $maximumReceivable);

            $register_receipt = ReceiptsModel::create([
                'jamiat_id' => $request->input('jamiat_id'),
                'family_id' => $request->input('family_id'),
                'receipt_no' => $request->input('receipt_no'),
                'date' => $request->input('date'),
                'its' => $request->input('its'),
                'folio_no' => $request->input('folio_no'),
                'name' => $member->name,
                'sector' => $request->input('sector'),
                'sub_sector' => $request->input('sub_sector'),
                'amount' => $amountsForMembers,
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

            $receipts [] = $register_receipt;

            $remainingAmount -= $amountsForMembers;

            if ($remainingAmount <= 0) {
                break;
            }
        }

        if ($remainingAmount > 0) 
        {
            $dataForAdvanceReceipt = [
                'jamiat_id' => $validatedData['jamiat_id'], 
                'family_id' => $validatedData['family_id'],
                'name' => $validatedData['name'],
                'amount' => $remainingAmount,
                'sector' => $validatedData['sector'], 
                'sub_sector' => $validatedData['sub_sector'], 
            ];
            $newRequestAdvanceReceipt = new Request($dataForAdvanceReceipt);

           $advanceReceiptResponse = $this->register_advance_receipt($newRequestAdvanceReceipt);

            if ($advanceReceiptResponse->getStatusCode() !== 201) {
                return response()->json(['message' => 'Failed to create Advance Receipt!'], 400);
            }

            $advanceReceiptData = $advanceReceiptResponse->getOriginalContent();
        }

        unset($register_receipt['id'], $register_receipt['created_at'], $register_receipt['updated_at']);

        return $register_receipt
            ? response()->json(['message' => 'Receipt created successfully!', 'receipts' => $receipts, 'advance_receipt' => $advanceReceiptData ?? null], 201)
            : response()->json(['message' => 'Failed to create receipt!'], 400);
    }

    // view
    public function all_receipts()
    {
        $get_all_receipts = ReceiptsModel::select(
            'jamiat_id', 'family_id', 'receipt_no', 'date', 'its', 'folio_no', 'name',
            'sector', 'sub_sector', 'amount', 'mode', 'bank_name', 'cheque_no', 'cheque_date',
            'ifsc_code', 'transaction_id', 'transaction_date', 'year', 'comments', 'status', 'cancellation_reason', 'collected_by', 'log_user', 'attachment', 'payment_id'
        )->get();

        return $get_all_receipts->isNotEmpty()
            ? response()->json(['message' => 'Receipts fetched successfully!', 'data' => $get_all_receipts], 200)
            : response()->json(['message' => 'No receipts found!'], 404);
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

}
