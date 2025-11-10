<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClockEventController;
use App\Http\Controllers\Api\WeekController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\GroupController;

Route::prefix('v1')->group(function () {
    // Clock In/Out action
    Route::post('/clock/action', [ClockEventController::class, 'clockInOut']);
    
    // Get last events (optional userId parameter)
    Route::get('/clock-events/last', [ClockEventController::class, 'getLastEvents']);
    Route::get('/clock-events/last/{userId}', [ClockEventController::class, 'getLastEvents']);
    
    // Clock Events CRUD
    Route::apiResource('clock-events', ClockEventController::class)->except(['index']);
    
    // Week data
    Route::get('/week/{userId}', [WeekController::class, 'get']);
    
    // Users CRUD
    Route::apiResource('users', UserController::class);
    
    // Groups CRUD
    Route::apiResource('groups', GroupController::class);
});

