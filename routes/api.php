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
use App\Http\Controllers\UploadController;
use App\Http\Controllers\DashboardController;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserControllerWithRoleController;
use App\Http\Controllers\UserControllerWithoutRoleController;

use App\Http\Controllers\TestMiddlewareController;

Route::get('/test-middleware', [TestMiddlewareController::class, 'test']);

use App\Http\Controllers\OrdersController;

Route::post('/orders', [OrdersController::class, 'createOrder']);

use App\Http\Controllers\PaymentController;

Route::post('/payment/verify', [PaymentController::class, 'verifyPayment']);
Route::post('/webhook/payment', [PaymentController::class, 'handleWebhook']);

use App\Http\Controllers\PDFController;

Route::get('/generate-pdf', [PDFController::class, 'generatePDF']);
Route::get('/receipt_print/{id}', [PDFController::class, 'printReceipt']);


Route::post('/register', [MumeneenController::class, 'register_users']);
Route::post('/get_otp', [AuthController::class, 'generate_otp']);
Route::post('/login/{id?}', [AuthController::class, 'login']);

// Register New Jamaat
Route::post('/register_jamaat', [JamiatController::class, 'register_jamaat']);
Route::post('/verify_email', [JamiatController::class, 'verify_email']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/import_users', [CSVImportController::class, 'importUser']);
    Route::post('/migrate_user_csv', [CSVImportController::class, 'migrateFromCsv']);

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


    // Daashboard
    Route::get('/dashboard-stats', [DashboardController::class, 'getDashboardStats']);
    
    
    
    // user
    //Route::get('/user', [MumeneenController::class, 'users']);
   
    
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
    Route::post('/receipts/by_family_ids', [AccountsController::class, 'getReceiptsByFamilyIds']);
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

    // upload
    Route::post('/upload', [UploadController::class, 'upload']);

    Route::post('/get_files', [UploadController::class, 'fetch_uploads']);

    Route::post('/get_photo', [UploadController::class, 'store_photo']);
});
// Route::get('/import_users', [CSVImportController::class, 'importUser']);

// Route::get('/import_users', [CSVImportController::class, 'importUser']);

