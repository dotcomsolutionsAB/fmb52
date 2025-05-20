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

class ExpenseController extends Controller
{
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
}