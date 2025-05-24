<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

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

class PaymentsController extends Controller
{
    public function register_payments(Request $request)
    {
        $validatedData = $request->validate([
            'date' => 'nullable|date', // Multiple receipt IDs for cash or single receipt for others
            'receipt_ids' => 'nullable|array', // Multiple receipt IDs for cash or single receipt for others
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
                'date'               => now()->timezone('Asia/Kolkata')->toDateString(),
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

        if($updatedStatus) {
            return response()->json(['message' => 'Payment status updated successfully!'], 200);
        }

        return response()->json(['message' => 'Failed to update payment status.'], 500);
    }

}