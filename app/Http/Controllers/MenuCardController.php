<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuCardModel;
use App\Models\DishModel;
use App\Models\DishItemsModel;

class MenuCardController extends Controller
{
    //
    // create
    public function register_menu_card(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'dish' => 'required|string|max:255',
        ]);

        $register_menu_card = MenuCardModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'dish' => $request->input('dish'),
        ]);

        unset($register_menu_card['id'], $register_menu_card['created_at'], $register_menu_card['updated_at']);

        return $register_menu_card
            ? response()->json(['message' => 'Menu card entry created successfully!', 'data' => $register_menu_card], 201)
            : response()->json(['message' => 'Failed to create menu card entry!'], 400);
    }

    // view
    public function all_menu_cards()
    {
        $get_all_menu_cards = MenuCardModel::select('jamiat_id', 'name', 'dish')->get();

        return $get_all_menu_cards->isNotEmpty()
            ? response()->json(['message' => 'Menu cards fetched successfully!', 'data' => $get_all_menu_cards], 200)
            : response()->json(['message' => 'No records found!'], 404);
    }

    // update
    public function update_menu_card(Request $request, $id)
    {
        $get_menu_card = MenuCardModel::find($id);

        if (!$get_menu_card) {
            return response()->json(['message' => 'Menu card entry not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'dish' => 'required|string|max:255',
        ]);

        $update_menu_card = $get_menu_card->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'dish' => $request->input('dish'),
        ]);

        return ($update_menu_card == 1)
            ? response()->json(['message' => 'Menu card entry updated successfully!', 'data' => $update_menu_card], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_menu_card($id)
    {
        $delete_menu_card = MenuCardModel::where('id', $id)->delete();

        return $delete_menu_card
            ? response()->json(['message' => 'Menu card entry deleted successfully!'], 200)
            : response()->json(['message' => 'Menu card entry not found!'], 404);
    }

    // create
    public function register_dish(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'log_user' => 'required|string|max:100',
        ]);

        $register_dish = DishModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'log_user' => $request->input('log_user'),
        ]);

        unset($register_dish['id'], $register_dish['created_at'], $register_dish['updated_at']);

        return $register_dish
            ? response()->json(['message' => 'Dish created successfully!', 'data' => $register_dish], 201)
            : response()->json(['message' => 'Failed to create dish!'], 400);
    }

    // view
    public function all_dishes()
    {
        $get_all_dishes = DishModel::select('jamiat_id', 'name', 'log_user')->get();

        return $get_all_dishes->isNotEmpty()
            ? response()->json(['message' => 'Dishes fetched successfully!', 'data' => $get_all_dishes], 200)
            : response()->json(['message' => 'No records found!'], 404);
    }

    // update
    public function update_dish(Request $request, $id)
    {
        $get_dish = DishModel::find($id);

        if (!$get_dish) {
            return response()->json(['message' => 'Dish not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'log_user' => 'required|string|max:100',
        ]);

        $update_dish = $get_dish->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'log_user' => $request->input('log_user'),
        ]);

        return($update_dish == 1)
            ? response()->json(['message' => 'Dish updated successfully!', 'data' => $update_dish], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_dish($id)
    {
        $delete_dish = DishModel::where('id', $id)->delete();

        return $delete_dish
            ? response()->json(['message' => 'Dish deleted successfully!'], 200)
            : response()->json(['message' => 'Dish not found!'], 404);
    }

    // create
    public function register_dish_items(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'dish_id' => 'required|integer',
            'food_item_id' => 'required|integer',
            'quantity' => 'required|integer',
            'unit' => 'required|numeric',
        ]);

        $register_dish_item = DishItemsModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'dish_id' => $request->input('dish_id'),
            'food_item_id' => $request->input('food_item_id'),
            'quantity' => $request->input('quantity'),
            'unit' => $request->input('unit'),
        ]);

        unset($register_dish_item['id'], $register_dish_item['created_at'], $register_dish_item['updated_at']);

        return $register_dish_item
            ? response()->json(['message' => 'Dish Item created successfully!', 'data' => $register_dish_item], 201)
            : response()->json(['message' => 'Failed to create Dish Item!'], 400);
    }

    // view
    public function all_dish_items()
    {
        $get_all_dish_items = DishItemsModel::select('jamiat_id', 'dish_id', 'food_item_id', 'quantity', 'unit')->get();

        return $get_all_dish_items->isNotEmpty()
            ? response()->json(['message' => 'Dish Items fetched successfully!', 'data' => $get_all_dish_items], 200)
            : response()->json(['message' => 'No records found!'], 404);
    }

    // update
    public function update_dish_item(Request $request, $id)
    {
        $get_dish_item = DishItemsModel::find($id);

        if (!$get_dish_item) {
            return response()->json(['message' => 'Dish Item not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'dish_id' => 'required|integer',
            'food_item_id' => 'required|integer',
            'quantity' => 'required|integer',
            'unit' => 'required|numeric',
        ]);

        $update_dish_item = $get_dish_item->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'dish_id' => $request->input('dish_id'),
            'food_item_id' => $request->input('food_item_id'),
            'quantity' => $request->input('quantity'),
            'unit' => $request->input('unit'),
        ]);

        return ($update_dish_item == 1)
            ? response()->json(['message' => 'Dish Item updated successfully!', 'data' => $update_dish_item], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_dish_item($id)
    {
        $delete_dish_item = DishItemsModel::where('id', $id)->delete();

        return $delete_dish_item
            ? response()->json(['message' => 'Dish Item deleted successfully!'], 200)
            : response()->json(['message' => 'Dish Item not found!'], 404);
    }

}
