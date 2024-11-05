<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendorsModel;
use App\Models\FoodItemsModel;
use App\Models\FoodPurchaseModel;
use App\Models\FoodSaleModel;
use App\Models\DamageLostModel;
use App\Models\FoodPurchaseItemsModel;
use App\Models\FoodSaleItemsModel;


class InventoryController extends Controller
{
    //
    // create
    public function register_vendors(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'vendor_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'group' => 'required|string|max:255',
            'mobile' => 'required|string|max:20|unique:t_vendors,mobile',
            'email' => 'nullable|string|email|max:255|unique:t_vendors,email',
            'pan_card' => 'nullable|string|max:50',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|integer',
            'state' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_ifsc' => 'nullable|string|max:11',
            'bank_account_name' => 'nullable|string|max:255',
            'vpa' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
        ]);

        // $register_vendor = VendorsModel::create($request->all());
        $register_vendor = VendorsModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'vendor_id' => $request->input('vendor_id'),
            'name' => $request->input('name'),
            'company_name' => $request->input('company_name'),
            'group' => $request->input('group'),
            'mobile' => $request->input('mobile'),
            'email' => $request->input('email'),
            'pan_card' => $request->input('pan_card'),
            'address_line_1' => $request->input('address_line_1'),
            'address_line_2' => $request->input('address_line_2'),
            'city' => $request->input('city'),
            'pincode' => $request->input('pincode'),
            'state' => $request->input('state'),
            'bank_name' => $request->input('bank_name'),
            'bank_account_no' => $request->input('bank_account_no'),
            'bank_ifsc' => $request->input('bank_ifsc'),
            'bank_account_name' => $request->input('bank_account_name'),
            'vpa' => $request->input('vpa'),
            'status' => $request->input('status'),
        ]);

        unset($register_vendor['id'], $register_vendor['created_at'], $register_vendor['updated_at']);


        return $register_vendor
            ? response()->json(['message' => 'Vendor created successfully!', 'data' => $register_vendor], 201)
            : response()->json(['message' => 'Failed to create vendor!'], 400);
    }

    // view
    public function all_vendors()
    {
        $get_all_vendors = VendorsModel::select(
            'jamiat_id', 'vendor_id', 'name', 'company_name', 'group', 'mobile', 'email', 
            'pan_card', 'address_line_1', 'address_line_2', 'city', 'pincode', 'state',
            'bank_name', 'bank_account_no', 'bank_ifsc', 'bank_account_name', 'vpa', 'status'
        )->get();
    
        return $get_all_vendors->isNotEmpty()
            ? response()->json(['message' => 'Vendors fetched successfully!', 'data' => $get_all_vendors], 200)
            : response()->json(['message' => 'No vendors found!'], 404);
    }
    
    // update
    public function update_vendors(Request $request, $id)
    {
        $get_vendor = VendorsModel::find($id);

        if (!$get_vendor) {
            return response()->json(['message' => 'Vendor not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'vendor_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'group' => 'required|string|max:255',
            'mobile' => 'required|string|max:20|unique:t_vendors,mobile,' . $id,
            'email' => 'nullable|string|email|max:255|unique:t_vendors,email,' . $id,
            'pan_card' => 'nullable|string|max:50',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|integer',
            'state' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_ifsc' => 'nullable|string|max:11',
            'bank_account_name' => 'nullable|string|max:255',
            'vpa' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
        ]);

        // $update_vendor_record = $get_vendor->update($request->all());
        $update_vendor_record = $get_vendor->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'vendor_id' => $request->input('vendor_id'),
            'name' => $request->input('name'),
            'company_name' => $request->input('company_name'),
            'group' => $request->input('group'),
            'mobile' => $request->input('mobile'),
            'email' => $request->input('email'),
            'pan_card' => $request->input('pan_card'),
            'address_line_1' => $request->input('address_line_1'),
            'address_line_2' => $request->input('address_line_2'),
            'city' => $request->input('city'),
            'pincode' => $request->input('pincode'),
            'state' => $request->input('state'),
            'bank_name' => $request->input('bank_name'),
            'bank_account_no' => $request->input('bank_account_no'),
            'bank_ifsc' => $request->input('bank_ifsc'),
            'bank_account_name' => $request->input('bank_account_name'),
            'vpa' => $request->input('vpa'),
            'status' => $request->input('status'),
        ]);

        return ($update_vendor_record == 1)
            ? response()->json(['message' => 'Vendor updated successfully!', 'data' => $update_vendor_record], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_vendors($id)
    {
        $delete_vendor = VendorsModel::where('id', $id)->delete();

        return $delete_vendor
            ? response()->json(['message' => 'Vendor deleted successfully!'], 200)
            : response()->json(['message' => 'Vendor not found'], 404);
    }

    // create
    public function register_food_items(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'food_item_id' => 'required|integer|unique:t_food_items,food_item_id',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|in:kg,ltr,gm,pckt,box,bottles,nos,pcs,bags',
            'rate' => 'required|integer',
            'hsn' => 'nullable|string|max:50',
            'tax' => 'nullable|numeric',
            'log_user' => 'required|string|max:100',
        ]);

        $register_food_item = FoodItemsModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'food_item_id' => random_int(1000000000, 9999999999), // 10 digits random unique number
            'name' => $request->input('name'),
            'category' => $request->input('category'),
            'unit' => $request->input('unit'),
            'rate' => $request->input('rate'),
            'hsn' => $request->input('hsn'),
            'tax' => $request->input('tax'),
            'log_user' => $request->input('log_user'),
        ]);

        unset($register_food_item['id'], $register_food_item['created_at'], $register_food_item['updated_at']);

        return $register_food_item
            ? response()->json(['message' => 'Food item created successfully!', 'data' => $register_food_item], 201)
            : response()->json(['message' => 'Failed to create food item!'], 400);
    }

    // view
    public function all_food_items()
    {
        $get_all_food_items = FoodItemsModel::select(
            'jamiat_id', 'food_item_id', 'name', 'category', 'unit', 'rate', 'hsn', 'tax', 'log_user'
        )->get();

        return $get_all_food_items->isNotEmpty()
            ? response()->json(['message' => 'Food items fetched successfully!', 'data' => $get_all_food_items], 200)
            : response()->json(['message' => 'No food items found!'], 404);
    }

    // update
    public function update_food_items(Request $request, $id)
    {
        $get_food_item = FoodItemsModel::find($id);

        if (!$get_food_item) {
            return response()->json(['message' => 'Food item not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|in:kg,ltr,gm,pckt,box,bottles,nos,pcs,bags',
            'rate' => 'required|integer',
            'hsn' => 'nullable|string|max:50',
            'tax' => 'nullable|numeric',
            'log_user' => 'required|string|max:100',
        ]);

        // $update_food_item_record = $get_food_item->update($request->all());
        $update_food_item_record = $get_food_item->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'category' => $request->input('category'),
            'unit' => $request->input('unit'),
            'rate' => $request->input('rate'),
            'hsn' => $request->input('hsn'),
            'tax' => $request->input('tax'),
            'log_user' => $request->input('log_user'),
        ]);
    

        return ($update_food_item_record == 1)
            ? response()->json(['message' => 'Food item updated successfully!', 'data' => $update_food_item_record], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_food_items($id)
    {
        $delete_food_item = FoodItemsModel::where('id', $id)->delete();

        return $delete_food_item
            ? response()->json(['message' => 'Food item deleted successfully!'], 200)
            : response()->json(['message' => 'Food item not found'], 404);
    }

    // create
    public function register_damage_lost(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'food_item_id' => 'required|integer',
            'quantity' => 'required|integer',
            'remarks' => 'nullable|string',
            'log_user' => 'required|string|max:100',
        ]);

        $register_damage_lost = DamageLostModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'food_item_id' => $request->input('food_item_id'),
            'quantity' => $request->input('quantity'),
            'remarks' => $request->input('remarks'),
            'log_user' => $request->input('log_user'),
        ]);

        unset($register_damage_lost['id'], $register_damage_lost['created_at'], $register_damage_lost['updated_at']);

        return $register_damage_lost
            ? response()->json(['message' => 'Damage/Lost record created successfully!', 'data' => $register_damage_lost], 201)
            : response()->json(['message' => 'Failed to create record!'], 400);
    }

    // view
    public function all_damage_lost()
    {
        $get_all_damage_lost = DamageLostModel::select(
            'jamiat_id', 'food_item_id', 'quantity', 'remarks', 'log_user'
        )->get();

        return $get_all_damage_lost->isNotEmpty()
            ? response()->json(['message' => 'Damage/Lost records fetched successfully!', 'data' => $get_all_damage_lost], 200)
            : response()->json(['message' => 'No records found!'], 404);
    }

    // update
    public function update_damage_lost(Request $request, $id)
    {
        $get_damage_lost = DamageLostModel::find($id);

        if (!$get_damage_lost) {
            return response()->json(['message' => 'Damage/Lost record not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'food_item_id' => 'required|integer',
            'quantity' => 'required|integer',
            'remarks' => 'nullable|string',
            'log_user' => 'required|string|max:100',
        ]);

        // $update_damage_lost_record = $get_damage_lost->update($request->all());
        $update_damage_lost_record = $get_damage_lost->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'food_item_id' => $request->input('food_item_id'),
            'quantity' => $request->input('quantity'),
            'remarks' => $request->input('remarks'),
            'log_user' => $request->input('log_user'),
        ]);

        return ($update_damage_lost_record == 1)
            ? response()->json(['message' => 'Record updated successfully!', 'data' => $update_damage_lost_record], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_damage_lost($id)
    {
        $delete_damage_lost = DamageLostModel::where('id', $id)->delete();

        return $delete_damage_lost
            ? response()->json(['message' => 'Damage/Lost record deleted successfully!'], 200)
            : response()->json(['message' => 'Record not found!'], 404);
    }

    // create
    public function register_food_purchase(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'vendor_id' => 'required|integer',
            'invoice_no' => 'required|string|max:255',
            'date' => 'required|date',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|string|max:255',
            'total' => 'required|integer',
            'log_user' => 'required|string|max:100',
        ]);

        $register_food_purchase = FoodPurchaseModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'vendor_id' => $request->input('vendor_id'),
            'invoice_no' => $request->input('invoice_no'),
            'date' => $request->input('date'),
            'remarks' => $request->input('remarks'),
            'attachment' => $request->input('attachment'),
            'total' => $request->input('total'),
            'log_user' => $request->input('log_user'),
        ]);

        unset($register_food_purchase['id'], $register_food_purchase['created_at'], $register_food_purchase['updated_at']);

        return $register_food_purchase
            ? response()->json(['message' => 'Food purchase record created successfully!', 'data' => $register_food_purchase], 201)
            : response()->json(['message' => 'Failed to create food purchase record!'], 400);
    }

    // view
    public function all_food_purchase()
    {
        $get_all_food_purchase = FoodPurchaseModel::select(
            'jamiat_id', 'vendor_id', 'invoice_no', 'date', 'remarks', 'attachment', 'total', 'log_user'
        )->get();

        return $get_all_food_purchase->isNotEmpty()
            ? response()->json(['message' => 'Food purchase records fetched successfully!', 'data' => $get_all_food_purchase], 200)
            : response()->json(['message' => 'No food purchase records found!'], 404);
    }

    // update
    public function update_food_purchase(Request $request, $id)
    {
        $get_food_purchase = FoodPurchaseModel::find($id);

        if (!$get_food_purchase) {
            return response()->json(['message' => 'Food purchase record not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'vendor_id' => 'required|integer',
            'invoice_no' => 'required|string|max:255',
            'date' => 'required|date',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|string|max:255',
            'total' => 'required|integer',
            'log_user' => 'required|string|max:100',
        ]);

        // $update_food_purchase_record = $get_food_purchase->update($request->all());
        $update_food_purchase_record = $get_food_purchase->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'vendor_id' => $request->input('vendor_id'),
            'invoice_no' => $request->input('invoice_no'),
            'date' => $request->input('date'),
            'remarks' => $request->input('remarks'),
            'attachment' => $request->input('attachment'),
            'total' => $request->input('total'),
            'log_user' => $request->input('log_user'),
        ]);

        return ($update_food_purchase_record == 1)
            ? response()->json(['message' => 'Food purchase record updated successfully!', 'data' => $update_food_purchase_record], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_food_purchase($id)
    {
        $delete_food_purchase = FoodPurchaseModel::where('id', $id)->delete();

        return $delete_food_purchase
            ? response()->json(['message' => 'Food purchase record deleted successfully!'], 200)
            : response()->json(['message' => 'Food purchase record not found!'], 404);
    }

    // create
    public function register_food_purchase_items(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'purchase_id' => 'required|integer',
            'food_item_id' => 'required|integer',
            'quantity' => 'required|integer',
            'unit' => 'required|integer',
            'rate' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
        ]);

        $register_food_purchase_item = FoodPurchaseItemsModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'purchase_id' => $request->input('purchase_id'),
            'food_item_id' => $request->input('food_item_id'),
            'quantity' => $request->input('quantity'),
            'unit' => $request->input('unit'),
            'rate' => $request->input('rate'),
            'discount' => $request->input('discount'),
            'tax' => $request->input('tax'),
        ]);

        unset($register_food_purchase_item['id'], $register_food_purchase_item['created_at'], $register_food_purchase_item['updated_at']);

        return $register_food_purchase_item
            ? response()->json(['message' => 'Food Purchase Item created successfully!', 'data' => $register_food_purchase_item], 201)
            : response()->json(['message' => 'Failed to create Food Purchase Item!'], 400);
    }

    // view
    public function all_food_purchase_items()
    {
        $get_all_food_purchase_items = FoodPurchaseItemsModel::select(
            'jamiat_id', 'purchase_id', 'food_item_id', 'quantity', 'unit', 'rate', 'discount', 'tax'
        )->get();

        return $get_all_food_purchase_items->isNotEmpty()
            ? response()->json(['message' => 'Food Purchase Items fetched successfully!', 'data' => $get_all_food_purchase_items], 200)
            : response()->json(['message' => 'No records found!'], 404);
    }

    // update
    public function update_food_purchase_items(Request $request, $id)
    {
        $get_food_purchase_item = FoodPurchaseItemsModel::find($id);

        if (!$get_food_purchase_item) {
            return response()->json(['message' => 'Food Purchase Item not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'purchase_id' => 'required|integer',
            'food_item_id' => 'required|integer',
            'quantity' => 'required|integer',
            'unit' => 'required|integer',
            'rate' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
        ]);

        // $update_food_purchase_item = $get_food_purchase_item->update($request->all());
        $update_food_purchase_item = $get_food_purchase_item->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'purchase_id' => $request->input('purchase_id'),
            'food_item_id' => $request->input('food_item_id'),
            'quantity' => $request->input('quantity'),
            'unit' => $request->input('unit'),
            'rate' => $request->input('rate'),
            'discount' => $request->input('discount'),
            'tax' => $request->input('tax'),
        ]);

        return ($update_food_purchase_item == 1)
            ? response()->json(['message' => 'Food Purchase Item updated successfully!', 'data' => $update_food_purchase_item], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_food_purchase_items($id)
    {
        $delete_food_purchase_item = FoodPurchaseItemsModel::where('id', $id)->delete();

        return $delete_food_purchase_item
            ? response()->json(['message' => 'Food Purchase Item deleted successfully!'], 200)
            : response()->json(['message' => 'Food Purchase Item not found!'], 404);
    }

    // create
    public function register_food_sale(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'menu' => 'required|string|max:255',
            'family_id' => 'nullable|integer',
            'date' => 'required|date',
            'thaal_count' => 'required|integer',
            'total' => 'required|integer',
            'log_user' => 'required|string|max:100',
        ]);

        $register_food_sale = FoodSaleModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'menu' => $request->input('menu'),
            'family_id' => $request->input('family_id'),
            'date' => $request->input('date'),
            'thaal_count' => $request->input('thaal_count'),
            'total' => $request->input('total'),
            'log_user' => $request->input('log_user'),
        ]);

        unset($register_food_sale['id'], $register_food_sale['created_at'], $register_food_sale['updated_at']);

        return $register_food_sale
            ? response()->json(['message' => 'Food sale record created successfully!', 'data' => $register_food_sale], 201)
            : response()->json(['message' => 'Failed to create food sale record!'], 400);
    }

    // view
    public function all_food_sales()
    {
        $get_all_food_sales = FoodSaleModel::select(
            'jamiat_id', 'name', 'menu', 'family_id', 'date', 'thaal_count', 'total', 'log_user'
        )->get();

        return $get_all_food_sales->isNotEmpty()
            ? response()->json(['message' => 'Food sales fetched successfully!', 'data' => $get_all_food_sales], 200)
            : response()->json(['message' => 'No food sale records found!'], 404);
    }

    // update
    public function update_food_sale(Request $request, $id)
    {
        $get_food_sale = FoodSaleModel::find($id);

        if (!$get_food_sale) {
            return response()->json(['message' => 'Food sale record not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'menu' => 'required|string|max:255',
            'family_id' => 'nullable|integer',
            'date' => 'required|date',
            'thaal_count' => 'required|integer',
            'total' => 'required|integer',
            'log_user' => 'required|string|max:100',
        ]);

        // $update_food_sale_record = $get_food_sale->update($request->all());
        $update_food_sale_record = $get_food_sale->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'menu' => $request->input('menu'),
            'family_id' => $request->input('family_id'),
            'date' => $request->input('date'),
            'thaal_count' => $request->input('thaal_count'),
            'total' => $request->input('total'),
            'log_user' => $request->input('log_user'),
        ]);

        return ($update_food_sale_record == 1)
            ? response()->json(['message' => 'Food sale record updated successfully!', 'data' => $update_food_sale_record], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_food_sale($id)
    {
        $delete_food_sale = FoodSaleModel::where('id', $id)->delete();

        return $delete_food_sale
            ? response()->json(['message' => 'Food sale record deleted successfully!'], 200)
            : response()->json(['message' => 'Food sale record not found!'], 404);
    }

    // create
    public function register_food_sale_items(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'sale_id' => 'required|integer',
            'food_item_id' => 'required|integer',
            'quantity' => 'required|integer',
            'unit' => 'required|integer',
            'rate' => 'required|numeric',
        ]);

        $register_food_sale_item = FoodSaleItemsModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'sale_id' => $request->input('sale_id'),
            'food_item_id' => $request->input('food_item_id'),
            'quantity' => $request->input('quantity'),
            'unit' => $request->input('unit'),
            'rate' => $request->input('rate'),
        ]);

        unset($register_food_sale_item['id'], $register_food_sale_item['created_at'], $register_food_sale_item['updated_at']);

        return $register_food_sale_item
            ? response()->json(['message' => 'Food Sale Item created successfully!', 'data' => $register_food_sale_item], 201)
            : response()->json(['message' => 'Failed to create Food Sale Item!'], 400);
    }

    // view
    public function all_food_sale_items()
    {
        $get_all_food_sale_items = FoodSaleItemsModel::select(
            'jamiat_id', 'sale_id', 'food_item_id', 'quantity', 'unit', 'rate'
        )->get();

        return $get_all_food_sale_items->isNotEmpty()
            ? response()->json(['message' => 'Food Sale Items fetched successfully!', 'data' => $get_all_food_sale_items], 200)
            : response()->json(['message' => 'No records found!'], 404);
    }

    // update
    public function update_food_sale_items(Request $request, $id)
    {
        $get_food_sale_item = FoodSaleItemsModel::find($id);

        if (!$get_food_sale_item) {
            return response()->json(['message' => 'Food Sale Item not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'sale_id' => 'required|integer',
            'food_item_id' => 'required|integer',
            'quantity' => 'required|integer',
            'unit' => 'required|integer',
            'rate' => 'required|numeric',
        ]);

        // $update_food_sale_item = $get_food_sale_item->update($request->all());
        $update_food_sale_item = $get_food_sale_item->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'sale_id' => $request->input('sale_id'),
            'food_item_id' => $request->input('food_item_id'),
            'quantity' => $request->input('quantity'),
            'unit' => $request->input('unit'),
            'rate' => $request->input('rate'),
        ]);

        return ($update_food_sale_item == 1)
            ? response()->json(['message' => 'Food Sale Item updated successfully!', 'data' => $update_food_sale_item], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_food_sale_items($id)
    {
        $delete_food_sale_item = FoodSaleItemsModel::where('id', $id)->delete();

        return $delete_food_sale_item
            ? response()->json(['message' => 'Food Sale Item deleted successfully!'], 200)
            : response()->json(['message' => 'Food Sale Item not found!'], 404);
    }

}
