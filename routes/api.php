<?php

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
use App\Http\Controllers\UserController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\WhatsAppQueueController;
use App\Http\Controllers\NiyazController;

// Public Routes
Route::post('/register', [MumeneenController::class, 'register_users']);
Route::post('/get_otp', [AuthController::class, 'generate_otp']);
Route::post('/login/{id?}', [AuthController::class, 'login']);
Route::get('/sector', [MumeneenController::class, 'all_sector'])
    ->middleware(['auth:sanctum',
        'check-api-permission:sector.create,sector.edit,sector.view,sector.view_global,sector.delete,sector.export,sector.print,sub_sector.create,sub_sector.edit,sub_sector.view,sub_sector.view_global,sub_sector.delete,sub_sector.export,sub_sector.print'
    ]);

// Sync Routes


// Scenario 1: Detect Missing HOFs in users
Route::get('/sync/detect-missing-hof', [SyncController::class, 'detectMissingHofInUsers']);

// Scenario 2: Confirm and add missing Family Members (FM) from t_its_data
Route::post('/sync/confirm-fm-from-its-data', [SyncController::class, 'confirmFmFromItsData']);

// Scenario 3: Detect Invalid HOFs in users
Route::get('/sync/detect-invalid-hof', [SyncController::class, 'detectInvalidHofInUsers']);

// Scenario 4: Remove Family Members (FM) in users not present in t_its_data
Route::post('/sync/remove-fm-not-in-its-data', [SyncController::class, 'removeFmNotInItsData']);

// Scenario 5: Detect role mismatches - HOF marked as FM in users
Route::get('/sync/detect-hof-marked-as-fm', [SyncController::class, 'detectHofMarkedAsFmInUsers']);

// Scenario 5: Confirm role update - Mark FM in users as HOF
Route::post('/sync/confirm-hof-role-update', [SyncController::class, 'confirmHofRoleUpdate']);

// Scenario 6: Detect role mismatches - HOF marked as HOF in users but FM in t_its_data
Route::get('/sync/detect-hof-as-fm-in-its-data', [SyncController::class, 'detectHofMarkedAsFmInItsData']);

// Scenario 6: Confirm role update - Mark HOF in users as FM
Route::post('/sync/confirm-fm-role-update', [SyncController::class, 'confirmFmRoleUpdate']);

// Consolidated Sync: Run all scenarios sequentially
Route::get('/sync/consolidated', [SyncController::class, 'consolidatedSync']);

// Payment Routes
Route::post('/payment/verify', [PaymentController::class, 'verifyPayment']);
Route::post('/webhook/payment', [PaymentController::class, 'handleWebhook']);

// PDF Generation
Route::get('/generate-pdf', [PDFController::class, 'generatePDF']);
Route::get('/receipt_print/{id}', [PDFController::class, 'printReceipt']);

// WhatsApp Queue
Route::post('/whatsapp-queue', [WhatsAppQueueController::class, 'addToQueue']);
Route::post('/whatsapp-queue/process', [WhatsAppQueueController::class, 'processQueue']);