// permissions
// Mumeneen Routes
Route::middleware(['auth:sanctum', 'check-api-permission:mumeneen.create'])->group(function () {
    Route::post('/register', [MumeneenController::class, 'register_users']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:mumeneen.edit'])->group(function () {
    Route::post('/update_user/{id}', [MumeneenController::class, 'update_record']);
});

Route::middleware(['auth:sanctum', 'check-api-permission:mumeneen.view'])->group(function () {
    Route::get('/get_all_user/{year?}', [MumeneenController::class, 'usersWithHubData']);
    Route::get('/user_details/{id}', [MumeneenController::class, 'get_user']);
    Route::get('/user', [MumeneenController::class, 'users']);
    Route::get('/family_hub_details/{family_id}',[MumeneenController::class,'familyHubDetails']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:mumeneen.view_global'])->group(function () {
    Route::get('/get_all_user/{year?}', [MumeneenController::class, 'usersWithHubData']);
    Route::get('/user_details/{id}', [MumeneenController::class, 'get_user']);
    Route::get('/user', [MumeneenController::class, 'users']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:mumeneen.delete'])->group(function () {
    Route::delete('/user/{id}', [MumeneenController::class, 'delete_user']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:mumeneen.export'])->group(function () {
    Route::get('/get_all_user/{year?}', [MumeneenController::class, 'usersWithHubData']);
    Route::get('/user_details/{id}', [MumeneenController::class, 'get_user']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:mumeneen.print'])->group(function () {
    Route::get('/get_all_user/{year?}', [MumeneenController::class, 'usersWithHubData']);
    Route::get('/user_details/{id}', [MumeneenController::class, 'get_user']);
});

// Hub Routes
Route::middleware(['auth:sanctum', 'check-api-permission:hub.create'])->group(function () {
    Route::post('/hub', [MumeneenController::class, 'register_hub']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:hub.edit'])->group(function () {
    Route::post('/update_hub/{id}', [MumeneenController::class, 'update_hub']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:hub.view'])->group(function () {
    Route::get('/hub', [MumeneenController::class, 'all_hub']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:hub.view_global'])->group(function () {
    Route::get('/hub', [MumeneenController::class, 'all_hub']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:hub.delete'])->group(function () {
    Route::delete('/hub/{id}', [MumeneenController::class, 'delete_hub']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:hub.export'])->group(function () {
    Route::get('/hub', [MumeneenController::class, 'all_hub']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:hub.print'])->group(function () {
    Route::get('/hub', [MumeneenController::class, 'all_hub']);
});

// Receipts Routes
Route::middleware(['auth:sanctum', 'check-api-permission:receipts.create'])->group(function () {
    Route::post('/receipts', [AccountsController::class, 'register_receipts']);
});

Route::middleware(['auth:sanctum', 'check-api-permission:receipts.view'])->group(function () {
    Route::get('/receipts', [AccountsController::class, 'all_receipts']);
});

Route::middleware(['auth:sanctum', 'check-api-permission:receipts.edit'])->group(function () {
    Route::post('/update_receipts/{id}', [AccountsController::class, 'update_receipts']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:receipts.view_global'])->group(function () {
    Route::get('/receipts', [AccountsController::class, 'all_receipts']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:receipts.delete'])->group(function () {
    Route::delete('/receipts/{id}', [AccountsController::class, 'delete_receipts']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:receipts.export'])->group(function () {
    Route::get('/receipts', [AccountsController::class, 'all_receipts']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:receipts.print'])->group(function () {
    Route::get('/receipts', [AccountsController::class, 'all_receipts']);
});

// Payments Routes
Route::middleware(['auth:sanctum', 'check-api-permission:payments.create'])->group(function () {
    Route::post('/payments', [AccountsController::class, 'register_payments']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:payments.edit'])->group(function () {
    Route::post('/update_payments/{id}', [AccountsController::class, 'update_payments']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:payments.view'])->group(function () {
    Route::get('/payments', [AccountsController::class, 'all_payments']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:payments.view_global'])->group(function () {
    Route::get('/payments', [AccountsController::class, 'all_payments']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:payments.delete'])->group(function () {
    Route::delete('/payments/{id}', [AccountsController::class, 'delete_payments']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:payments.export'])->group(function () {
    Route::get('/payments', [AccountsController::class, 'all_payments']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:payments.print'])->group(function () {
    Route::get('/payments', [AccountsController::class, 'all_payments']);
});

// Menu Routes
Route::middleware(['auth:sanctum', 'check-api-permission:menu.create'])->group(function () {
    Route::post('/menu', [MumeneenController::class, 'register_menu']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:menu.edit'])->group(function () {
    Route::post('/update_menu/{id}', [MumeneenController::class, 'update_menu']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:menu.view'])->group(function () {
    Route::get('/menu', [MumeneenController::class, 'all_menu']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:menu.view_global'])->group(function () {
    Route::get('/menu', [MumeneenController::class, 'all_menu']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:menu.delete'])->group(function () {
    Route::delete('/menu/{id}', [MumeneenController::class, 'delete_menu']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:menu.export'])->group(function () {
    Route::get('/menu', [MumeneenController::class, 'all_menu']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:menu.print'])->group(function () {
    Route::get('/menu', [MumeneenController::class, 'all_menu']);
});

// Sector Routes
Route::middleware(['auth:sanctum', 'check-api-permission:sector.create'])->group(function () {
    Route::post('/sector', [MumeneenController::class, 'register_sector']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:sector.view'])->group(function () {
    Route::get('/sector', [MumeneenController::class, 'all_sector']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:sector.view_global'])->group(function () {
    Route::get('/sector', [MumeneenController::class, 'all_sector']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:sector.edit'])->group(function () {
    Route::post('/update_sector/{id}', [MumeneenController::class, 'update_sector']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:sector.delete'])->group(function () {
    Route::delete('/sector/{id}', [MumeneenController::class, 'delete_sector']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:sector.export'])->group(function () {
    Route::get('/sector', [MumeneenController::class, 'all_sector']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:sector.print'])->group(function () {
    Route::get('/sector', [MumeneenController::class, 'all_sector']);
});

// SubSector Routes
Route::middleware(['auth:sanctum', 'check-api-permission:sub_sector.create'])->group(function () {
    Route::post('/sub_sector', [MumeneenController::class, 'register_sub_sector']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:sub_sector.edit'])->group(function () {
    Route::post('/update_sub_sector/{id}', [MumeneenController::class, 'update_sub_sector']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:sub_sector.view'])->group(function () {
    Route::get('/sub_sector', [MumeneenController::class, 'all_sub_sector']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:sub_sector.view_global'])->group(function () {
    Route::get('/sub_sector', [MumeneenController::class, 'all_sub_sector']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:sub_sector.delete'])->group(function () {
    Route::delete('/sub_sector/{id}', [MumeneenController::class, 'delete_sub_sector']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:sub_sector.export'])->group(function () {
    Route::get('/sub_sector', [MumeneenController::class, 'all_sub_sector']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:sub_sector.print'])->group(function () {
    Route::get('/sub_sector', [MumeneenController::class, 'all_sub_sector']);
});

// Expense Routes
Route::middleware(['auth:sanctum', 'check-api-permission:expense.create'])->group(function () {
    Route::post('/expense', [AccountsController::class, 'register_expense']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:expense.edit'])->group(function () {
    Route::post('/update_expense/{id}', [AccountsController::class, 'update_expense']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:expense.view'])->group(function () {
    Route::get('/expense', [AccountsController::class, 'all_expense']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:expense.view_global'])->group(function () {
    Route::get('/expense', [AccountsController::class, 'all_expense']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:expense.delete'])->group(function () { 
    Route::delete('/expense/{id}', [AccountsController::class, 'delete_expense']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:expense.export'])->group(function () {
    Route::get('/expense', [AccountsController::class, 'all_expense']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:expense.print'])->group(function () {
    Route::get('/expense', [AccountsController::class, 'all_expense']);
});

// Transfer Routes
Route::middleware(['auth:sanctum', 'check-api-permission:transfer.create'])->group(function () {
    Route::post('/transfer/create', [TransferController::class, 'create']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:transfer.edit'])->group(function () {
    Route::put('/transfer/edit/{id}', [TransferController::class, 'edit']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:transfer.view'])->group(function () {
    Route::get('/transfer/view/{id}', [TransferController::class, 'view']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:transfer.view_global'])->group(function () {
    Route::get('/transfer/view_global', [TransferController::class, 'viewGlobal']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:transfer.delete'])->group(function () {
    Route::delete('/transfer/delete/{id}', [TransferController::class, 'delete']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:transfer.export'])->group(function () {
    Route::get('/transfer/export', [TransferController::class, 'export']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:transfer.print'])->group(function () {
    Route::get('/transfer/print/{id}', [TransferController::class, 'print']);
});

// Notifications Routes
Route::middleware(['auth:sanctum', 'check-api-permission:notifications.create'])->group(function () {
    Route::post('/notifications/create', [NotificationsController::class, 'create']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:notifications.edit'])->group(function () {
    Route::put('/notifications/edit/{id}', [NotificationsController::class, 'edit']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:notifications.view'])->group(function () {
    Route::get('/notifications/view/{id}', [NotificationsController::class, 'view']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:notifications.view_global'])->group(function () {
    Route::get('/notifications/view_global', [NotificationsController::class, 'viewGlobal']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:notifications.delete'])->group(function () {
    Route::delete('/notifications/delete/{id}', [NotificationsController::class, 'delete']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:notifications.export'])->group(function () {
    Route::get('/notifications/export', [NotificationsController::class, 'export']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:notifications.print'])->group(function () {
    Route::get('/notifications/print/{id}', [NotificationsController::class, 'print']);
});

// Dashboard Widgets Routes
Route::middleware(['auth:sanctum', 'check-api-permission:dashboard_widgets.create'])->group(function () {
    Route::post('/dashboard_widgets/create', [DashboardWidgetsController::class, 'create']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:dashboard_widgets.edit'])->group(function () {
    Route::put('/dashboard_widgets/edit/{id}', [DashboardWidgetsController::class, 'edit']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:dashboard_widgets.view'])->group(function () {
    Route::get('/dashboard-stats', [DashboardController::class, 'getDashboardStats']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:dashboard_widgets.view_global'])->group(function () {
    Route::get('/dashboard-stats', [DashboardController::class, 'getDashboardStats']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:dashboard_widgets.delete'])->group(function () {
    Route::delete('/dashboard_widgets/delete/{id}', [DashboardWidgetsController::class, 'delete']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:dashboard_widgets.export'])->group(function () {
    Route::get('/dashboard_widgets/export', [DashboardWidgetsController::class, 'export']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:dashboard_widgets.print'])->group(function () {
    Route::get('/dashboard_widgets/print/{id}', [DashboardWidgetsController::class, 'print']);
});

// Feedback Routes
Route::middleware(['auth:sanctum', 'check-api-permission:feedback.create'])->group(function () {
    Route::post('/feedback', [FeedbackController::class, 'register_feedback']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:feedback.edit'])->group(function () {
    Route::post('/update_feedback/{id}', [FeedbackController::class, 'update_feedback']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:feedback.view'])->group(function () {
    Route::get('/feedback', [FeedbackController::class, 'view_feedbacks']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:feedback.view_global'])->group(function () {
    Route::get('/feedback', [FeedbackController::class, 'view_feedbacks']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:feedback.delete'])->group(function () {
    Route::delete('/feedback/{id}', [FeedbackController::class, 'delete_feedback']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:feedback.export'])->group(function () {
    Route::get('/feedback', [FeedbackController::class, 'view_feedbacks']);
});
Route::middleware(['auth:sanctum', 'check-api-permission:feedback.print'])->group(function () {
    Route::get('/feedback', [FeedbackController::class, 'view_feedbacks']);
});
