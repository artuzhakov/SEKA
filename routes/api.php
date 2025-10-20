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

Route::get('/debug/seka-routes', function () {
    $routes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
        ->filter(fn($route) => str_starts_with($route->uri(), 'api/'))
        ->map(fn($route) => [
            'uri' => $route->uri(),
            'methods' => $route->methods(),
            'action' => $route->getActionName(),
        ])
        ->values();

    return response()->json($routes);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('games')->group(function () {
        // 🎯 Основные маршруты игры
        Route::post('/start', [GameController::class, 'start']);
        Route::get('/{game}/status', [GameController::class, 'getStatus']);
        
        // 🎯 Готовность игроков
        Route::post('/{game}/ready', [GameController::class, 'markReady']);
        Route::get('/{game}/timers', [GameController::class, 'getTimers']);
        
        // 🎯 Игровой процесс
        Route::post('/{game}/distribution', [GameController::class, 'startDistribution']);
        Route::post('/{game}/action', [GameController::class, 'playerAction']);
        Route::post('/{game}/finish', [GameController::class, 'finish']);
        
        // 🎯 Споры (свары)
        Route::post('/{game}/quarrel/initiate', [GameController::class, 'initiateQuarrel']);
        Route::post('/{game}/quarrel/start', [GameController::class, 'startQuarrel']);
        Route::post('/{game}/quarrel/resolve', [GameController::class, 'resolveQuarrel']);
        
        // 🎯 Таймауты (для cron job или WebSocket)
        Route::post('/{game}/check-timeouts', [GameController::class, 'checkTimeouts']);
    });
});

Route::prefix('game')->group(function () {
    Route::post('/{gameId}/start', function (Request $request, $gameId) {
        broadcast(new \App\Events\GameStarted(
            gameId: $gameId,
            players: [
                ['id' => 'player1', 'name' => 'Alice', 'chips' => 1000],
                ['id' => 'player2', 'name' => 'Bob', 'chips' => 1000]
            ],
            firstPlayerId: 'player1',
            initialState: [
                'pot' => 0,
                'current_bet' => 10,
                'community_cards' => [],
                'round' => 'preflop'
            ]
        ));

        return response()->json(['success' => true, 'message' => 'Game started']);
    });

    Route::post('/{gameId}/join', function (Request $request, $gameId) {
        $player = $request->validate(['player_id' => 'required', 'player_name' => 'required']);

        broadcast(new \App\Events\PlayerJoined(
            gameId: $gameId,
            player: ['id' => $player['player_id'], 'name' => $player['player_name'], 'chips' => 1000],
            playersList: [
                ['id' => 'player1', 'name' => 'Alice', 'chips' => 1000],
                ['id' => $player['player_id'], 'name' => $player['player_name'], 'chips' => 1000]
            ],
            currentPlayersCount: 2
        ));

        return response()->json(['success' => true, 'message' => 'Player joined']);
    });

    Route::post('/{gameId}/play-card', function (Request $request, $gameId) {
        $data = $request->validate([
            'player_id' => 'required',
            'card' => 'required|array',
            'action' => 'required|string' // 'fold', 'call', 'raise', 'check'
        ]);

        broadcast(new \App\Events\CardPlayed(
            gameId: $gameId,
            playerId: $data['player_id'],
            card: $data['card'],
            newGameState: [
                'pot' => 150,
                'current_bet' => 20,
                'community_cards' => [],
                'round' => 'preflop'
            ],
            nextPlayerId: 'player2'
        ));

        return response()->json(['success' => true, 'message' => 'Card played']);
    });

    Route::post('/{gameId}/change-turn', function (Request $request, $gameId) {
        $data = $request->validate([
            'previous_player_id' => 'required',
            'current_player_id' => 'required', 
            'turn_time_left' => 'required|integer'
        ]);

        broadcast(new \App\Events\TurnChanged(
            gameId: $gameId,
            previousPlayerId: $data['previous_player_id'],
            currentPlayerId: $data['current_player_id'],
            turnTimeLeft: $data['turn_time_left']
        ));

        return response()->json(['success' => true, 'message' => 'Turn changed']);
    });

    Route::post('/{gameId}/finish', function (Request $request, $gameId) {
        $data = $request->validate([
            'winner_id' => 'required',
            'scores' => 'required|array',
            'final_state' => 'required|array'
        ]);

        broadcast(new \App\Events\GameFinished(
            gameId: $gameId,
            winnerId: $data['winner_id'],
            scores: $data['scores'],
            finalState: $data['final_state']
        ));

        return response()->json(['success' => true, 'message' => 'Game finished']);
    });
});

// 🎯 Временные маршруты для тестов (без аутентификации)
Route::prefix('test')->group(function () {
    Route::post('/games/start', [GameController::class, 'start'])->withoutMiddleware('auth:sanctum');
    Route::post('/games/{game}/action', [GameController::class, 'playerAction'])->withoutMiddleware('auth:sanctum');
    Route::post('/games/{game}/ready', [GameController::class, 'markReady'])->withoutMiddleware('auth:sanctum');
});

