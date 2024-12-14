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

// Public Routes
Route::post('/register', [MumeneenController::class, 'register_users']);
Route::post('/get_otp', [AuthController::class, 'generate_otp']);
Route::post('/login/{id?}', [AuthController::class, 'login']);
Route::get('/sector', [MumeneenController::class, 'all_sector'])
    ->middleware(['auth:sanctum',
        'check-api-permission:sector.create,sector.edit,sector.view,sector.view_global,sector.delete,sector.export,sector.print,sub_sector.create,sub_sector.edit,sub_sector.view,sub_sector.view_global,sub_sector.delete,sub_sector.export,sub_sector.print'
    ]);

// Sync Routes
Route::prefix('sync')->group(function () {
    Route::get('/its-mismatches', [SyncController::class, 'findItsMismatches']);
    Route::get('/its-mumeneen-type-mismatches', [SyncController::class, 'findItsAndMumeneenTypeMismatches']);
    Route::get('/its-mobile-mismatches', [SyncController::class, 'findMobileMismatches']);
    Route::get('/all', [SyncController::class, 'syncData']);
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

// Middleware-protected routes
Route::middleware(['auth:sanctum'])->group(function () {
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
});