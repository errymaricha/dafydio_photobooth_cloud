<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\SessionController as AdminSessionController;
use App\Http\Controllers\Admin\StationController as AdminStationController;
use App\Http\Controllers\Admin\SyncLogController as AdminSyncLogController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\PublicSessionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
});

Route::get('/login', function () {
    return Inertia::render('Auth/Login', [
        'tenantSlug' => config('app.demo_tenant_slug', 'dafydio-demo'),
        'defaultMode' => request('mode', 'customer'),
    ]);
})->middleware('guest')->name('login');

Route::get('/customer/login', function () {
    return redirect()->route('login', ['mode' => 'customer']);
})->name('customer.login');

Route::get('/customer/dashboard', function () {
    return Inertia::render('Customer/Dashboard');
})->name('customer.dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/admin/login', fn () => redirect()->route('login', ['mode' => 'admin']))->name('admin.login');
    Route::post('/login/admin', [AuthController::class, 'store'])->middleware('throttle:admin-login')->name('admin.login.store');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/stations', [AdminStationController::class, 'index'])->name('stations.index');
    Route::post('/stations/{station}/token', [AdminStationController::class, 'regenerateToken'])->name('stations.token');
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::patch('/customers/{customer}', [AdminCustomerController::class, 'update'])->name('customers.update');
    Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
    Route::get('/sessions', [AdminSessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{session}', [AdminSessionController::class, 'show'])->name('sessions.show');
    Route::post('/sessions/{session}/link-customer', [AdminSessionController::class, 'linkCustomer'])->name('sessions.link-customer');
    Route::get('/sync-logs', [AdminSyncLogController::class, 'index'])->name('sync-logs.index');
    Route::get('/sync-logs/{syncLog}', [AdminSyncLogController::class, 'show'])->name('sync-logs.show');
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{payment}/approve', [AdminPaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('payments.reject');
    Route::get('/templates', [AdminTemplateController::class, 'index'])->name('templates.index');
    Route::post('/templates', [AdminTemplateController::class, 'store'])->name('templates.store');
    Route::patch('/templates/{template}', [AdminTemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [AdminTemplateController::class, 'destroy'])->name('templates.destroy');
});

Route::get('/{sessionCode}/download', [PublicSessionController::class, 'downloadAll'])
    ->where('sessionCode', '[A-Za-z0-9-]+')
    ->name('public.sessions.download');

Route::get('/{sessionCode}', [PublicSessionController::class, 'show'])
    ->where('sessionCode', '[A-Za-z0-9-]+')
    ->name('public.sessions.show');