// Register New Jamaat
Route::post('/register-jamaat', [JamiatController::class, 'register_jamaat']);
Route::post('/forgot_password', [JamiatController::class, 'forgot_password']);
Route::post('/verify_email', [JamiatController::class, 'verify_email']);

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

    //Upload ITS_DATA
    Route::post('/its_upload', [CSVImportController::class, 'uploadExcel']);
   

    Route::delete('/its-data/{jamiatId}', [CSVImportController::class, 'deleteByJamiatId']);

    Route::post('/logout', [AuthController::class, 'logout']);

   

    // Dashboard
    Route::prefix('dashboard')->group(function () {
       
        
        Route::get('/cash-summary', [DashboardController::class, 'getCashSummary'])
            ->middleware('check-api-permission:dashboard_widgets.view,dashboard_widgets.view_global,dashboard_widgets.delete,dashboard_widgets.export,dashboard_widgets.print');
    });
    Route::get('dashboard-stats', [DashboardController::class, 'getDashboardStats'])
    ->middleware('check-api-permission:dashboard_widgets.view,dashboard_widgets.view_global,dashboard_widgets.delete,dashboard_widgets.export,dashboard_widgets.print');

    // Upload
    Route::prefix('upload')->group(function () {
        Route::post('/', [UploadController::class, 'upload']);
        Route::post('/get_files', [UploadController::class, 'fetch_uploads']);
        Route::post('/get_photo', [UploadController::class, 'store_photo']);
    });


    Route::post('/sector_registor', [MumeneenController::class, 'register_sector']);
    
    //Year
   
    Route::post('/create-year', [MumeneenController::class, 'createYearAndHubEntries']);
    Route::get('/year', [MumeneenController::class, 'all_years']);
    Route::post('/update_year/{id}', [MumeneenController::class, 'update_year']);
    Route::delete('/year/{id}', [MumeneenController::class, 'delete_year']);

   // getDistinctFamilyCountUnderAge14
    Route::get('/child', [MumeneenController::class, 'getDistinctFamilyCountUnderAge14']);
    Route::get('/users/below-age-15', [MumeneenController::class, 'getUsersBelowAge15WithHofDetails']);

    
    // User Management
    Route::get('/get_all_user/{year?}', [MumeneenController::class, 'usersWithHubData'])
    ->middleware(['check-api-permission:mumeneen.view,mumeneen.view_global']);
    Route::get('/user_details/{id}', [MumeneenController::class, 'get_user']) ->middleware(['check-api-permission:mumeneen.view,mumeneen.view_global']);

    Route::prefix('users')->group(function () {
       
        Route::post('/update/{id}', [MumeneenController::class, 'update_record'])
            ->middleware(['check-api-permission:mumeneen.edit']);
        Route::delete('/{id}', [MumeneenController::class, 'delete_user'])
            ->middleware(['check-api-permission:mumeneen.delete']);
        Route::get('/details/{id}', [MumeneenController::class, 'get_user']);
        Route::post('/assign-permissions', [PermissionRoleController::class, 'assignPermissionsToUser']);
        Route::get('/{userId}/permissions', [PermissionRoleController::class, 'getUserPermissions']);
        Route::get('/with-permissions', [PermissionRoleController::class, 'getUsersWithPermissions']);
           });

           Route::post('/users/remove-permissions', [PermissionRoleController::class, 'removePermissionsFromUser']);

    // Permissions and Roles
    Route::prefix('permissions')->group(function () {
        Route::post('/create', [PermissionRoleController::class, 'createPermission']);
        Route::post('/create-bulk', [PermissionRoleController::class, 'createBulkPermissions']);
        Route::get('/all', [PermissionRoleController::class, 'getAllPermissions']);
        Route::delete('/delete', [PermissionRoleController::class, 'deletePermission']);
    });

    Route::prefix('roles')->group(function () {
        Route::post('/create', [PermissionRoleController::class, 'createRole']);
        Route::post('/add-permissions', [PermissionRoleController::class, 'addPermissionsToRole']);
        Route::get('/all', [PermissionRoleController::class, 'getAllRoles']);
        Route::get('/{roleName}/permissions', [PermissionRoleController::class, 'getRolePermissions']);
        Route::post('/create-with-permissions', [PermissionRoleController::class, 'createRoleWithPermissions']);
    });
   

    Route::prefix('sub_sector')->middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [MumeneenController::class, 'register_sub_sector'])
            ->middleware('check-api-permission:sub_sector.create');
        Route::post('/update/{id}', [MumeneenController::class, 'update_sub_sector'])
            ->middleware('check-api-permission:sub_sector.edit');
        Route::get('/', [MumeneenController::class, 'all_sub_sector'])
            ->middleware('check-api-permission:sub_sector.view,sub_sector.view_global,sub_sector.export,sub_sector.print');
        Route::delete('/{id}', [MumeneenController::class, 'delete_sub_sector'])
            ->middleware('check-api-permission:sub_sector.delete');
    });

    // Hubs->middleware(['check-api-permission:mumeneen.edit,mumeneen.view']);
    Route::prefix('hub')->group(function () {
        Route::get('/', [MumeneenController::class, 'all_hub'])
            ->middleware(['check-api-permission:hub.view,hub.view_global,hub.export,hub.print']);
        Route::post('/', [MumeneenController::class, 'register_hub'])
            ->middleware(['check-api-permission:hub.create']);
        Route::post('/update/{id}', [MumeneenController::class, 'update_hub'])
            ->middleware(['check-api-permission:hub.edit']);
        Route::delete('/{id}', [MumeneenController::class, 'delete_hub'])
            ->middleware(['check-api-permission:hub.delete']);
    });
    Route::get('/family_hub_details/{family_id}',[MumeneenController::class,'familyHubDetails']) ->middleware(['check-api-permission:mumeneen.edit,mumeneen.view']);
    Route::post('/get_family_user', [MumeneenController::class, 'usersByFamily'])->middleware(['check-api-permission:mumeneen.edit,mumeneen.view']);


    // Receipts
    Route::prefix('receipts')->group(function () {
        Route::get('/', [AccountsController::class, 'all_receipts'])
            ->middleware(['check-api-permission:receipts.view,receipts.view_global,receipts.export,receipts.print']);
        Route::post('/', [AccountsController::class, 'register_receipts'])
            ->middleware(['check-api-permission:receipts.create']);
        Route::post('/update/{id}', [AccountsController::class, 'update_receipts'])
            ->middleware(['check-api-permission:receipts.edit']);
        Route::post('/by_family_ids', [AccountsController::class, 'getReceiptsByFamilyIds'])
        ->middleware(['check-api-permission:receipts.view,receipts.view_global,receipts.export,receipts.print']);
        Route::delete('/{id}', [AccountsController::class, 'delete_receipts'])
            ->middleware(['check-api-permission:receipts.delete']);
    });
    

    // Payments
    Route::prefix('payments')->group(function () {
        Route::get('/', [AccountsController::class, 'all_payments'])
            ->middleware(['check-api-permission:payments.view,payments.view_global,payments.export,payments.print']);
        Route::post('/', [AccountsController::class, 'register_payments'])
            ->middleware(['check-api-permission:payments.create']);
        Route::post('/update/{id}', [AccountsController::class, 'update_payments'])
            ->middleware(['check-api-permission:payments.edit']);
        Route::delete('/{id}', [AccountsController::class, 'delete_payments'])
            ->middleware(['check-api-permission:payments.delete']);
    });

    // Feedback
    Route::prefix('feedback')->group(function () {
        Route::get('/', [FeedbackController::class, 'view_feedbacks'])
            ->middleware(['check-api-permission:feedback.view,feedback.view_global,feedback.export,feedback.print']);
        Route::post('/', [FeedbackController::class, 'register_feedback'])
            ->middleware(['check-api-permission:feedback.create']);
        Route::post('/update/{id}', [FeedbackController::class, 'update_feedback'])
            ->middleware(['check-api-permission:feedback.edit']);
        Route::delete('/{id}', [FeedbackController::class, 'delete_feedback'])
            ->middleware(['check-api-permission:feedback.delete']);
    });

    //Niyaz
    Route::get('/hub-slabs', [NiyazController::class, 'getHubSlabs']);
    Route::get('/users-by-slab/{hubSlabId}', [NiyazController::class, 'getUsersBySlabId']);



});