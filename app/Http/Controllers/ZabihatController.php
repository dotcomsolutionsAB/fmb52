<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ZabihatModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use Auth;

class ZabihatController extends Controller
{
    //
    public function register_zabihat(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'family_id' => 'required|string|max:255',
            'year' => 'required|string|max:10',
            'zabihat_count' => 'required|integer',
            'hub_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'due_amount' => 'required|numeric',
            'log_user' => 'required|string|max:100',
        ]);

        $register_zabihat = ZabihatModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'year' => $request->input('year'),
            'zabihat_count' => $request->input('zabihat_count'),
            'hub_amount' => $request->input('hub_amount'),
            'paid_amount' => $request->input('paid_amount'),
            'due_amount' => $request->input('due_amount'),
            'log_user' => $request->input('log_user'),
        ]);

        unset($register_zabihat['id'], $register_zabihat['created_at'], $register_zabihat['updated_at']);

        return $register_zabihat
            ? response()->json(['message' => 'Zabihat record created successfully!', 'data' => $register_zabihat], 201)
            : response()->json(['message' => 'Failed to create zabihat record!'], 400);
    }

    // view
    public function all_zabihat()
    {
        $get_all_zabihats = ZabihatModel::select('jamiat_id', 'family_id', 'year', 'zabihat_count', 'hub_amount', 'paid_amount', 'due_amount', 'log_user')->get();

        return $get_all_zabihats->isNotEmpty()
            ? response()->json(['message' => 'Zabihat records fetched successfully!', 'data' => $get_all_zabihats], 200)
            : response()->json(['message' => 'No zabihat records found!'], 404);
    }

    // update
    public function update_zabihat(Request $request, $id)
    {
        $get_zabihat = ZabihatModel::find($id);

        if (!$get_zabihat) {
            return response()->json(['message' => 'Zabihat record not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'family_id' => 'required|string|max:255',
            'year' => 'required|string|max:10',
            'zabihat_count' => 'required|integer',
            'hub_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'due_amount' => 'required|numeric',
            'log_user' => 'required|string|max:100',
        ]);

        $update_zabihat_record = $get_zabihat->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'family_id' => $request->input('family_id'),
            'year' => $request->input('year'),
            'zabihat_count' => $request->input('zabihat_count'),
            'hub_amount' => $request->input('hub_amount'),
            'paid_amount' => $request->input('paid_amount'),
            'due_amount' => $request->input('due_amount'),
            'log_user' => $request->input('log_user'),
        ]);

        return ($update_zabihat_record == 1)
            ? response()->json(['message' => 'Zabihat record updated successfully!', 'data' => $update_zabihat_record], 200)
            : response()->json(['No changes detected!'], 304);
    }

    // delete
    public function delete_zabihat($id)
    {
        $delete_zabihat = ZabihatModel::where('id', $id)->delete();

        return $delete_zabihat
            ? response()->json(['message' => 'Zabihat record deleted successfully!'], 200)
            : response()->json(['message' => 'Zabihat record not found!'], 404);
    }

}
