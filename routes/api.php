<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ClientImportExportController;
use App\Http\Controllers\Api\V1\ClientNoteController;
use App\Http\Controllers\Api\V1\CallLogController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\ClientTimelineController;
use App\Http\Controllers\Api\V1\ClientDocumentController;
use App\Http\Controllers\Api\V1\SupplierDocumentController;
use App\Http\Controllers\Api\V1\CommercialDashboardController;
use App\Http\Controllers\Api\V1\PersonalDashboardController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\UserRoleController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\EmailNotificationPreferenceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// API Version 1
Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', [ApiController::class, 'health']);

    // Authentication routes (public)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        // Protected auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/user', [AuthController::class, 'user']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // Public routes
    Route::group(['prefix' => 'public'], function () {
        // Add public API routes here
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Legacy user profile (keep for compatibility)
        Route::get('/user', [ApiController::class, 'user']);

        // Clients management
        Route::get('clients/search', [ClientController::class, 'search'])->name('clients.search');
        Route::apiResource('clients', ClientController::class);

        // Suppliers management
        Route::get('suppliers/search', [SupplierController::class, 'search'])->name('suppliers.search');
        Route::apiResource('suppliers', SupplierController::class);

        // Contacts management
        // Global contacts endpoint (all clients and suppliers)
        Route::get('contacts', [ContactController::class, 'indexAll']);

        // List contacts for a specific client
        Route::get('clients/{clientId}/contacts', [ContactController::class, 'index']);
        // Create contact for a specific client
        Route::post('clients/{clientId}/contacts', [ContactController::class, 'store']);

        // List contacts for a specific supplier
        Route::get('suppliers/{supplierId}/contacts', [ContactController::class, 'indexForSupplier']);
        // Create contact for a specific supplier
        Route::post('suppliers/{supplierId}/contacts', [ContactController::class, 'storeForSupplier']);

        // Individual contact operations (not nested under client/supplier)
        Route::get('contacts/{id}', [ContactController::class, 'show']);
        Route::put('contacts/{id}', [ContactController::class, 'update']);
        Route::delete('contacts/{id}', [ContactController::class, 'destroy']);
        Route::put('contacts/{id}/make-primary', [ContactController::class, 'makePrimary']);

        // Categories management
        Route::apiResource('categories', CategoryController::class);

        // Client categories assignment
        Route::get('clients/{clientId}/categories', [CategoryController::class, 'getClientCategories']);
        Route::post('clients/{clientId}/categories', [CategoryController::class, 'assignToClient']);
        Route::delete('clients/{clientId}/categories/{categoryId}', [CategoryController::class, 'removeFromClient']);

        // Client import/export
        Route::post('clients/import/preview', [ClientImportExportController::class, 'preview']);
        Route::post('clients/import', [ClientImportExportController::class, 'import']);
        Route::get('clients/export/template', [ClientImportExportController::class, 'downloadTemplate']);
        Route::get('clients/export/template/excel', [ClientImportExportController::class, 'downloadExcelTemplate']);
        Route::post('clients/export', [ClientImportExportController::class, 'export']);

        // Client notes management
        Route::get('clients/{client}/notes', [ClientNoteController::class, 'index']);
        Route::post('clients/{client}/notes', [ClientNoteController::class, 'store']);
        Route::get('notes/{note}', [ClientNoteController::class, 'show']);
        Route::put('notes/{note}', [ClientNoteController::class, 'update']);
        Route::delete('notes/{note}', [ClientNoteController::class, 'destroy']);
        Route::post('notes/{note}/pin', [ClientNoteController::class, 'togglePin']);
        Route::post('notes/{note}/attachments', [ClientNoteController::class, 'addAttachment']);
        Route::delete('notes/attachments/{attachment}', [ClientNoteController::class, 'removeAttachment']);

        // Call logs management
        Route::get('clients/{client}/calls', [CallLogController::class, 'index']);
        Route::post('clients/{client}/calls', [CallLogController::class, 'store']);
        Route::get('calls/{call}', [CallLogController::class, 'show']);
        Route::put('calls/{call}', [CallLogController::class, 'update']);
        Route::delete('calls/{call}', [CallLogController::class, 'destroy']);
        Route::put('calls/{call}/complete-follow-up', [CallLogController::class, 'completeFollowUp']);
        Route::get('dashboard/calls/follow-ups', [CallLogController::class, 'pendingFollowUps']);

        // Appointments management
        Route::get('clients/{client}/appointments', [AppointmentController::class, 'index']);
        Route::post('clients/{client}/appointments', [AppointmentController::class, 'store']);
        Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
        Route::put('appointments/{appointment}', [AppointmentController::class, 'update']);
        Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy']);
        Route::put('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
        Route::get('dashboard/appointments/today', [AppointmentController::class, 'today']);
        Route::get('dashboard/appointments/upcoming', [AppointmentController::class, 'upcoming']);

        // Client timeline management
        Route::get('clients/{client}/timeline', [ClientTimelineController::class, 'index']);
        Route::get('clients/{client}/timeline/export', [ClientTimelineController::class, 'export']);
        Route::get('dashboard/interactions', [ClientTimelineController::class, 'dashboard']);

        // Client documents management
        Route::get('clients/{client}/documents', [ClientDocumentController::class, 'index'])->name('clients.documents.index');
        Route::post('clients/{client}/documents', [ClientDocumentController::class, 'store'])->name('clients.documents.store');
        Route::get('clients/{client}/documents/{document}', [ClientDocumentController::class, 'show'])->name('clients.documents.show');
        Route::put('clients/{client}/documents/{document}', [ClientDocumentController::class, 'update'])->name('clients.documents.update');
        Route::delete('clients/{client}/documents/{document}', [ClientDocumentController::class, 'destroy'])->name('clients.documents.destroy');
        Route::get('clients/{client}/documents/{document}/download', [ClientDocumentController::class, 'download'])->name('clients.documents.download');
        Route::get('clients/{client}/documents/{document}/preview', [ClientDocumentController::class, 'preview'])->name('clients.documents.preview');
        Route::get('clients/{client}/documents/{document}/versions', [ClientDocumentController::class, 'versions'])->name('clients.documents.versions');

        // Supplier documents management
        Route::get('suppliers/{supplier}/documents', [SupplierDocumentController::class, 'index'])->name('suppliers.documents.index');
        Route::post('suppliers/{supplier}/documents', [SupplierDocumentController::class, 'store'])->name('suppliers.documents.store');
        Route::get('suppliers/{supplier}/documents/{document}', [SupplierDocumentController::class, 'show'])->name('suppliers.documents.show');
        Route::put('suppliers/{supplier}/documents/{document}', [SupplierDocumentController::class, 'update'])->name('suppliers.documents.update');
        Route::delete('suppliers/{supplier}/documents/{document}', [SupplierDocumentController::class, 'destroy'])->name('suppliers.documents.destroy');
        Route::get('suppliers/{supplier}/documents/{document}/download', [SupplierDocumentController::class, 'download'])->name('suppliers.documents.download');
        Route::get('suppliers/{supplier}/documents/{document}/preview', [SupplierDocumentController::class, 'preview'])->name('suppliers.documents.preview');
        Route::get('suppliers/{supplier}/documents/{document}/versions', [SupplierDocumentController::class, 'versions'])->name('suppliers.documents.versions');

        // Dashboard routes
        // Commercial Dashboard (Manager level)
        Route::prefix('dashboard/commercial')->group(function () {
            Route::get('/overview', [CommercialDashboardController::class, 'overview'])->name('dashboard.commercial.overview');
            Route::get('/stats', [CommercialDashboardController::class, 'stats'])->name('dashboard.commercial.stats');
            Route::get('/clients-evolution', [CommercialDashboardController::class, 'clientsEvolution'])->name('dashboard.commercial.clients-evolution');
            Route::get('/interactions/recent', [CommercialDashboardController::class, 'recentInteractions'])->name('dashboard.commercial.interactions.recent');
            Route::get('/clients/inactive', [CommercialDashboardController::class, 'inactiveClients'])->name('dashboard.commercial.clients.inactive');
        });

        // Personal Dashboard (Individual user level)
        Route::prefix('dashboard/personal')->group(function () {
            Route::get('/overview', [PersonalDashboardController::class, 'overview'])->name('dashboard.personal.overview');
            Route::get('/portfolio', [PersonalDashboardController::class, 'portfolio'])->name('dashboard.personal.portfolio');
            Route::get('/tasks/today', [PersonalDashboardController::class, 'tasksToday'])->name('dashboard.personal.tasks.today');
            Route::get('/appointments/upcoming', [PersonalDashboardController::class, 'upcomingAppointments'])->name('dashboard.personal.appointments.upcoming');
            Route::get('/interactions/recent', [PersonalDashboardController::class, 'recentInteractions'])->name('dashboard.personal.interactions.recent');
        });

        // Roles Management
        Route::apiResource('roles', RoleController::class);
        Route::get('roles/{role}/permissions', [RoleController::class, 'permissions']);
        Route::post('roles/{role}/permissions', [RoleController::class, 'assignPermissions']);
        Route::delete('roles/{role}/permissions/{permission}', [RoleController::class, 'removePermission']);

        // Permissions Management
        Route::get('permissions', [PermissionController::class, 'index']);
        Route::get('permissions/modules', [PermissionController::class, 'modules']);
        Route::get('permissions/{permission}', [PermissionController::class, 'show']);
        Route::post('permissions/check', [PermissionController::class, 'checkPermission']);
        Route::post('permissions/bulk-check', [PermissionController::class, 'bulkCheckPermissions']);
        Route::get('users/me/permissions', [PermissionController::class, 'myPermissions']);
        Route::get('permissions/user/{user}', [PermissionController::class, 'userPermissions']);

        // User Role Assignment
        Route::get('users/{user}/roles', [UserRoleController::class, 'index']);
        Route::post('users/{user}/roles', [UserRoleController::class, 'store']);
        Route::put('users/{user}/roles/{role}', [UserRoleController::class, 'update']);
        Route::delete('users/{user}/roles/{role}', [UserRoleController::class, 'destroy']);
        Route::post('users/{user}/roles/bulk-assign', [UserRoleController::class, 'bulkAssign']);
        Route::post('users/{user}/roles/bulk-remove', [UserRoleController::class, 'bulkRemove']);

        // Users Management
        Route::get('users/search', [UserController::class, 'search']);
        Route::get('users/validate-email', [UserController::class, 'validateEmail']);
        Route::get('users/stats', [UserController::class, 'stats']);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword']);
        Route::apiResource('users', UserController::class);

        // Email Notification Preferences
        Route::get('users/{user}/email-preferences', [EmailNotificationPreferenceController::class, 'show']);
        Route::put('users/{user}/email-preferences', [EmailNotificationPreferenceController::class, 'update']);
        Route::get('email-reminders/pending', [EmailNotificationPreferenceController::class, 'getPendingReminders']);
        Route::get('email-reminders/sent', [EmailNotificationPreferenceController::class, 'getSentReminders']);
        Route::get('email-reminders/statistics', [EmailNotificationPreferenceController::class, 'getStatistics']);

        // Calls routes (US-CC-001)
        Route::post('/calls', [App\Http\Controllers\Api\V1\CallController::class, 'store']);
    });
});

