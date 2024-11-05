<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JamiatModel;
use App\Models\JamiatSettingsModel;
use App\Models\SuperAdminReceiptsModel;
use App\Models\SuperAdminCounterModel;

class JamiatController extends Controller
{
    //
    // create
    public function register_jamiat(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'mobile' => 'required|string|max:20',
            'email' => 'required|string|email|max:150|unique:t_jamiat,email',
            'package' => 'required|integer',
            'billing_address' => 'nullable|string',
            'billing_contact' => 'nullable|string|max:150',
            'billing_email' => 'nullable|string|email|max:150',
            'billing_phone' => 'nullable|string|max:20',
            'last_payment_date' => 'nullable|date',
            'last_payment_amount' => 'nullable|numeric',
            'payment_due_date' => 'nullable|date',
            'validity' => 'required|date',
            'notes' => 'nullable|string',
            'logs' => 'nullable|string',
        ]);

        $jamiat = JamiatModel::create([
            'name' => $request->input('name'),
            'mobile' => $request->input('mobile'),
            'email' => $request->input('email'),
            'package' => $request->input('package'),
            'billing_address' => $request->input('billing_address'),
            'billing_contact' => $request->input('billing_contact'),
            'billing_email' => $request->input('billing_email'),
            'billing_phone' => $request->input('billing_phone'),
            'last_payment_date' => $request->input('last_payment_date'),
            'last_payment_amount' => $request->input('last_payment_amount'),
            'payment_due_date' => $request->input('payment_due_date'),
            'validity' => $request->input('validity'),
            'notes' => $request->input('notes'),
            'logs' => $request->input('logs')
        ]);

        unset($jamiat['id'], $jamiat['created_at'], $jamiat['updated_at']);

        return $jamiat
            ? response()->json(['message' => 'Jamiat created successfully!', 'data' => $jamiat], 201)
            : response()->json(['message' => 'Failed to create Jamiat!'], 400);
    }

    // view
    public function view_jamiats()
    {
        $jamiats = JamiatModel::select('name', 'mobile', 'email', 'package', 'billing_address', 'billing_contact', 'billing_email', 'billing_phone', 'last_payment_date', 'last_payment_amount', 'payment_due_date', 'validity', 'notes', 'logs')->get();

        return $jamiats->isNotEmpty()
            ? response()->json(['message' => 'Jamiats fetched successfully!', 'data' => $jamiats], 200)
            : response()->json(['message' => 'No Jamiats found!'], 404);
    }

    // update
    public function update_jamiat(Request $request, $id)
    {
        $jamiat = JamiatModel::find($id);

        if (!$jamiat) {
            return response()->json(['message' => 'Jamiat not found!'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:150',
            'mobile' => 'required|string|max:20',
            'email' => 'required|string|email|max:150|unique:t_jamiat,email,' . $id,
            'package' => 'required|integer',
            'billing_address' => 'nullable|string',
            'billing_contact' => 'nullable|string|max:150',
            'billing_email' => 'nullable|string|email|max:150',
            'billing_phone' => 'nullable|string|max:20',
            'last_payment_date' => 'nullable|date',
            'last_payment_amount' => 'nullable|numeric',
            'payment_due_date' => 'nullable|date',
            'validity' => 'required|date',
            'notes' => 'nullable|string',
            'logs' => 'nullable|string',
        ]);

        $update_jamiat = $jamiat->update([
            'name' => $request->input('name'),
            'mobile' => $request->input('mobile'),
            'email' => $request->input('email'),
            'package' => $request->input('package'),
            'billing_address' => $request->input('billing_address'),
            'billing_contact' => $request->input('billing_contact'),
            'billing_email' => $request->input('billing_email'),
            'billing_phone' => $request->input('billing_phone'),
            'last_payment_date' => $request->input('last_payment_date'),
            'last_payment_amount' => $request->input('last_payment_amount'),
            'payment_due_date' => $request->input('payment_due_date'),
            'validity' => $request->input('validity'),
            'notes' => $request->input('notes'),
            'logs' => $request->input('logs')
        ]);

        return ($update_jamiat == 1)
            ? response()->json(['message' => 'Jamiat updated successfully!', 'data' => $update_jamiat], 200)
            : response()->json(['No changes detected'], 304);
    }
    // delete
    public function delete_jamiat($id)
    {
        $jamiat = JamiatModel::find($id);

        if (!$jamiat) {
            return response()->json(['message' => 'Jamiat not found!'], 404);
        }

        $jamiat->delete();

        return response()->json(['message' => 'Jamiat deleted successfully!'], 200);
    }


    // create
    public function register_jamiat_settings(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'value' => 'required|string'
        ]);

        $jamiat_setting = JamiatSettingsModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'value' => $request->input('value')
        ]);

        unset($jamiat_setting['id'], $jamiat_setting['created_at'], $jamiat_setting['updated_at']);

        return isset($jamiat_setting)
            ? response()->json(['message' => 'Setting created successfully!', 'data' => $jamiat_setting], 201)
            : response()->json(['message' => 'Failed to create setting!'], 400);
    }

    // view
    public function view_jamiat_settings()
    {
        $settings = JamiatSettingsModel::select('jamiat_id', 'name', 'value')->get();

        return $settings->isNotEmpty()
            ? response()->json(['message' => 'Settings fetched successfully!', 'data' => $settings], 200)
            : response()->json(['message' => 'No settings found!'], 404);
    }

    // update
    public function update_jamiat_settings(Request $request, $id)
    {
        $jamiat_settings = JamiatSettingsModel::find($id);

        if (!$jamiat_settings) {
            return response()->json(['message' => 'Setting not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'value' => 'required|string'
        ]);

        $update_jamiat_settings = $jamiat_settings->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'value' => $request->input('value')
        ]);

        return ($update_jamiat_settings == 1)
            ? response()->json(['message' => 'Jamiat settings updated successfully!', 'data' => $update_jamiat_settings], 200)
            : response()->json(['No changes detected'], 304);

    }

    // delete
    public function delete_jamiat_settings($id)
    {
        $jamiat_setting = JamiatSettingsModel::find($id);

        if (!$jamiat_setting) {
            return response()->json(['message' => 'Setting not found!'], 404);
        }

        $jamiat_setting->delete();

        return response()->json(['message' => 'Setting deleted successfully!'], 200);
    }

    // create
    public function register_super_admin_receipts(Request $request)
    {
        // Validation rules for the receipt
        $request->validate([
            'jamiat_id' => 'required|integer',
            'amount' => 'required|numeric',
            'package' => 'required|integer',
            'payment_date' => 'required|date',
            'receipt_number' => 'required|string|max:100|unique:t_super_admin_receipts,receipt_number',
            'created_by' => 'required|integer',
            'notes' => 'nullable|string'
        ]);

        // Create new receipt record
        $receipt = SuperAdminReceiptsModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'amount' => $request->input('amount'),
            'package' => $request->input('package'),
            'payment_date' => $request->input('payment_date'),
            'receipt_number' => $request->input('receipt_number'),
            'created_by' => $request->input('created_by'),
            'notes' => $request->input('notes')
        ]);

        unset($receipt['id'], $receipt['created_at'], $receipt['updated_at']);

        return isset($receipt)
            ? response()->json(['message' => 'Receipt created successfully!', 'data' => $receipt], 201)
            : response()->json(['message' => 'Failed to create receipt!'], 400);
    }

    // view
    public function view_super_admin_receipts()
    {
        // Fetch all receipt records
        $receipts = SuperAdminReceiptsModel::select('jamiat_id', 'amount', 'package', 'payment_date', 'receipt_number', 'created_by', 'notes')->get();

        return $receipts->isNotEmpty()
            ? response()->json(['message' => 'Receipts fetched successfully!', 'data' => $receipts], 200)
            : response()->json(['message' => 'No receipts found!'], 404);
    }

    // update
    public function update_super_admin_receipt(Request $request, $id)
    {
        // Find the specific receipt by ID
        $receipt = SuperAdminReceiptsModel::find($id);

        if (!$receipt) {
            return response()->json(['message' => 'Receipt not found!'], 404);
        }

        // Validation rules for updating the receipt
        $request->validate([
            'jamiat_id' => 'required|integer',
            'amount' => 'required|numeric',
            'package' => 'required|integer',
            'payment_date' => 'required|date',
            'receipt_number' => 'required|string|max:100|unique:t_super_admin_receipts,receipt_number,' . $id,
            'created_by' => 'required|integer',
            'notes' => 'nullable|string'
        ]);

        // Update the receipt record
        $update_receipt = $receipt->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'amount' => $request->input('amount'),
            'package' => $request->input('package'),
            'payment_date' => $request->input('payment_date'),
            'receipt_number' => $request->input('receipt_number'),
            'created_by' => $request->input('created_by'),
            'notes' => $request->input('notes')
        ]);

        return ($update_receipt == 1)
            ? response()->json(['message' => 'Receipt updated successfully!', 'data' => $update_receipt], 200)
            : response()->json(['No changes detected'], 304);
    }

    public function delete_super_admin_receipt($id)
    {
        // Find the specific receipt by ID
        $receipt = SuperAdminReceiptsModel::find($id);

        if (!$receipt) {
            return response()->json(['message' => 'Receipt not found!'], 404);
        }

        // Delete the receipt
        $receipt->delete();

        return response()->json(['message' => 'Receipt deleted successfully!'], 200);
    }

    // create
    public function register_super_admin_counter(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:100|unique:t_super_admin_counter,key',
            'value' => 'required|string',
        ]);

        $counter_entry = SuperAdminCounterModel::create([
            'key' => $request->input('key'),
            'value' => $request->input('value'),
        ]);

        unset($counter_entry['id'], $counter_entry['created_at'], $counter_entry['updated_at']);

        return isset($counter_entry)
            ? response()->json(['message' => 'Counter entry created successfully!', 'data' => $counter_entry], 201)
            : response()->json(['message' => 'Failed to create counter entry!'], 400);
    }

    // view
    public function view_super_admin_counters()
    {
        $counters = SuperAdminCounterModel::select('key', 'value')->get();

        return $counters->isNotEmpty()
            ? response()->json(['message' => 'Counters fetched successfully!', 'data' => $counters], 200)
            : response()->json(['message' => 'No counter entries found!'], 404);
    }

    // update
    public function update_super_admin_counter(Request $request, $id)
    {
        $counter_entry = SuperAdminCounterModel::find($id);

        if (!$counter_entry) {
            return response()->json(['message' => 'Counter entry not found!'], 404);
        }

        $request->validate([
            'key' => 'required|string',
            'value' => 'required|string',
        ]);

        $update_counter_entry = $counter_entry->update([
            'key' => $request->input('key'),
            'value' => $request->input('value'),
        ]);

        return ($update_counter_entry == 1)
            ? response()->json(['message' => 'Super-Admin counter updated successfully!', 'data' => $update_counter_entry], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_super_admin_counter($id)
    {
        // $counter_entry = SuperAdminCounterModel::where('key', $key)->first();
        // Find the specific receipt by ID
        $counter_entry = SuperAdminCounterModel::find($id);

        if (!$counter_entry) {
            return response()->json(['message' => 'Counter entry not found!'], 404);
        }

        $counter_entry->delete();

        return response()->json(['message' => 'Counter entry deleted successfully!'], 200);
    }
}
