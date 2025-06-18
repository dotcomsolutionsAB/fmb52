<?php

use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ReceiptsController;
use App\Http\Controllers\PaymentsController;
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
use App\Http\Controllers\ExportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\WhatsAppQueueController;
use App\Http\Controllers\NiyazController;
use App\Http\Controllers\HubController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\FeedbacksController;

Route::post('/migrate_user_csv', [CSVImportController::class, 'migrateFromApi']);
Route::get('/import_receipts',[CSVImportController::class,'importDataFromUrl']);
Route::post('/import_users', [CSVImportController::class, 'importUser']);
Route::get('/import_its', [CSVImportController::class, 'importIts']); 
// Public Routes
Route::post('/register', [MumeneenController::class, 'register_users']);
Route::post('/get_otp', [AuthController::class, 'generate_otp']);
Route::post('/login/{id?}', [AuthController::class, 'login']);

// Register New Jamaat
Route::post('/register-jamaat', [JamiatController::class, 'register_jamaat']);
Route::post('/forgot_password', [JamiatController::class, 'forgot_password']);
Route::post('/verify_email', [JamiatController::class, 'verify_email']);

// Sync Routes - Manage Family Members & HOF Integrity
Route::prefix('sync')->group(function () {
    
    // Scenario 0: Sync all family members
    Route::get('/sync-family-members', [SyncController::class, 'syncFamilyMembers']);

    // Scenario 1: Detect & Add Missing HOFs
    Route::get('/detect-missing-hof', [SyncController::class, 'detectMissingHofInUsers']);
    Route::post('/add-missing-hof', [SyncController::class, 'addMissingHofInUsers']);

    // Scenario 2: Confirm and add missing FMs from ITS data
    Route::get('/confirm-fm-from-its-data', [SyncController::class, 'confirmFmFromItsData']);

    // Scenario 3: Detect invalid HOFs in users
    Route::get('/detect-invalid-hof', [SyncController::class, 'detectInvalidHofInUsers']);

    // Scenario 4: Remove FMs in users not present in ITS data
    Route::post('/remove-fm-not-in-its-data', [SyncController::class, 'removeFmNotInItsData']);

    // Scenario 5: Resolve role mismatches - HOF marked as FM
    Route::get('/detect-hof-marked-as-fm', [SyncController::class, 'detectHofMarkedAsFmInUsers']);
    Route::post('/confirm-hof-role-update', [SyncController::class, 'confirmHofRoleUpdate']);

    // Scenario 6: Resolve role mismatches - HOF in users but FM in ITS data
    Route::get('/detect-hof-as-fm-in-its-data', [SyncController::class, 'detectHofMarkedAsFmInItsData']);
    Route::post('/confirm-fm-role-update', [SyncController::class, 'confirmFmRoleUpdate']);
    Route::get('/update_hof', [SyncController::class, 'updateHofData']);
    

    // Scenario 7: Consolidated Sync - Run all scenarios
    Route::get('/all', [SyncController::class, 'consolidatedSync']);
});

// Payment Routes
Route::post('/payment/verify', [PaymentController::class, 'verifyPayment']);
Route::post('/webhook/payment', [PaymentController::class, 'handleWebhook']);

// PDF Generation
Route::get('/generate-pdf', [PDFController::class, 'generatePDF']);
Route::get('/receipt_print/{id}', [PDFController::class, 'printReceipt']);

// WhatsApp Queue
Route::post('/whatsapp-queue', [WhatsAppQueueController::class, 'addToQueue']);
Route::post('/whatsapp-queue/process', [WhatsAppQueueController::class, 'processQueue']);

Route::get('banks',[AccountsController::class,'allBanks']);

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

Route::get('/currencies', [AccountsController::class, 'fetchCurrencies']);

