<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;

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

// 🎯 PUBLIC API ROUTES (без авторизации)
Route::prefix('seka')->group(function () {
    // 🎯 Основные публичные маршруты
    Route::get('/games', [GameController::class, 'listGames']);
    Route::get('/games/{id}/state', [GameController::class, 'getGameState']);
    Route::post('/games/{id}/join', [GameController::class, 'joinGame']);
    Route::post('/games/{id}/ready', [GameController::class, 'markReady']);
    Route::get('/games/joinable', [GameController::class, 'listJoinableGames']);
    Route::get('/lobby', [GameController::class, 'getLobbyGames']);
    
    // 🎯 Подсчёт очков
    Route::post('/public/calculate-points', [GameController::class, 'calculatePoints']);
    
    // 🎯 Очистка лобби - ПЕРЕМЕСТИТЬ СЮДА
    Route::post('/lobby/clear', [GameController::class, 'clearLobby']);
    Route::post('/lobby/cleanup', [GameController::class, 'cleanupLobby']);
});

// 🎯 PROTECTED ROUTES (требуют авторизации)
Route::middleware('auth:sanctum')->group(function () {
    
    // 🎯 SEKA Game Management
    Route::prefix('seka')->group(function () {
        // 🎯 Создание и управление игрой
        Route::post('/games', [GameController::class, 'createGame']);
        Route::post('/games/{gameId}/leave', [GameController::class, 'leaveGame']);
        Route::post('/games/{gameId}/action', [GameController::class, 'playerAction']);
        
        // 🎯 Жизненный цикл игры
        Route::post('/start', [GameController::class, 'start']);
        Route::post('/{gameId}/finish', [GameController::class, 'finish']);
        Route::post('/{gameId}/clear', [GameController::class, 'clearGame']);
        Route::post('/{gameId}/force-start', [GameController::class, 'forceStartGame']);
        
        // 🎯 Игровые действия
        Route::post('/{gameId}/distribute', [GameController::class, 'startDistribution']);
        Route::post('/{gameId}/start-bidding', [GameController::class, 'startBidding']);
        Route::post('/{gameId}/collect-ante', [GameController::class, 'collectAnte']);
        
        // 🎯 Система Свары
        Route::post('/{gameId}/quarrel/initiate', [GameController::class, 'initiateQuarrel']);
        Route::post('/{gameId}/quarrel/start', [GameController::class, 'startQuarrel']);
        Route::post('/{gameId}/quarrel/resolve', [GameController::class, 'resolveQuarrel']);
        
        // 🎯 Информационные эндпоинты
        Route::get('/{gameId}/game-info', [GameController::class, 'getGameInfo']);
        Route::get('/{gameId}/full-state', [GameController::class, 'getFullState']);
        Route::get('/{gameId}/cards', [GameController::class, 'getPlayerCards']);
        Route::get('/{gameId}/timers', [GameController::class, 'getTimers']);
        Route::post('/{gameId}/check-timeouts', [GameController::class, 'checkTimeouts']);
    });

    // 🎯 User authentication
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::post('/seka/games/{gameId}/leave-to-lobby', [GameController::class, 'leaveToLobby']);
Route::get('/seka/test-game-id-generation', [GameController::class, 'testGameIdGeneration']);