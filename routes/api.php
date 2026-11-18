<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Feature 1: JWT login for API clients. All routes here start with /api.
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('jwt')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
});
