<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoanApplicationController;
use App\Http\Controllers\Api\LoanHistoryController;
use App\Http\Controllers\Api\LocationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('/v1')->middleware('api.key', 'secure.api')->group(function () {
    Route::get('/locations/wards', [LocationController::class, 'wards']);
    Route::get('/loans/member/{nationalId}', [LoanHistoryController::class, 'checkMember']);
    Route::get('/loans/group/{registrationNumber}', [LoanHistoryController::class, 'checkGroup']);
    Route::get('/loans/group-loan/{groupLoanId}', [LoanHistoryController::class, 'showGroupLoan']);
    Route::post('/loans/debt-reminder-sms', [LoanHistoryController::class, 'sendDebtReminderSms']);
    Route::post('/loans/repayments', [LoanHistoryController::class, 'recordRepayment']);
    Route::post('/application', [LoanApplicationController::class, 'store']);
    Route::get('/application/{id}', [LoanApplicationController::class, 'show']);
    // Route::put('/application/{id}', [LoanApplicationController::class, 'update']);
    // Route::post('/applications/{applicationId}/upload-file', [LoanApplicationController::class, 'uploadFile']);
    // Route::get('/applications/{applicationId}/files', [LoanApplicationController::class, 'getApplicationFiles']);
    // Health check endpoint
    Route::get('api/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'LMS API'
        ]);
    });
});

//middlware auth download file
Route::middleware('auth:sanctum')->get('/files/{fileId}/download', [LoanApplicationController::class, 'downloadFile'])->name('api.application.file.download');


Route::prefix('/v1/test/secure')->middleware('secure.api')->group(function () {
    Route::post('/application', [LoanApplicationController::class, 'store']);
    Route::get('/application/{id}', [LoanApplicationController::class, 'show']);
    Route::put('/application/{id}', [LoanApplicationController::class, 'update']);

    // Health check endpoint
    Route::get('api/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'LMS API'
        ]);
    });
});

Route::prefix('test')->group(function () {
    Route::get('/v1/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'LMS API GET',
            'version' => '1.0.0'
        ]);
    });

    Route::post('/v1/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'LMS API POST',
            'version' => '1.0.0'
        ]);
    })->middleware('verifyApiKey');
});




Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
