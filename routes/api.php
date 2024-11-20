<?php

use App\Http\Controllers\Api\ApiVerificationController;
use App\Http\Controllers\Api\SmsApiController;
use Illuminate\Support\Facades\Route;


// API v1 routes
Route::prefix('v1')->group(function () {
    Route::get('/verify', [ApiVerificationController::class, 'verifyCredentials']);
    Route::post('/messages/single', [SmsApiController::class, 'sendSingle']);
    Route::post('/messages/bulk', [SmsApiController::class, 'sendBulk']);
    Route::get('/messages/{message_id}/status', [SmsApiController::class, 'checkStatus']);
});

