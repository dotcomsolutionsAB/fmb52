<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\MumeneenController;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\CSVImportController;
use App\Http\Controllers\MenuCardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\JamiatController;
use App\Http\Controllers\SectorImportController;
use App\Http\Controllers\SubSectorImportController;
use App\Http\Controllers\PermissionRoleController;

Route::post('/register', [MumeneenController::class, 'register_users']);
Route::post('/get_otp', [AuthController::class, 'generate_otp']);
Route::post('/login/{id?}', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/import_users', [CSVImportController::class, 'importUser']);
    Route::post('/migrate_user_csv', [CSVImportController::class, 'migrateFromCsv']);

    // Register New Jamaat
    Route::post('/register_jamaat', [JamiatController::class, 'register_jamaat']);
    Route::post('/verify_email', [JamiatController::class, 'verify_email']);

    Route::prefix('permissions')->group(function () {
        Route::post('/create', [PermissionRoleController::class, 'createPermission']);
        Route::post('/create-bulk', [PermissionRoleController::class, 'createBulkPermissions']);
        Route::get('/all', [PermissionRoleController::class, 'getAllPermissions']);
        Route::delete('/delete', [PermissionRoleController::class, 'deletePermission']);
        Route::delete('/delete-bulk', [PermissionRoleController::class, 'deleteBulkPermissions']);
    });

    Route::prefix('roles')->group(function () {
        Route::post('/create', [PermissionRoleController::class, 'createRole']);
        Route::post('/add-permissions', [PermissionRoleController::class, 'addPermissionsToRole']);
        Route::get('/all', [PermissionRoleController::class, 'getAllRoles']);
        Route::put('/edit', [PermissionRoleController::class, 'editRole']);
        Route::delete('/delete', [PermissionRoleController::class, 'deleteRole']);
    });

    Route::prefix('users')->group(function () {
        Route::post('/assign-permissions', [PermissionRoleController::class, 'assignPermissionsToUser']);
        Route::get('/{userId}/permissions', [PermissionRoleController::class, 'getUserPermissions']);
    });

    Route::prefix('roles')->group(function () {
        Route::get('/{roleName}/permissions', [PermissionRoleController::class, 'getRolePermissions']);
    });


    // user
    Route::get('/user', [MumeneenController::class, 'users']);
    Route::get('/get_all_user/{year?}', [MumeneenController::class, 'usersWithHubData']);
    Route::post('/update_user/{id}', [MumeneenController::class, 'update_record']);
    Route::get('/user_details/{id}', [MumeneenController::class, 'get_user']);
    Route::post('/split', [MumeneenController::class, 'split_family']);
    Route::post('/merge', [MumeneenController::class, 'merge_family']);
    Route::delete('/user/{id}', [MumeneenController::class, 'delete_user']);
    Route::get('/user_migrate', [MumeneenController::class, 'migrate']);
    Route::post('/get_family_user', [MumeneenController::class, 'usersByFamily']);

    // its
    Route::post('/its', [MumeneenController::class, 'register_its']);
    Route::get('/its', [MumeneenController::class, 'all_its']);
    Route::post('/update_its/{id}', [MumeneenController::class, 'update_its']);
    Route::delete('/its/{id}', [MumeneenController::class, 'delete_its']);
    
    // sector
    Route::post('/sector', [MumeneenController::class, 'register_sector']);
    Route::get('/sector', [MumeneenController::class, 'all_sector']);
    Route::post('/update_sector/{id}', [MumeneenController::class, 'update_sector']);
    Route::delete('/sector/{id}', [MumeneenController::class, 'delete_sector']);

    // sub-sector
    Route::post('/sub_sector', [MumeneenController::class, 'register_sub_sector']);
    Route::get('/sub_sector', [MumeneenController::class, 'all_sub_sector']);
    Route::post('/update_sub_sector/{id}', [MumeneenController::class, 'update_sub_sector']);
    Route::delete('/sub_sector/{id}', [MumeneenController::class, 'delete_sub_sector']);

    // building
    Route::post('/building', [MumeneenController::class, 'register_building']);
    Route::get('/building', [MumeneenController::class, 'all_building']);
    Route::post('/update_building/{id}', [MumeneenController::class, 'update_building']);
    Route::delete('/building/{id}', [MumeneenController::class, 'delete_building']);

    // year
    Route::post('/year', [MumeneenController::class, 'register_year']);
    Route::get('/year', [MumeneenController::class, 'all_years']);
    Route::post('/update_year/{id}', [MumeneenController::class, 'update_year']);
    Route::delete('/year/{id}', [MumeneenController::class, 'delete_year']);

    // menu
    Route::post('/menu', [MumeneenController::class, 'register_menu']);
    Route::get('/menu', [MumeneenController::class, 'all_menu']);
    Route::post('/update_menu/{id}', [MumeneenController::class, 'update_menu']);
    Route::delete('/menu/{id}', [MumeneenController::class, 'delete_menu']);
    
    // fcm
    Route::post('/fcm', [MumeneenController::class, 'register_fcm']);
    Route::get('/fcm', [MumeneenController::class, 'all_fcm']);
    Route::post('/update_fcm/{id}', [MumeneenController::class, 'update_fcm']);
    Route::delete('/fcm/{id}', [MumeneenController::class, 'delete_fcm']);

    // hub
    Route::post('/hub', [MumeneenController::class, 'register_hub']);
    Route::get('/hub', [MumeneenController::class, 'all_hub']);
    Route::post('/update_hub/{id}', [MumeneenController::class, 'update_hub']);
    Route::delete('/hub/{id}', [MumeneenController::class, 'delete_hub']);

    // zabihat
    Route::post('/zabihat', [MumeneenController::class, 'register_zabihat']);
    Route::get('/zabihat', [MumeneenController::class, 'all_zabihat']);
    Route::post('/update_zabihat/{id}', [MumeneenController::class, 'update_zabihat']);
    Route::delete('/zabihat/{id}', [MumeneenController::class, 'delete_zabihat']);
    
    // counter
    Route::post('/counter', [AccountsController::class, 'register_counter']);
    Route::get('/counter', [AccountsController::class, 'all_counter']);
    Route::post('/update_counter/{id}', [AccountsController::class, 'update_counter']);
    Route::delete('/counter/{id}', [AccountsController::class, 'delete_counter']);
    
    // advance_receipt
    Route::post('/advance_receipt', [AccountsController::class, 'register_advance_receipt']);
    Route::get('/advance_receipt', [AccountsController::class, 'all_advance_receipt']);
    Route::post('/update_advance_receipt/{id}', [AccountsController::class, 'update_advance_receipt']);
    Route::delete('/advance_receipt/{id}', [AccountsController::class, 'delete_advance_receipt']);
    
    // expense
    Route::post('/expense', [AccountsController::class, 'register_expense']);
    Route::get('/expense', [AccountsController::class, 'all_expense']);
    Route::post('/update_expense/{id}', [AccountsController::class, 'update_expense']);
    Route::delete('/expense/{id}', [AccountsController::class, 'delete_expense']);
    
    // payments
    Route::post('/payments', [AccountsController::class, 'register_payments']);
    Route::get('/payments', [AccountsController::class, 'all_payments']);
    Route::post('/update_payments/{id}', [AccountsController::class, 'update_payments']);
    Route::delete('/payments/{id}', [AccountsController::class, 'delete_payments']);

    // receipts
    Route::post('/receipts', [AccountsController::class, 'register_receipts']);
    Route::get('/receipts', [AccountsController::class, 'all_receipts']);
    Route::post('/update_receipts/{id}', [AccountsController::class, 'update_receipts']);
    Route::delete('/receipts/{id}', [AccountsController::class, 'delete_receipts']);

    // vendors
    Route::post('/vendors', [InventoryController::class, 'register_vendors']);
    Route::get('/vendors', [InventoryController::class, 'all_vendors']);
    Route::post('/update_vendors/{id}', [InventoryController::class, 'update_vendors']);
    Route::delete('/vendors/{id}', [InventoryController::class, 'delete_vendors']);

    // food_items
    Route::post('/food_items', [InventoryController::class, 'register_food_items']);
    Route::get('/food_items', [InventoryController::class, 'all_food_items']);
    Route::post('/update_food_items/{id}', [InventoryController::class, 'update_food_items']);
    Route::delete('/food_items/{id}', [InventoryController::class, 'delete_food_items']);

    // damage_lost
    Route::post('/damage_lost', [InventoryController::class, 'register_damage_lost']);
    Route::get('/damage_lost', [InventoryController::class, 'all_damage_lost']);
    Route::post('/update_damage_lost/{id}', [InventoryController::class, 'update_damage_lost']);
    Route::delete('/damage_lost/{id}', [InventoryController::class, 'delete_damage_lost']);

    // food_purchase
    Route::post('/food_purchase', [InventoryController::class, 'register_food_purchase']);
    Route::get('/food_purchase', [InventoryController::class, 'all_food_purchase']);
    Route::post('/update_food_purchase/{id}', [InventoryController::class, 'update_food_purchase']);
    Route::delete('/food_purchase/{id}', [InventoryController::class, 'delete_food_purchase']);

    // food_purchase_items
    Route::post('/food_purchase_items', [InventoryController::class, 'register_food_purchase_items']);
    Route::get('/food_purchase_items', [InventoryController::class, 'all_food_purchase_items']);
    Route::post('/update_food_purchase_items/{id}', [InventoryController::class, 'update_food_purchase_items']);
    Route::delete('/food_purchase_items/{id}', [InventoryController::class, 'delete_food_purchase_items']);

    // food_sale
    Route::post('/food_sale', [InventoryController::class, 'register_food_sale']);
    Route::get('/food_sale', [InventoryController::class, 'all_food_sales']);
    Route::post('/update_food_sale/{id}', [InventoryController::class, 'update_food_sale']);
    Route::delete('/food_sale/{id}', [InventoryController::class, 'delete_food_sale']);
    
    // food_sale_items
    Route::post('/food_sale_items', [InventoryController::class, 'register_food_sale_items']);
    Route::get('/food_sale_items', [InventoryController::class, 'all_food_sale_items']);
    Route::post('/update_food_sale_items/{id}', [InventoryController::class, 'update_food_sale_items']);
    Route::delete('/food_sale_items/{id}', [InventoryController::class, 'delete_food_sale_items']);

    // menu_card
    Route::post('/menu_card', [MenuCardController::class, 'register_menu_card']);
    Route::get('/menu_card', [MenuCardController::class, 'all_menu_cards']);
    Route::post('/update_menu_card/{id}', [MenuCardController::class, 'update_menu_card']);
    Route::delete('/menu_card/{id}', [MenuCardController::class, 'delete_menu_card']);

    // dishes
    Route::post('/dish', [MenuCardController::class, 'register_dish']);
    Route::get('/dish', [MenuCardController::class, 'all_dishes']);
    Route::post('/update_dish/{id}', [MenuCardController::class, 'update_dish']);
    Route::delete('/dish/{id}', [MenuCardController::class, 'delete_dish']);

    // dish_items
    Route::post('/dish_items', [MenuCardController::class, 'register_dish_items']);
    Route::get('/dish_items', [MenuCardController::class, 'all_dish_items']);
    Route::post('/update_dish_items/{id}', [MenuCardController::class, 'update_dish_item']);
    Route::delete('/dish_items/{id}', [MenuCardController::class, 'delete_dish_item']);

    // feedback
    Route::post('/feedback', [FeedbackController::class, 'register_feedback']);
    Route::get('/feedback', [FeedbackController::class, 'view_feedbacks']);
    Route::post('/update_feedback/{id}', [FeedbackController::class, 'update_feedback']);
    Route::delete('/feedback/{id}', [FeedbackController::class, 'delete_feedback']);

    // feedback_responses
    Route::post('/feedback_responses', [FeedbackController::class, 'register_feedback_response']);
    Route::get('/feedback_responses', [FeedbackController::class, 'view_feedback_responses']);
    Route::post('/update_feedback_responses/{id}', [FeedbackController::class, 'update_feedback_response']);
    Route::delete('/feedback_responses/{id}', [FeedbackController::class, 'delete_feedback_response']);

    // jamiat
    Route::get('/jamiat', [JamiatController::class, 'view_jamiats']);
    Route::post('/update_jamiat/{id}', [JamiatController::class, 'update_jamiat']);
    Route::delete('/jamiat/{id}', [JamiatController::class, 'delete_jamiat']);

    // jamiat_settings
    Route::post('/jamiat_settings', [JamiatController::class, 'register_jamiat_settings']);
    Route::get('/jamiat_settings', [JamiatController::class, 'view_jamiat_settings']);
    Route::post('/update_jamiat_settings/{id}', [JamiatController::class, 'update_jamiat_settings']);
    Route::delete('/jamiat_settings/{id}', [JamiatController::class, 'delete_jamiat_settings']);

    // super_admin_receipt
    Route::post('/super_admin_receipt', [JamiatController::class, 'register_super_admin_receipts']);
    Route::get('/super_admin_receipt', [JamiatController::class, 'view_super_admin_receipts']);
    Route::post('/update_super_admin_receipt/{id}', [JamiatController::class, 'update_super_admin_receipt']);
    Route::delete('/super_admin_receipt/{id}', [JamiatController::class, 'delete_super_admin_receipt']);

    // super_admin_counter
    Route::post('/super_admin_counter', [JamiatController::class, 'register_super_admin_counter']);
    Route::get('/super_admin_counter', [JamiatController::class, 'view_super_admin_counters']);
    Route::post('/update_super_admin_counter/{id}', [JamiatController::class, 'update_super_admin_counter']);
    Route::delete('/super_admin_counter/{id}', [JamiatController::class, 'delete_super_admin_counter']);

    Route::get('/import_its', [CSVImportController::class, 'importIts']);  
    Route::get('/import_receipts',[CSVImportController::class,'importDataFromUrl']);
    Route::get('/import_sectors', [SectorImportController::class, 'importSectorData']);  
    Route::get('/import_sub_sectors', [SubSectorImportController::class, 'importSubSectorData']);  
});
// Route::get('/import_users', [CSVImportController::class, 'importUser']);

// Route::get('/import_users', [CSVImportController::class, 'importUser']);
