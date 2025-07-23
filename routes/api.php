<?php

use App\Http\Controllers\Api\Admin\FinancialController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InvoiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/announcements', [AnnouncementController::class, 'index']);

// Rute Terproteksi (wajib login/punya token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // === RUTE UNTUK WARGA ===
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices/{invoice}/upload-proof', [InvoiceController::class, 'uploadProof']);

    // Grup rute khusus untuk 'pengurus'
    // Setiap rute di dalam grup ini akan dicek oleh middleware IsPengurus
    Route::middleware('is_pengurus')->prefix('admin')->group(function () {
        Route::apiResource('announcements', AnnouncementController::class)->except(['index']);
        // Ini akan otomatis membuat rute GET (index), PUT (update), dan DELETE (destroy)
        Route::apiResource('users', UserController::class)->only(['index', 'update', 'destroy']);

        // Rute untuk mengelola Iuran (Invoices)
        Route::post('/invoices/generate-monthly', [FinancialController::class, 'generateMonthlyInvoices']);
        Route::patch('/invoices/{invoice}/verify', [FinancialController::class, 'verifyPayment']);
        Route::get('/invoices', [FinancialController::class, 'getAllInvoices']);

        // Rute untuk mengelola Pengeluaran (Expenses)
        Route::apiResource('expenses', FinancialController::class)->except(['show']);

        Route::get('/financial-summary', [FinancialController::class, 'getFinancialSummary']);
    });
});
