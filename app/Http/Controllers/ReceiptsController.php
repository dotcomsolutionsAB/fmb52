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


class ReceiptsController extends Controller
{

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

}