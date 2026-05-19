<?php

use App\Http\Controllers\Api\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\Admin\PrintRequestController as AdminPrintRequestController;
use App\Http\Controllers\Api\Admin\SessionController as AdminSessionController;
use App\Http\Controllers\Api\Admin\StationController as AdminStationController;
use App\Http\Controllers\Api\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\Api\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Api\Customer\AssetDownloadController as CustomerAssetDownloadController;
use App\Http\Controllers\Api\Customer\EditJobController as CustomerEditJobController;
use App\Http\Controllers\Api\Customer\PaymentController as CustomerPaymentController;
use App\Http\Controllers\Api\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Api\Customer\PrintRequestController as CustomerPrintRequestController;
use App\Http\Controllers\Api\Customer\SessionController as CustomerSessionController;
use App\Http\Controllers\Api\Customer\TemplateController as CustomerTemplateController;
use App\Http\Controllers\Api\Station\CustomerController as StationCustomerController;
use App\Http\Controllers\Api\Station\HeartbeatController;
use App\Http\Controllers\Api\Station\PrintRequestController as StationPrintRequestController;
use App\Http\Controllers\Api\Station\SessionSyncController;
use App\Http\Controllers\Api\Station\TemplateSyncController;
use App\Http\Middleware\AuthenticateStation;
use Illuminate\Support\Facades\Route;

Route::prefix('station')
    ->middleware(AuthenticateStation::class)
    ->group(function (): void {
        Route::post('heartbeat', HeartbeatController::class);
        Route::post('customers', [StationCustomerController::class, 'store']);
        Route::post('customers/cloud-account', [StationCustomerController::class, 'cloudAccount']);
        Route::post('sync/session', [SessionSyncController::class, 'syncSession']);
        Route::post('sync/template', [TemplateSyncController::class, 'sync']);
        Route::post('templates/{template}/assets', [TemplateSyncController::class, 'assets']);
        Route::match(['post', 'put'], 'templates/{template}/assets/{stationAssetId}/upload', [TemplateSyncController::class, 'uploadAsset']);
        Route::post('templates/{template}/assets/{stationAssetId}/complete', [TemplateSyncController::class, 'completeAsset']);
        Route::post('sessions', [SessionSyncController::class, 'store']);
        Route::post('sessions/{cloudSession}/assets', [SessionSyncController::class, 'assets']);
        Route::post('sessions/{cloudSession}/link-customer', [SessionSyncController::class, 'linkCustomer']);
        Route::match(['post', 'put'], 'assets/{cloudAsset}/upload', [SessionSyncController::class, 'uploadAsset']);
        Route::post('assets/{cloudAsset}/complete', [SessionSyncController::class, 'completeAsset']);
        Route::post('sessions/{cloudSession}/finalize', [SessionSyncController::class, 'finalize']);
        Route::get('print-requests', [StationPrintRequestController::class, 'index']);
        Route::patch('print-requests/{printRequest}', [StationPrintRequestController::class, 'update']);
    });

Route::prefix('customer')->group(function (): void {
    Route::post('auth/login', [CustomerAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [CustomerAuthController::class, 'logout']);
        Route::patch('profile', [CustomerProfileController::class, 'update']);
        Route::get('sessions', [CustomerSessionController::class, 'index']);
        Route::get('sessions/{cloudSession}', [CustomerSessionController::class, 'show']);
        Route::post('assets/{cloudAsset}/download-url', CustomerAssetDownloadController::class);
        Route::get('templates', [CustomerTemplateController::class, 'index']);
        Route::post('templates/{template}/purchase', [CustomerTemplateController::class, 'purchase']);
        Route::get('payments', [CustomerPaymentController::class, 'index']);
        Route::get('edit-jobs', [CustomerEditJobController::class, 'index']);
        Route::post('edit-jobs', [CustomerEditJobController::class, 'store']);
        Route::post('print-requests', [CustomerPrintRequestController::class, 'store']);
    });
});

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'tenant.admin'])
    ->group(function (): void {
        Route::get('stations', [AdminStationController::class, 'index']);
        Route::post('stations/{station}/tokens', [AdminStationController::class, 'token']);
        Route::get('customers', [AdminCustomerController::class, 'index']);
        Route::get('sessions', [AdminSessionController::class, 'index']);
        Route::get('print-requests', [AdminPrintRequestController::class, 'index']);
        Route::get('templates', [AdminTemplateController::class, 'index']);
        Route::post('templates', [AdminTemplateController::class, 'store']);
        Route::patch('templates/{template}', [AdminTemplateController::class, 'update']);
        Route::delete('templates/{template}', [AdminTemplateController::class, 'destroy']);
    });

Route::prefix('webhooks')->group(function (): void {
    Route::post('midtrans', fn () => response()->json(['message' => 'Midtrans webhook received']));
    Route::post('xendit', fn () => response()->json(['message' => 'Xendit webhook received']));
});
