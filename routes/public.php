<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;

Route::prefix('public')->group(function () {
    Route::prefix('seka')->group(function () {
        Route::post('/calculate-points', [GameController::class, 'calculatePoints']);
    });
});