<?php

use App\Http\Controllers\ApiKeyController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/api-keys/generate', [ApiKeyController::class, 'generateCredentials']);
    Route::post('/api-keys/revoke', [ApiKeyController::class, 'revokeCredentials']);
    Route::get('/api-keys/current', [ApiKeyController::class, 'getCurrentKey']);
});

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
});
