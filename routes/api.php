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
        Route::apiResource('clients', ClientController::class);

        // Suppliers management
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
        Route::get('dashboard/interactions', [ClientTimelineController::class, 'dashboard']);

        // Add protected API routes here
    });
});