// Middleware-protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Dashboard Routes
    Route::prefix('dashboard')->middleware('check-api-permission:dashboard_widgets.FullAccess')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getDashboardStats']);
        Route::get('/cash-summary', [DashboardController::class, 'getCashSummary']);
        Route::post('/hub_distribution', [HubController::class, 'hub_distribution']);
        Route::post('/niyaz_stats', [HubController::class, 'niyaz_stats']);
        Route::post('/mohalla_wise', [HubController::class, 'mohalla_wise']);
        Route::post('/users_by_niyaz', [HubController::class, 'usersByNiyazSlab']);
        Route::post('/users_by_sector', [HubController::class, 'usersBySector']);
    });

    // Mumeneen Routes
    Route::prefix('mumeneen')->middleware('check-api-permission:mumeneen.ViewOnly')->group(function () {
        Route::get('/{year?}', [MumeneenController::class, 'usersWithHubData']);
        Route::post('/export/{year?}', [ExportController::class, 'exportUsersWithHubData']);
        Route::get('/user/{id}', [MumeneenController::class, 'get_user']);
        Route::get('/name/{its}', [MumeneenController::class, 'getUserNameByIts']);
        Route::post('/update_details/{id}', [MumeneenController::class, 'update_user_details']);
        Route::post('/thaali_statuses', [MumeneenController::class, 'thaali']);
        Route::post('/transfer_out', [MumeneenController::class, 'transferOut']);

        Route::post('/family_members', [MumeneenController::class, 'usersByFamily'])->middleware('check-api-permission:mumeneen.ViewOnly');
        Route::get('/hub_details/{family_id}', [MumeneenController::class, 'familyHubDetails'])->middleware('check-api-permission:mumeneen.ViewOnly');
    });

    Route::post('/mumeneen_switch_hof', [MumeneenController::class, 'updateHeadOfFamily']);


    // Hub Routes
    Route::prefix('hub')->middleware('check-api-permission:hub.ViewOnly')->group(function () {
        Route::get('/', [MumeneenController::class, 'all_hub']);
        Route::post('/', [MumeneenController::class, 'register_hub'])->middleware('check-api-permission:hub.FullAccess');
        Route::post('/update/{id}', [MumeneenController::class, 'update_hub'])->middleware('check-api-permission:hub.FullAccess');
        Route::get('/recalculate/{id}', [HubController::class, 'updateFamilyHub']);
        
        Route::delete('/{id}', [MumeneenController::class, 'delete_hub'])->middleware('check-api-permission:hub.FullAccess');
    });

    // Receipts Routes
    Route::prefix('receipts')->group(function () {
        Route::middleware('check-api-permission:receipts.ViewOnly')->group(function () {
        Route::post('/all', [ReceiptsController::class, 'all_receipts']);
        Route::get('/{id}',[ReceiptsController::class,'show']);
        Route::post('/export', [ExportController::class, 'exportReceipts']);
        Route::post('/by_family_ids', [ReceiptsController::class, 'getReceiptsByFamilyIds']);
        });
         Route::post('/cancel/{id}', [ReceiptsController::class, 'cancelReceipt']);
        Route::post('/pending', [ReceiptsController::class, 'getPendingCashReceipts']);
        Route::post('/', [ReceiptsController::class, 'register_receipts'])->middleware('check-api-permission:receipts.FullAccess');
        Route::post('/update/{id}', [ReceiptsController::class, 'update_receipts'])->middleware('check-api-permission:receipts.FullAccess');
        Route::delete('/{id}', [ReceiptsController::class, 'delete_receipts'])->middleware('check-api-permission:receipts.FullAccess');
    });

    // Expense Routes
    Route::post('/expense', [ExpenseController::class, 'register_expense']);
    Route::get('/expense', [ExpenseController::class, 'all_expense']);
     Route::get('/expense/export', [ExportController::class, 'exportExpenses']);
    
    Route::post('/expense/update/{id}', [ExpenseController::class, 'update_expense']);
    Route::delete('/expense/{id}', [ExpenseController::class, 'delete_expense']);


    // Payments Routes
    Route::prefix('payments')->group(function () {
        Route::middleware('check-api-permission:payments.ViewOnly')->group(function () {
        Route::post('/all', [PaymentsController::class, 'all_payments']);
        });
        Route::post('/', [PaymentsController::class, 'register_payments'])->middleware('check-api-permission:payments.FullAccess');
        Route::post('/update', [PaymentsController::class, 'changePaymentStatus'])->middleware('check-api-permission:payments.FullAccess');
        Route::delete('/{id}', [PaymentsController::class, 'delete_payments'])->middleware('check-api-permission:payments.FullAccess');
    });

    // Thali Menu Routes
        

    // Feedback Routes
    Route::prefix('feedback')->group(function () {
        Route::middleware('check-api-permission:feedback.ViewOnly')->group(function () {
            Route::get('/', [FeedbackController::class, 'view_feedbacks']);
        });
        Route::post('/', [FeedbackController::class, 'register_feedback'])->middleware('check-api-permission:feedback.FullAccess');
        Route::post('/update/{id}', [FeedbackController::class, 'update_feedback'])->middleware('check-api-permission:feedback.FullAccess');
        Route::delete('/{id}', [FeedbackController::class, 'delete_feedback'])->middleware('check-api-permission:feedback.FullAccess');
    });

    // Sector Routes
    Route::prefix('sector')->group(function () {
        Route::get('/', [MumeneenController::class, 'all_sector'])->middleware([
            'auth:sanctum',
            'check-api-permission:sector.ViewOnly'
        ]);
        Route::post('/', [MumeneenController::class, 'register_sector']);
    });

    // Sub-Sector Routes
    Route::prefix('sub_sector')->middleware('auth:sanctum')->group(function () {
        Route::middleware('check-api-permission:sub_sector.ViewOnly')->group(function () {
            Route::get('/', [MumeneenController::class, 'all_sub_sector']);
        });
        Route::post('/', [MumeneenController::class, 'register_sub_sector'])->middleware('check-api-permission:sub_sector.FullAccess');
        Route::post('/update/{id}', [MumeneenController::class, 'update_sub_sector'])->middleware('check-api-permission:sub_sector.FullAccess');
        Route::delete('/{id}', [MumeneenController::class, 'delete_sub_sector'])->middleware('check-api-permission:sub_sector.FullAccess');
    });

    // Niyaz Routes
    Route::prefix('niyaz')->group(function () {
        Route::post('/', [NiyazController::class, 'addNiyaz']);
        Route::get('/', [NiyazController::class, 'show']);
        Route::get('/{niyaz_id}', [NiyazController::class, 'getNiyazDetailsById']);
        Route::post('/update/{niyaz_id}', [NiyazController::class, 'editNiyaz']);
        Route::delete('/niyaz/{niyaz_id}', [NiyazController::class, 'destroy']);
        Route::get('/hub-slabs', [NiyazController::class, 'getHubSlabs']);
        Route::get('/users-by-slab/{hubSlabId}', [NiyazController::class, 'getUsersBySlabId']);
    });

    // Year Routes
    Route::prefix('year')->group(function () {
        Route::post('/', [MumeneenController::class, 'createYearAndHubEntries']);
        Route::get('/', [MumeneenController::class, 'all_years']);
        Route::post('/update/{id}', [MumeneenController::class, 'update_year']);
        Route::delete('/{id}', [MumeneenController::class, 'delete_year']);
    });

    // Users Routes
    Route::prefix('users')->group(function () {
        Route::post('/update/{id}', [MumeneenController::class, 'update_record'])->middleware('check-api-permission:mumeneen.FullAccess');
        Route::delete('/{id}', [MumeneenController::class, 'delete_user'])->middleware('check-api-permission:mumeneen.FullAccess');
        Route::get('/fetch/{id}', [MumeneenController::class, 'get_user']);

        // Permission management
        Route::post('/assign-permissions', [PermissionRoleController::class, 'assignPermissionsToUser']);
        Route::get('/permissions/{user_id}', [PermissionRoleController::class, 'getUserPermissions']);
        Route::get('/with-permissions/{id?}', [PermissionRoleController::class, 'getUsersWithPermissions']);
    });




    // Upload
    Route::prefix('upload')->group(function () {
        Route::post('/', [UploadController::class, 'upload']);
        Route::post('/get_files', [UploadController::class, 'fetch_uploads']);
        Route::post('/get_photo', [UploadController::class, 'store_photo']);
    });
    
    

    // getDistinctFamilyCountUnderAge14
    Route::get('/child', [MumeneenController::class, 'getDistinctFamilyCountUnderAge14']);
    Route::get('/users/below-age-15', [MumeneenController::class, 'getUsersBelowAge15WithHofDetails']);
    Route::post('/users/remove-permissions', [PermissionRoleController::class, 'removePermissionsFromUser']);

    // Permissions and Roles
    Route::prefix('permissions')->group(function () {
        Route::post('/create', [PermissionRoleController::class, 'createPermission']);
        Route::post('/create-bulk', [PermissionRoleController::class, 'createBulkPermissions']);
        Route::get('/all', [PermissionRoleController::class, 'getAllPermissions']);
        Route::get('/by_role/{id}', [PermissionRoleController::class, 'getPermissionsByRole']);
        
        Route::delete('/delete', [PermissionRoleController::class, 'deletePermission']);
    });

    Route::prefix('roles')->group(function () {
        Route::post('/create', [PermissionRoleController::class, 'createRole']);
        Route::post('/add-permissions', [PermissionRoleController::class, 'addPermissionsToRole']);
        Route::get('/all', [PermissionRoleController::class, 'getAllRoles']);
        Route::get('/permissions/{rolename}', [PermissionRoleController::class, 'getRolePermissions']);
        Route::post('/create-with-permissions', [PermissionRoleController::class, 'createRoleWithPermissions']);
    });

    
    //Upload ITS_DATA
    Route::post('/user_migrate', [MumeneenController::class, 'migrate']);

    Route::post('/its_upload', [CSVImportController::class, 'uploadExcel']);
    Route::delete('/its-data/{jamiatId}', [CSVImportController::class, 'deleteByJamiatId']);
     Route::post('/expense_migrate', [CSVImportController::class, 'importExpensesFromCSV']);
     Route::post('/transfer_migrate', [CSVImportController::class, 'importTransfersFromCSV']);
    
    Route::post('/logout', [AuthController::class, 'logout']);

    // Feedback
    Route::prefix('feedbacks')->group(function () {
    Route::post('add', [FeedbacksController::class, 'add']); // Add feedback
    Route::put('edit/{id}', [FeedbacksController::class, 'edit']); // Edit feedback
    Route::get('view/{id}', [FeedbacksController::class, 'view']); // View a specific feedback
    Route::get('view-all', [FeedbacksController::class, 'viewAll']); // View all feedbacks
     Route::get('report', [FeedbacksController::class, 'dailyMenuReport']); // View all feedbacks
      Route::post('menu_wise', [FeedbacksController::class, 'getMenuFeedback']);
     
    
    });

    //Niyaz
    Route::get('/hub-slabs', [NiyazController::class, 'getHubSlabs']);
    Route::get('/users-by-slab/{hubSlabId}', [NiyazController::class, 'getUsersBySlabId']);
    Route::post('/niyaz/add', [NiyazController::class, 'addNiyaz']);
    Route::get('/view-all', [NiyazController::class, 'show']);
    Route::post('/niyaz/edit/{niyaz_id}', [NiyazController::class, 'editNiyaz']);
    Route::get('/niyaz/{niyaz_id}', [NiyazController::class, 'getNiyazDetailsById']);
    Route::post('/niyaz/delete/{niyaz_id}', [NiyazController::class, 'destroy']);

    Route::prefix('menus')->group(function () {
        Route::get('/', [MenuController::class, 'all_menu']);
        Route::post('/', [MenuController::class, 'register_menu']);
        Route::post('/by-date', [MenuController::class, 'getMenuByDate']); // Get menu by a specific date
        Route::post('/by-week', [MenuController::class, 'getMenuForWeek']); // Get menu for the week
        Route::post('/update/{id}', [MenuController::class, 'update_menu']); // Update
    Route::delete('/{id}', [MenuController::class, 'delete_menu']); // 
    });

});