// Тестовые маршруты для проверки Pusher
Route::prefix('test')->group(function () {
    Route::post('/pusher/event', function (Request $request) {
        \Log::info('=== PUSHER TEST START ===');
        
        $validated = $request->validate([
            'game_id' => 'required|integer',
            'message' => 'required|string'
        ]);

        \Log::info('Validated data:', $validated);

        try {
            // Проверим конфигурацию broadcasting
            $broadcastDriver = config('broadcasting.default');
            \Log::info("Broadcast driver: {$broadcastDriver}");
            
            $pusherConfig = config('broadcasting.connections.pusher');
            \Log::info("Pusher config:", [
                'app_id' => $pusherConfig['app_id'] ? 'SET' : 'MISSING',
                'key' => $pusherConfig['key'] ? 'SET' : 'MISSING', 
                'secret' => $pusherConfig['secret'] ? 'SET' : 'MISSING'
            ]);

            $event = new \App\Events\TestPusherEvent(
                $validated['game_id'],
                $validated['message']
            );

            \Log::info('Event created, broadcasting...');

            // Попробуем разные способы broadcast
            broadcast($event);
            \Log::info('Broadcast called');

            // Альтернативный способ
            // event($event);

            \Log::info('=== PUSHER TEST END ===');

            return response()->json([
                'success' => true,
                'message' => 'Test event sent to Pusher',
                'channel' => "game.{$validated['game_id']}",
                'event' => 'test.event',
                'driver' => $broadcastDriver
            ]);

        } catch (\Exception $e) {
            \Log::error('Broadcast failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::info('=== PUSHER TEST ERROR ===');
            
            return response()->json([
                'success' => false,
                'message' => 'Broadcast failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });
});

Route::get('/pusher-test', function () {
    return inertia('PusherTest');
});

Route::post('/seka/{gameId}/force-start', [GameController::class, 'forceStartGame']);

Route::prefix('seka')->group(function () {
    // 🎯 Основные игровые endpoints
    Route::post('/start', [GameController::class, 'start']);
    Route::post('/{gameId}/ready', [GameController::class, 'markReady']);
    Route::post('/{gameId}/action', [GameController::class, 'playerAction']);
    Route::post('/{gameId}/distribute', [GameController::class, 'startDistribution']);
    Route::post('/{gameId}/finish', [GameController::class, 'finish']);
    
    // 🎯 Свара (quarrel)
    Route::post('/{gameId}/quarrel/initiate', [GameController::class, 'initiateQuarrel']);
    Route::post('/{gameId}/quarrel/start', [GameController::class, 'startQuarrel']);
    Route::post('/{gameId}/quarrel/resolve', [GameController::class, 'resolveQuarrel']);
    
    // 🎯 Информационные endpoints
    Route::get('/{gameId}/status', [GameController::class, 'getStatus']);
    Route::get('/{gameId}/timers', [GameController::class, 'getTimers']);
    Route::get('/{gameId}/check-timeouts', [GameController::class, 'checkTimeouts']);
});

Route::prefix('test-seka')->group(function () {
    // 🎯 Простой тест создания игры
    Route::post('/simple-start', function (Request $request) {
        \Log::info('Simple start game called', $request->all());
        
        try {
            // Создаем простую заглушку игры
            $gameData = [
                'game_id' => 1,
                'players' => [
                    ['id' => 1, 'position' => 1, 'status' => 'waiting', 'balance' => 1000, 'current_bet' => 0, 'is_ready' => false],
                    ['id' => 2, 'position' => 2, 'status' => 'waiting', 'balance' => 1000, 'current_bet' => 0, 'is_ready' => false]
                ],
                'status' => 'waiting',
                'current_player_position' => 1,
                'bank' => 0
            ];

            // Триггерим событие напрямую
            broadcast(new \App\Events\GameStarted(
                gameId: 1,
                players: $gameData['players'],
                firstPlayerId: '1',
                initialState: [
                    'status' => 'waiting',
                    'current_player_position' => 1,
                    'bank' => 0,
                    'round' => 'waiting'
                ]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Simple game started',
                'game_id' => 1
            ]);

        } catch (\Exception $e) {
            \Log::error('Simple start error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    });

    // 🎯 Простой тест готовности
    Route::post('/{gameId}/simple-ready/{playerId}', function ($gameId, $playerId) {
        \Log::info("Player $playerId ready for game $gameId");

        broadcast(new \App\Events\PlayerReady(
            gameId: (int)$gameId,
            playerId: (int)$playerId,
            playerStatus: 'ready',
            readyPlayersCount: 1,
            timeUntilStart: 5
        ));

        return response()->json([
            'success' => true,
            'message' => "Player $playerId marked as ready"
        ]);
    });

    // 🎯 Простой тест раздачи карт
    Route::post('/{gameId}/simple-distribute', function ($gameId) {
        \Log::info("Distribute cards for game $gameId");

        broadcast(new \App\Events\CardsDistributed(
            gameId: (int)$gameId,
            playerCards: [
                1 => ['A♥', 'K♥'],
                2 => ['Q♠', 'J♠']
            ],
            communityCards: ['10♦', '9♦', '8♦'],
            round: 'flop'
        ));

        return response()->json([
            'success' => true,
            'message' => 'Cards distributed'
        ]);
    });
});

Route::post('/seka/ready', [GameController::class, 'markReady']);

Route::post('/seka/{gameId}/clear', [GameController::class, 'clearGame']);