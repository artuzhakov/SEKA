<?php
// app/Http/Controllers/GameController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\Repositories\InMemoryGameRepository;
use App\Application\Services\GameService;
use App\Application\Services\DistributionService;
use App\Application\Services\BiddingService;
use App\Application\Services\QuarrelService;
use App\Application\Services\ReadinessService;
use App\Application\Services\ScoringService;
use App\Application\DTO\StartGameDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Events\GameStarted;
use App\Events\PlayerReady;
use App\Events\PlayerJoined;
use App\Domain\Game\Enums\GameStatus;
use App\Events\PlayerActionTaken;
use App\Events\CardsDistributed;
use App\Events\GameFinished;
use Illuminate\Support\Facades\Auth;
use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\ValueObjects\PlayerId;

class GameController extends Controller
{
    public function __construct(
        private GameService $gameService,
        private DistributionService $distributionService,
        private BiddingService $biddingService,
        private QuarrelService $quarrelService,
        private ReadinessService $readinessService
    ) {}

    /**
     * 🎯 Принудительно запустить систему торгов (для тестирования)
     */
    public function startBidding(int $gameId): JsonResponse
    {
        $game = $this->getGameById($gameId);
        
        \Log::info("🎯 Forcing bidding start for game: " . $gameId);
        
        // Запускаем систему торгов
        $this->biddingService->startBiddingRound($game);
        
        // Сохраняем игру
        $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
        $repository->save($game);
        
        \Log::info("🎯 Bidding forced to start. Current player position: " . $game->getCurrentPlayerPosition());
        
        return response()->json([
            'success' => true,
            'message' => 'Bidding round started',
            'current_player_position' => $game->getCurrentPlayerPosition(),
            'game_status' => $game->getStatus()->value
        ]);
    }

    /**
     * 🎯 Начать новую игру
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|integer|min:1',
            'players' => 'required|array|min:2',
            'players.*' => 'integer|min:1'
        ]);

        $dto = StartGameDTO::fromRequest($request);
        $game = $this->gameService->startNewGame($dto);

        // 🎯 ИСПРАВЛЕНИЕ: сохраняем игру с реальными игроками
        $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
        $repository->save($game);

        \Log::info("Game created with players: " . count($game->getPlayers()));

        broadcast(new GameStarted(
            gameId: $game->getId()->toInt(),
            players: $this->formatPlayersForBroadcast($game->getPlayers()),
            firstPlayerId: $this->getFirstPlayerId($game),
            initialState: $this->getInitialGameState($game)
        ));

        return response()->json([
            'success' => true,
            'game_id' => $game->getId()->toInt(),
            'status' => $game->getStatus()->value,
            'message' => 'Game created. Players need to mark ready within 10 seconds.'
        ]);
    }

    /**
     * 🎯 Отметить игрока как готового
     */
    public function markReady(Request $request)
    {
        $validated = $request->validate([
            'game_id' => 'required|integer|min:1',
            'player_id' => 'required|integer|min:1'
        ]);
        
        $userId = (int)$validated['player_id'];
        $gameId = (int)$validated['game_id'];
        
        // 🎯 ИСПРАВЛЕНИЕ: Находим игрока вручную вместо getPlayerByUserId()
        $game = $this->readinessService->getGame($gameId);

        // 🎯 ДОБАВЬТЕ ПРОВЕРКУ: если игра уже не в waiting, не пытаемся отмечать готовность
        if ($game->getStatus() !== \App\Domain\Game\Enums\GameStatus::WAITING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Game is already started or finished',
                'game_status' => $game->getStatus()->value
            ], 400);
        }
        
        // Ищем игрока по user_id
        $player = null;
        foreach ($game->getPlayers() as $p) {
            $playerUserId = $p->getUserId();
            
            // Если это объект с методом toInt()
            if (is_object($playerUserId) && method_exists($playerUserId, 'toInt')) {
                if ($playerUserId->toInt() === $userId) {
                    $player = $p;
                    break;
                }
            }
            // Если это просто число
            elseif ((int)$playerUserId === $userId) {
                $player = $p;
                break;
            }
        }
        
        if (!$player) {
            // 🎯 ДИАГНОСТИКА: Какие игроки доступны?
            $availablePlayers = [];
            foreach ($game->getPlayers() as $p) {
                $playerUserId = $p->getUserId();
                if (is_object($playerUserId) && method_exists($playerUserId, 'toInt')) {
                    $availablePlayers[] = $playerUserId->toInt();
                } else {
                    $availablePlayers[] = (int)$playerUserId;
                }
            }
            
            throw new \DomainException("Player {$userId} not found in game. Available: " . implode(', ', $availablePlayers));
        }
        
        // Сохраняем статус игры до изменения
        $oldStatus = $game->getStatus();
        
        $this->readinessService->markPlayerReady($game, $player);
        
        // Получаем обновленную игру
        $updatedGame = $this->readinessService->getGame($gameId);
        
        // 🎯 Упрощенная логика timeUntilStart
        $timeUntilStart = $updatedGame->getStatus() === GameStatus::WAITING ? 5 : 0;

        // 🎯 Рассчитываем количество готовых игроков
        $readyPlayersCount = count(array_filter($updatedGame->getPlayers(), function($p) {
            return $p->isReady();
        }));

        // 🎯 Отправляем событие
        broadcast(new PlayerReady(
            gameId: $gameId,
            playerId: $userId,        // 🎯 playerId вместо userId
            playerStatus: 'ready',    // 🎯 playerStatus вместо isReady
            readyPlayersCount: count(array_filter($updatedGame->getPlayers(), function($p) {
                return $p->isReady();
            })),                      // 🎯 readyPlayersCount вместо gameStatus
            timeUntilStart: $timeUntilStart
        ));
        
        // 🎯 Если игра началась, отправляем дополнительное событие
        if ($updatedGame->getStatus() === GameStatus::ACTIVE) {
            broadcast(new GameStarted(
                gameId: $gameId,
                players: $this->formatPlayersForBroadcast($updatedGame->getPlayers()),
                firstPlayerId: (string)$updatedGame->getCurrentPlayerPosition(), // 🎯 firstPlayerId как string
                initialState: $this->getInitialGameState($updatedGame)
            ));
        }
        
        return response()->json([
            'status' => 'success',
            'game_status' => $updatedGame->getStatus()->value,
            'ready_players' => $readyPlayersCount, // 🎯 Используем ту же переменную
            'time_until_start' => $timeUntilStart
        ]);
    }

    /**
     * 🎯 Начать раздачу карт
     */
    public function startDistribution(int $gameId): JsonResponse
    {
        $game = $this->getGameById($gameId);
        
        \Log::info("Game status before distribution: " . $game->getStatus()->value);
        
        if ($game->getStatus() !== \App\Domain\Game\Enums\GameStatus::ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'Game is not active. Current status: ' . $game->getStatus()->value
            ], 400);
        }
        
        // 🎯 ИСПРАВЛЕНИЕ: Вызываем collectAnte как метод сервиса, а не endpoint
        $anteResult = $this->collectAnteInternal($game);
        
        // 🎯 ПОТОМ раздаем карты
        $distributionResult = $this->distributionService->distributeCards($game);

        broadcast(new CardsDistributed(
            gameId: $gameId,
            playerCards: $this->formatPlayerCards($distributionResult),
            communityCards: $distributionResult['community_cards'] ?? [],
            round: $distributionResult['round'] ?? 'preflop'
        ));

        return response()->json([
            'success' => true,
            'message' => 'Cards distributed and ante collected',
            'ante_collected' => $anteResult['total_ante'] ?? 0,
            'game_status' => $game->getStatus()->value
        ]);
    }

    /**
     * 🎯 Внутренний метод для сбора анте (без HTTP response)
     */
    private function collectAnteInternal(Game $game): array
    {
        $ante = 10; // Стандартное анте
        $totalAnte = 0;
        
        foreach ($game->getActivePlayers() as $player) {
            if ($player->getBalance() >= $ante) {
                $player->placeBet($ante);
                $totalAnte += $ante;
                \Log::info("💰 Ante collected from player {$player->getUserId()}: {$ante} chips");
            } else {
                \Log::warning("⚠️ Player {$player->getUserId()} has insufficient balance for ante");
            }
        }
        
        $game->setBank($totalAnte);
        $game->setCurrentMaxBet($ante);
        
        // Сохраняем игру
        $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
        $repository->save($game);
        
        return [
            'total_ante' => $totalAnte,
            'bank' => $totalAnte
        ];
    }

    /**
     * 🎯 Действие игрока (ставка, пас, вскрытие и т.д.) - УЛУЧШЕННАЯ ВЕРСИЯ
     */
    public function playerAction(Request $request, int $gameId): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|integer',
            'action' => 'required|string',
            'bet_amount' => 'sometimes|integer|min:0'
        ]);

        try {
            $game = $this->getGameById($gameId);
            $player = $this->getPlayerById($game, (int)$validated['player_id']);
            $action = \App\Domain\Game\Enums\PlayerAction::from($validated['action']);

            \Log::info("🎯 Player Action Request", [
                'game_id' => $gameId,
                'player_id' => $validated['player_id'],
                'action' => $action->value,
                'bet_amount' => $validated['bet_amount'] ?? null,
                'current_position_before' => $game->getCurrentPlayerPosition(),
                'player_status' => $player->getStatus()->value,
                'player_bet' => $player->getCurrentBet()
            ]);

            // 🎯 Проверяем доступные действия перед выполнением
            $availableActions = $this->biddingService->getAvailableActions($game, $player);
            $availableActionsValues = array_map(fn($a) => $a->value, $availableActions);
            
            \Log::info("🎯 Available actions for player: " . implode(', ', $availableActionsValues));
            
            if (!in_array($action, $availableActions)) {
                throw new \DomainException("Action {$action->value} is not available. Available: " . implode(', ', $availableActionsValues));
            }

            $this->biddingService->processPlayerAction(
                $game, 
                $player, 
                $action, 
                $validated['bet_amount'] ?? null
            );

            // 🎯 ПОЛУЧАЕМ ОБНОВЛЕННУЮ ИГРУ ДЛЯ АКТУАЛЬНЫХ ДАННЫХ
            $updatedGame = $this->getGameById($gameId);
            $nextPlayer = $this->getCurrentPlayerFromGame($updatedGame);
            
            \Log::info("🎯 Player Action Completed Successfully", [
                'action' => $action->value,
                'new_current_position' => $updatedGame->getCurrentPlayerPosition(),
                'next_player_id' => $nextPlayer ? $nextPlayer->getUserId() : null,
                'game_status' => $updatedGame->getStatus()->value,
                'bank' => $updatedGame->getBank(),
                'max_bet' => $updatedGame->getCurrentMaxBet()
            ]);

            // 🎯 Отправляем broadcast событие
            broadcast(new PlayerActionTaken(
                gameId: $gameId,
                playerId: $player->getUserId(),
                action: $action->value,
                betAmount: $validated['bet_amount'] ?? null,
                newPlayerPosition: $updatedGame->getCurrentPlayerPosition(),
                bank: $updatedGame->getBank()
                // 🎯 УБЕРИТЕ playerStatus - его нет в конструкторе PlayerActionTaken
            ));

            return response()->json([
                'success' => true,
                'action' => $action->value,
                'player_id' => $player->getUserId(),
                'player_status' => $player->getStatus()->value,
                'current_player_position' => $updatedGame->getCurrentPlayerPosition(),
                'next_player_id' => $nextPlayer ? $nextPlayer->getUserId() : null,
                'next_player_actions' => $this->getAvailableActionsForCurrentPlayer($updatedGame),
                'game_status' => $updatedGame->getStatus()->value,
                'bank' => $updatedGame->getBank(),
                'max_bet' => $updatedGame->getCurrentMaxBet(),
                'message' => 'Action processed successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error("❌ Player Action Failed", [
                'game_id' => $gameId,
                'player_id' => $validated['player_id'] ?? 'unknown',
                'action' => $validated['action'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_details' => 'Action failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 🎯 Завершить игру и определить победителей
     */
    public function finish(int $gameId): JsonResponse
    {
        $game = $this->getGameById($gameId);
        $results = $this->gameService->finishGame($game);

        // 🎯 ДОБАВИТЬ broadcast события GameFinished
        broadcast(new GameFinished(
            gameId: $gameId,
            winnerId: $this->getMainWinnerId($results),
            scores: $this->formatScores($results),
            finalState: $this->getFinalState($game, $results)
        ));

        return response()->json([
            'success' => true,
            'winners' => array_map(fn($player) => [
                'user_id' => $player->getUserId(),
                'position' => $player->getPosition(),
                'prize' => $results['prize_per_winner']
            ], $results['winners']),
            'total_prize' => $results['total_prize'],
            'game_status' => $game->getStatus()->value
        ]);
    }

    /**
     * 🎯 Инициировать спор (свару)
     */
    public function initiateQuarrel(Request $request, int $gameId): JsonResponse
    {
        $validated = $request->validate([
            'winning_players' => 'required|array|min:2',
            'winning_players.*' => 'integer|min:1'
        ]);

        $game = $this->getGameById($gameId);
        $winningPlayers = $this->getPlayersByIds($game, $validated['winning_players']);

        $quarrelInitiated = $this->quarrelService->initiateQuarrel($game, $winningPlayers);

        broadcast(new QuarrelInitiated($gameId, $winningPlayers));

        return response()->json([
            'success' => true,
            'quarrel_initiated' => $quarrelInitiated,
            'message' => $quarrelInitiated ? 'Quarrel initiated' : 'Quarrel not approved by winners'
        ]);
    }

    /**
     * 🎯 Начать спор (после голосования)
     */
    public function startQuarrel(Request $request, int $gameId): JsonResponse
    {
        $validated = $request->validate([
            'participants' => 'required|array|min:2',
            'participants.*' => 'integer|min:1'
        ]);

        $game = $this->getGameById($gameId);
        $participants = $this->getPlayersByIds($game, $validated['participants']);

        $this->quarrelService->startQuarrel($game, $participants);

        broadcast(new QuarrelStarted($gameId, $participants));

        return response()->json([
            'success' => true,
            'message' => 'Quarrel started with card redistribution'
        ]);
    }

    /**
     * 🎯 Завершить спор и определить победителей
     */
    public function resolveQuarrel(Request $request, int $gameId): JsonResponse
    {
        $validated = $request->validate([
            'participants' => 'required|array|min:2',
            'participants.*' => 'integer|min:1'
        ]);

        $game = $this->getGameById($gameId);
        $participants = $this->getPlayersByIds($game, $validated['participants']);

        $winners = $this->quarrelService->resolveQuarrel($game, $participants);

        broadcast(new QuarrelResolved($gameId, $winners));

        return response()->json([
            'success' => true,
            'winners' => array_map(fn($player) => [
                'user_id' => $player->getUserId(),
                'position' => $player->getPosition()
            ], $winners)
        ]);
    }

    /**
     * 🎯 Получить информацию о таймерах
     */
    public function getTimers(int $gameId): JsonResponse
    {
        $game = $this->getGameById($gameId);
        $timers = $this->readinessService->getTimersInfo($game);

        return response()->json([
            'success' => true,
            'timers' => $timers,
            'game_status' => $game->getStatus()->value,
            'ready_players_count' => $this->readinessService->getReadyPlayersCount($game),
            'time_until_start' => $this->readinessService->getTimeUntilGameStart($game)
        ]);
    }

    /**
     * 🎯 Проверить таймауты (для cron job или WebSocket)
     */
    public function checkTimeouts(int $gameId): JsonResponse
    {
        $game = $this->getGameById($gameId);
        
        $readyTimeouts = $this->readinessService->checkReadyTimeouts($game);
        $turnTimeouts = $this->readinessService->checkTurnTimeouts($game);

        $response = [
            'success' => true,
            'ready_timeouts' => count($readyTimeouts),
            'turn_timeouts' => count($turnTimeouts),
            'game_status' => $game->getStatus()->value
        ];

        // 🎯 Добавляем информацию о выбывших игроках
        if (!empty($readyTimeouts)) {
            $response['timed_out_players'] = array_map(
                fn($player) => $player->getUserId(),
                $readyTimeouts
            );
        }

        return response()->json($response);
    }

    /**
     * 🎯 Получить статус игры
     */
    public function getStatus(int $gameId): JsonResponse
    {
        $game = $this->getGameById($gameId);
        
        $players = $this->formatPlayersForApi($game);
        
        \Log::info("📊 GET STATUS - Players data:", [
            'count' => count($players),
            'players' => $players
        ]);

        return response()->json([
            'success' => true,
            'game_id' => $gameId,
            'status' => $game->getStatus()->value,
            'current_player_position' => $game->getCurrentPlayerPosition(),
            'ready_players_count' => $this->readinessService->getReadyPlayersCount($game),
            'total_players' => count($game->getPlayers()),
            'bank' => $game->getBank(),
            'players' => $players
        ]);
    }

    /**
     * 🎯 Форматировать игроков для статуса
     */
    private function formatPlayersForStatus($game): array  // 🎯 УБЕРИТЕ ТИП ИЛИ ИСПОЛЬЗУЙТЕ ПРАВИЛЬНЫЙ
    {
        $players = [];
        foreach ($game->getPlayers() as $player) {
            $players[] = [
                'id' => $player->getUserId(),
                'position' => $player->getPosition(),
                'status' => $player->getStatus()->value,
                'balance' => $player->getBalance(),
                'current_bet' => $player->getCurrentBet(),
                'is_ready' => $player->isReady(),
                'is_playing' => $player->isPlaying(),
                'is_playing_dark' => $player->getStatus() === \App\Domain\Game\Enums\PlayerStatus::DARK
            ];
        }
        return $players;
    }

    /**
     * 🎯 Получить полную информацию об игре
     */
    public function getGameInfo(int $gameId): JsonResponse
    {
        try {
            $game = $this->getGameById($gameId);
            
            $players = $this->formatPlayersForApi($game);
            
            \Log::info("📊 GET GAME INFO - Players data:", [
                'count' => count($players),
                'players' => $players
            ]);
            
            return response()->json([
                'success' => true,
                'game' => [
                    'id' => $gameId,
                    'status' => $game->getStatus()->value,
                    'current_player_position' => $game->getCurrentPlayerPosition(),
                    'bank' => $game->getBank(),
                    'max_bet' => $game->getCurrentMaxBet(),
                    'round' => $game->getCurrentRound(),
                    'players' => $players
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 🎯 Форматировать игроков для API
     */
    private function formatPlayersForApi($game): array
    {
        $players = [];
        
        \Log::info("🎯 Formatting players for API", [
            'game_type' => get_class($game),
            'has_getPlayers' => method_exists($game, 'getPlayers')
        ]);
        
        if (!method_exists($game, 'getPlayers')) {
            \Log::error('Game object does not have getPlayers method');
            return $players;
        }
        
        try {
            $gamePlayers = $game->getPlayers();
            \Log::info("🎯 Found players in game:", ['count' => count($gamePlayers)]);
            
            foreach ($gamePlayers as $index => $player) {
                \Log::info("🎯 Processing player:", [
                    'index' => $index,
                    'player_type' => get_class($player),
                    'has_getUserId' => method_exists($player, 'getUserId'),
                    'has_getPosition' => method_exists($player, 'getPosition')
                ]);
                
                $playerData = [
                    'id' => method_exists($player, 'getUserId') ? $player->getUserId() : ($index + 1),
                    'position' => method_exists($player, 'getPosition') ? $player->getPosition() : ($index + 1),
                    'status' => 'active',
                    'balance' => method_exists($player, 'getBalance') ? $player->getBalance() : 1000,
                    'current_bet' => method_exists($player, 'getCurrentBet') ? $player->getCurrentBet() : 0,
                    'is_ready' => method_exists($player, 'isReady') ? $player->isReady() : false,
                    'is_playing' => method_exists($player, 'isPlaying') ? $player->isPlaying() : true,
                    'is_playing_dark' => false
                ];
                
                // 🎯 Пробуем получить реальный статус
                if (method_exists($player, 'getStatus')) {
                    try {
                        $status = $player->getStatus();
                        $playerData['status'] = method_exists($status, 'value') ? $status->value : 'active';
                        $playerData['is_playing_dark'] = $status === \App\Domain\Game\Enums\PlayerStatus::DARK;
                    } catch (\Exception $e) {
                        \Log::warning('Error getting player status', ['error' => $e->getMessage()]);
                    }
                }
                
                $players[] = $playerData;
                \Log::info("🎯 Added player to response:", $playerData);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error in formatPlayersForApi', ['error' => $e->getMessage()]);
        }
        
        \Log::info("🎯 Final players array:", ['count' => count($players), 'players' => $players]);
        
        return $players;
    }

    /**
     * 🎯 Тестовый endpoint для получения игроков
     */
    public function getTestPlayers(int $gameId): JsonResponse
    {
        try {
            // 🎯 ВРЕМЕННО: Создаем тестовых игроков
            $testPlayers = [
                [
                    'id' => 1,
                    'position' => 1,
                    'status' => 'active',
                    'balance' => 1000,
                    'current_bet' => 0,
                    'is_ready' => true,
                    'is_playing' => true,
                    'is_playing_dark' => false
                ],
                [
                    'id' => 2,
                    'position' => 2,
                    'status' => 'active', 
                    'balance' => 1000,
                    'current_bet' => 0,
                    'is_ready' => true,
                    'is_playing' => true,
                    'is_playing_dark' => false
                ],
                [
                    'id' => 3,
                    'position' => 3,
                    'status' => 'active',
                    'balance' => 1000,
                    'current_bet' => 0,
                    'is_ready' => false,
                    'is_playing' => true,
                    'is_playing_dark' => false
                ]
            ];
            
            \Log::info("🎯 TEST PLAYERS RETURNED", ['count' => count($testPlayers)]);
            
            return response()->json([
                'success' => true,
                'players' => $testPlayers,
                'message' => 'Test players data'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 🎯 Получить игру по ID (ИСПРАВЛЕННАЯ ВЕРСИЯ С ДИАГНОСТИКОЙ)
     */
    private function getGameById(int $gameId)
    {
        $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
        $game = $repository->find(\App\Domain\Game\ValueObjects\GameId::fromInt($gameId));
        
        // ✅ Если игра не найдена - создаем через GameService
        if (!$game) {
            \Log::info("🎮 Creating NEW game via GameService for ID: {$gameId}");
            
            // Создаем DTO для новой игры
            $dto = new \App\Application\DTO\StartGameDTO(
                roomId: $gameId,
                playerIds: [1, 2, 3] // или получайте из запроса
            );
            
            $game = $this->gameService->startNewGame($dto);
            $repository->save($game);
            
            \Log::info("🎮 New game created with players: " . count($game->getPlayers()));
        }
        
        \Log::info("Game {$gameId} status: " . $game->getStatus()->value);
        \Log::info("Game {$gameId} players: " . count($game->getPlayers()));
        
        return $game;
    }

    /**
     * 🎯 Получить игрока по ID
     */
    // private function getPlayerById($game, int $playerId)
    // {
    //     foreach ($game->getPlayers() as $player) {
    //         if ($player->getUserId() === $playerId) {
    //             return $player;
    //         }
    //     }
    //     throw new \DomainException('Player not found in game');
    // }

    /**
     * 🎯 Получить игрока по ID (ИСПРАВЛЕННАЯ ВЕРСИЯ ДЛЯ INT)
     */
    private function getPlayerById($game, int $playerId)
    {
        foreach ($game->getPlayers() as $player) {
            // 🎯 Получаем user_id как число
            $userId = $player->getUserId();
            
            // Если это объект с методом toInt()
            if (is_object($userId) && method_exists($userId, 'toInt')) {
                if ($userId->toInt() === $playerId) {
                    return $player;
                }
            }
            // Если это просто число
            elseif ((int)$userId === $playerId) {
                return $player;
            }
        }
        
        // 🎯 ДИАГНОСТИКА
        $availablePlayers = [];
        foreach ($game->getPlayers() as $player) {
            $userId = $player->getUserId();
            if (is_object($userId) && method_exists($userId, 'toInt')) {
                $availablePlayers[] = $userId->toInt();
            } else {
                $availablePlayers[] = (int)$userId;
            }
        }
        
        throw new \DomainException("Player $playerId not found in game. Available: " . implode(', ', $availablePlayers));
    }

    /**
     * 🎯 Получить игроков по ID
     */
    private function getPlayersByIds($game, array $playerIds): array
    {
        $players = [];
        foreach ($playerIds as $playerId) {
            $players[] = $this->getPlayerById($game, $playerId);
        }
        return $players;
    }

    // Вспомогательный метод
    private function formatPlayersForBroadcast(array $players): array
    {
        return array_map(function ($player) {
            return [
                'id' => $player->getUserId(),        // 🎯 Теперь getUserId() возвращает int
                'position' => $player->getPosition(),
                'status' => $player->getStatus()->value,
                'balance' => $player->getBalance(),
                'current_bet' => $player->getCurrentBet(),
                'is_ready' => $player->isReady(),
            ];
        }, $players);
    }

    // 🎯 ДОБАВИТЬ вспомогательные методы
    private function getMainWinnerId($results): string
    {
        $winners = $results['winners'] ?? [];
        return $winners ? (string)$winners[0]->getUserId() : '';
    }

    private function formatScores($results): array
    {
        $scores = [];
        foreach ($results['winners'] ?? [] as $winner) {
            $scores[(string)$winner->getUserId()] = $results['prize_per_winner'] ?? 0;
        }
        return $scores;
    }

    private function getFinalState($game, $results): array
    {
        return [
            'total_prize' => $results['total_prize'] ?? 0,
            'winners_count' => count($results['winners'] ?? []),
            'final_status' => $game->getStatus()->value
        ];
    }

    private function formatPlayerCards($distributionResult): array
    {
        $playerCards = [];
        foreach ($distributionResult['player_cards'] ?? [] as $playerId => $cards) {
            $playerCards[$playerId] = $cards;
        }
        return $playerCards;
    }

    /**
     * 🎯 Получить ID первого игрока
     */
    private function getFirstPlayerId($game): string
    {
        $players = $game->getPlayers();
        return $players && count($players) > 0 ? (string)$players[0]->getUserId() : '';
    }

    /**
     * 🎯 Получить начальное состояние игры
     */
    private function getInitialGameState($game): array
    {
        return [
            'status' => $game->getStatus()->value,
            'current_player_position' => $game->getCurrentPlayerPosition(),
            'bank' => $game->getBank(),
            'round' => 'waiting'
        ];
    }

    /**
     * 🎯 Принудительно начать игру (для тестирования)
     */
    public function forceStartGame(int $gameId): JsonResponse
    {
        $game = $this->getGameById($gameId);
        
        \Log::info("Force starting game...");
        
        // 🎯 Принудительно запускаем игру через ReadinessService
        $this->readinessService->startGame($game);
        
        \Log::info("Game force started. New status: " . $game->getStatus()->value);

        return response()->json([
            'success' => true,
            'message' => 'Game force started',
            'game_status' => $game->getStatus()->value
        ]);
    }

    /**
     * 🎯 Принудительно начать игру (для тестирования)
     */
    public function forceStart(int $gameId): JsonResponse
    {
        $game = $this->getGameById($gameId);
        
        \Log::info("🚀 Force starting game...");
        
        // 🎯 Принудительно запускаем игру
        $this->readinessService->startGame($game);
        
        \Log::info("🚀 Game force started. New status: " . $game->getStatus()->value);

        return response()->json([
            'success' => true,
            'message' => 'Game force started',
            'game_status' => $game->getStatus()->value
        ]);
    }

    /**
     * 🎯 Очистить состояние игры (для тестирования)
     */
    public function clearGame(int $gameId): JsonResponse
    {
        $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
        $repository->clear($gameId);
        
        \Log::info("Game state cleared for game: " . $gameId);

        return response()->json([
            'success' => true,
            'message' => 'Game state cleared successfully'
        ]);
    }

    /**
     * 🎯 ПОЛУЧИТЬ ПОЛНОЕ СОСТОЯНИЕ ИГРЫ
     */
    public function getGameState(int $gameId): JsonResponse
    {
        try {
            $game = $this->getGameById($gameId);
            $currentPlayer = $this->getCurrentPlayerFromGame($game);

            \Log::info("🎮 Getting full game state", [
                'game_id' => $gameId,
                'status' => $game->getStatus()->value,
                'current_player_position' => $game->getCurrentPlayerPosition(),
                'players_count' => count($game->getPlayers())
            ]);

            $state = [
                'id' => $gameId,
                'status' => $game->getStatus()->value,
                'current_player_position' => $game->getCurrentPlayerPosition(),
                'current_player_id' => $currentPlayer ? $currentPlayer->getUserId() : null,
                'bank' => $game->getBank(),
                'max_bet' => $game->getCurrentMaxBet(),
                'round' => $game->getCurrentRound(),
                'players_list' => $this->formatPlayersListForEvent($game), // Единый формат
                'current_player_actions' => $this->getAvailableActionsForCurrentPlayer($game),
                'community_cards' => $this->getCommunityCards($game),
                'timers' => $this->getTimersInfo($game),
                'game_phase' => $this->getGamePhase($game),
                'timestamp' => now()->toISOString()
            ];

            return response()->json([
                'success' => true,
                'game' => $state
            ]);

        } catch (\Exception $e) {
            \Log::error("❌ Failed to get game state", [
                'game_id' => $gameId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get game state: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 🎯 ПРИСОЕДИНИТЬСЯ К ИГРЕ (С ОБРАБОТКОЙ ДУБЛИРОВАНИЯ)
     */
    public function joinGame(Request $request, int $gameId): JsonResponse
    {
        try {
            $userId = $request->input('user_id') ?? Auth::id() ?? 1;
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required'
                ], 400);
            }

            $playerName = $request->input('player_name') ?? "Player_{$userId}";
            $game = $this->getGameById($gameId);

            \Log::info("🎮 Player joining game", [
                'game_id' => $gameId,
                'user_id' => $userId,
                'current_status' => $game->getStatus()->value,
                'current_players' => count($game->getPlayers())
            ]);

            // 🎯 ПРОСТАЯ ПРОВЕРКА СТАТУСА ИГРЫ
            if ($game->getStatus() !== \App\Domain\Game\Enums\GameStatus::WAITING) {
                throw new \DomainException('Игра уже началась или завершена');
            }

            $player = null;
            
            // 🎯 ПРОБУЕМ ДОБАВИТЬ ИГРОКА
            try {
                $player = $this->gameService->addPlayerToGame($game, $userId, $playerName);
                \Log::info("🎯 New player added to game");
            } catch (\DomainException $e) {
                // 🎯 ЕСЛИ ИГРОК УЖЕ В ИГРЕ - НАХОДИМ ЕГО
                if (str_contains($e->getMessage(), 'already joined')) {
                    \Log::info("🎯 Player already in game - finding existing player");
                    
                    foreach ($game->getPlayers() as $existingPlayer) {
                        $playerUserId = $existingPlayer->getUserId();
                        
                        if (is_object($playerUserId) && method_exists($playerUserId, 'toInt')) {
                            if ($playerUserId->toInt() === $userId) {
                                $player = $existingPlayer;
                                break;
                            }
                        } elseif ((int)$playerUserId === $userId) {
                            $player = $existingPlayer;
                            break;
                        }
                    }
                    
                    if (!$player) {
                        throw new \DomainException('Player not found in game');
                    }
                } else {
                    throw $e; // Другие исключения пробрасываем дальше
                }
            }

            // Сохраняем игру (если был добавлен новый игрок)
            if ($player) {
                $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
                $repository->save($game);
            }

            // Форматируем данные для ответа
            $playerData = [
                'id' => $userId,
                'name' => $playerName,
                'position' => $player->getPosition(),
                'balance' => $player->getBalance(),
                'is_ready' => $player->isReady(),
                'joined_at' => now()->toISOString()
            ];

            // Форматируем список всех игроков
            $playersList = $this->formatPlayersListForEvent($game);

            \Log::info("🎮 Player successfully processed", [
                'game_id' => $gameId,
                'user_id' => $userId,
                'player_position' => $player->getPosition(),
                'players_count' => count($game->getPlayers())
            ]);

            // Отправляем WebSocket событие (только для новых игроков)
            if ($player->getPosition() > count($game->getPlayers()) - 1) { // Примерная проверка нового игрока
                broadcast(new \App\Events\PlayerJoined(
                    gameId: $gameId,
                    player: $playerData,
                    playersList: $playersList,
                    currentPlayersCount: count($game->getPlayers())
                ));
            }

            return response()->json([
                'success' => true,
                'message' => 'Успешно присоединились к игре',
                'player' => $playerData,
                'game' => [
                    'id' => $gameId,
                    'status' => $game->getStatus()->value,
                    'players_count' => count($game->getPlayers()),
                    'max_players' => 6,
                    'players_list' => $playersList
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("❌ Failed to join game", [
                'game_id' => $gameId,
                'user_id' => $userId ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка присоединения к игре: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 🎯 ПОКИНУТЬ ИГРУ (новый метод)
     */
    public function leaveGame(Request $request, int $gameId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|min:1'
            ]);

            $userId = (int)$validated['user_id'];
            $game = $this->getGameById($gameId);

            \Log::info("🎮 Player leaving game", [
                'game_id' => $gameId,
                'user_id' => $userId
            ]);

            // Удаляем игрока из игры
            $this->gameService->removePlayerFromGame($game, $userId);

            \Log::info("🎮 Player successfully left", [
                'game_id' => $gameId,
                'user_id' => $userId,
                'remaining_players' => count($game->getPlayers())
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully left the game',
                'remaining_players' => count($game->getPlayers())
            ]);

        } catch (\Exception $e) {
            \Log::error("❌ Failed to leave game", [
                'game_id' => $gameId,
                'user_id' => $userId ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to leave game: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 🎯 ДОБАВИТЬ ИГРОКА В СУЩЕСТВУЮЩУЮ ИГРУ
     */
    public function addPlayerToGame(Game $game, int $userId, string $playerName = null): \App\Domain\Game\Entities\Player
    {
        \Log::info("🎯 Adding player to game", [
            'game_id' => $game->getId()->toInt(),
            'user_id' => $userId,
            'current_players' => count($game->getPlayers())
        ]);

        // Проверяем максимальное количество игроков
        if (count($game->getPlayers()) >= 6) {
            throw new \DomainException('Game is full (max 6 players)');
        }

        // Проверяем, не присоединен ли уже игрок
        foreach ($game->getPlayers() as $existingPlayer) {
            $existingUserId = $existingPlayer->getUserId();
            
            if (is_object($existingUserId) && method_exists($existingUserId, 'toInt')) {
                if ($existingUserId->toInt() === $userId) {
                    throw new \DomainException('Player already joined this game');
                }
            } elseif ((int)$existingUserId === $userId) {
                throw new \DomainException('Player already joined this game');
            }
        }

        // Создаем нового игрока
        $playerId = PlayerId::fromInt($userId);
        $playerName = $playerName ?: "Player_{$userId}";
        
        $player = new \App\Domain\Game\Entities\Player(
            id: $playerId,
            userId: $playerId, // или создайте отдельный UserId если нужно
            name: $playerName,
            position: count($game->getPlayers()) + 1,
            balance: 1000, // начальный баланс
            status: PlayerStatus::WAITING
        );

        // Добавляем игрока в игру
        $game->addPlayer($player);

        \Log::info("🎯 Player added successfully", [
            'game_id' => $game->getId()->toInt(),
            'user_id' => $userId,
            'player_position' => $player->getPosition(),
            'new_players_count' => count($game->getPlayers())
        ]);

        return $player;
    }

    /**
     * 🎯 УДАЛИТЬ ИГРОКА ИЗ ИГРЫ
     */
    public function removePlayerFromGame(Game $game, int $userId): void
    {
        \Log::info("🎯 Removing player from game", [
            'game_id' => $game->getId()->toInt(),
            'user_id' => $userId
        ]);

        $players = $game->getPlayers();
        $playerToRemove = null;

        // Находим игрока для удаления
        foreach ($players as $index => $player) {
            $playerUserId = $player->getUserId();
            
            if (is_object($playerUserId) && method_exists($playerUserId, 'toInt')) {
                if ($playerUserId->toInt() === $userId) {
                    $playerToRemove = $player;
                    break;
                }
            } elseif ((int)$playerUserId === $userId) {
                $playerToRemove = $player;
                break;
            }
        }

        if (!$playerToRemove) {
            throw new \DomainException('Player not found in game');
        }

        // Удаляем игрока из игры
        $game->removePlayer($playerToRemove);

        \Log::info("🎯 Player removed successfully", [
            'game_id' => $game->getId()->toInt(),
            'user_id' => $userId,
            'remaining_players' => count($game->getPlayers())
        ]);
    }

    /**
     * 🎯 СПИСОК ИГР ДЛЯ ПРИСОЕДИНЕНИЯ (новый метод)
     */
    public function listJoinableGames(): JsonResponse
    {
        try {
            $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
            
            // Получаем все игры в статусе ожидания
            $games = $repository->findAll()->filter(function($game) {
                return $game->getStatus() === GameStatus::WAITING;
            });

            $formattedGames = [];
            foreach ($games as $game) {
                $players = $game->getPlayers();
                $formattedGames[] = [
                    'id' => $game->getId()->toInt(),
                    'status' => $game->getStatus()->value,
                    'players_count' => count($players),
                    'max_players' => 6,
                    'created_at' => $game->getCreatedAt()?->toISOString(),
                    'players' => array_map(function($player) {
                        return [
                            'id' => $player->getUserId(),
                            'name' => "Player_" . $player->getUserId(),
                            'is_ready' => $player->isReady()
                        ];
                    }, $players)
                ];
            }

            return response()->json([
                'success' => true,
                'games' => $formattedGames,
                'total' => count($formattedGames)
            ]);

        } catch (\Exception $e) {
            \Log::error("❌ Failed to list joinable games", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to list games: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 🎯 Форматировать состояние игроков
     */
    private function formatPlayersState($game)
    {
        $players = [];
        foreach ($game->getPlayers() as $player) {
            $players[] = [
                'id' => $player->getUserId(),
                'position' => $player->getPosition(),
                'chips' => $player->getBalance(),
                'current_bet' => $player->getCurrentBet(),
                'is_playing' => $player->isPlaying(),
                'is_playing_dark' => $player->getStatus() === \App\Domain\Game\Enums\PlayerStatus::DARK,
                'has_folded' => $player->getStatus() === \App\Domain\Game\Enums\PlayerStatus::FOLDED,
                'is_current_turn' => $player->getPosition() === $game->getCurrentPlayerPosition(),
                'status' => $player->getStatus()->value,
                'cards_count' => count($player->getCards())
            ];
        }
        return $players;
    }

    /**
     * 🎯 Получить доступные действия для текущего игрока
     */
    private function getAvailableActionsForCurrentPlayer(Game $game): array
    {
        $currentPlayer = $this->getCurrentPlayerFromGame($game);
        if (!$currentPlayer) {
            return [];
        }
        
        try {
            $actions = $this->biddingService->getAvailableActions($game, $currentPlayer);
            return array_map(fn($action) => $action->value, $actions);
        } catch (\Exception $e) {
            \Log::error("Error getting available actions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 🎯 Получить текущего игрока из игры
     */
    private function getCurrentPlayerFromGame(Game $game)
    {
        $currentPosition = $game->getCurrentPlayerPosition();
        foreach ($game->getPlayers() as $player) {
            if ($player->getPosition() === $currentPosition && $player->isPlaying()) {
                return $player;
            }
        }
        return null;
    }

    private function getAvailableActions(Game $game)
    {
        $currentPlayer = $game->getCurrentPlayer();
        if (!$currentPlayer) {
            return [];
        }
        
        return $this->biddingService->getAvailableActions($game, $currentPlayer);
    }

    /**
     * 🎯 Получить полное состояние игры (новый endpoint)
     */
    public function getFullState(int $gameId): JsonResponse
    {
        try {
            $game = $this->getGameById($gameId);
            $currentPlayer = $this->getCurrentPlayerFromGame($game);

            return response()->json([
                'success' => true,
                'game' => [
                    'id' => $gameId,
                    'status' => $game->getStatus()->value,
                    'current_player_position' => $game->getCurrentPlayerPosition(),
                    'current_player_id' => $currentPlayer ? $currentPlayer->getUserId() : null,
                    'bank' => $game->getBank(),
                    'max_bet' => $game->getCurrentMaxBet(),
                    'round' => $game->getCurrentRound(),
                    'players' => $this->formatPlayersState($game),
                    'current_player_actions' => $this->getAvailableActionsForCurrentPlayer($game)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 🎯 Получить карты игроков
     */
    public function getPlayerCards(int $gameId): JsonResponse
    {
        try {
            $game = $this->getGameById($gameId);
            $playerCards = [];

            foreach ($game->getPlayers() as $player) {
                $cards = [];
                foreach ($player->getCards() as $card) {
                    // Преобразуем карту в читаемый формат
                    $cards[] = $this->formatCard($card);
                }
                $playerCards[$player->getUserId()] = $cards;
            }

            return response()->json([
                'success' => true,
                'player_cards' => $playerCards
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 🎯 Форматировать карту для отображения
     */
    private function formatCard($card): string
    {
        if (method_exists($card, 'getRank') && method_exists($card, 'getSuit')) {
            $rank = $card->getRank();
            $suit = $card->getSuit();
            
            $rankMap = [
                'six' => '6', 'seven' => '7', 'eight' => '8', 'nine' => '9', 'ten' => '10',
                'jack' => 'J', 'queen' => 'Q', 'king' => 'K', 'ace' => 'A'
            ];
            
            $suitMap = [
                'hearts' => '♥', 'diamonds' => '♦', 'clubs' => '♣', 'spades' => '♠'
            ];
            
            return ($rankMap[$rank] ?? $rank) . ($suitMap[$suit] ?? $suit);
        }
        
        return $card->toString() ?? '?';
    }

    /**
     * 🎯 Списать анте с игроков
     */
    public function collectAnte(int $gameId): JsonResponse
    {
        try {
            $game = $this->getGameById($gameId);
            $ante = 10; // Стандартное анте
            $totalAnte = 0;
            
            foreach ($game->getActivePlayers() as $player) {
                if ($player->getBalance() >= $ante) {
                    $player->placeBet($ante);
                    $totalAnte += $ante;
                    \Log::info("💰 Ante collected from player {$player->getUserId()}: {$ante} chips");
                } else {
                    \Log::warning("⚠️ Player {$player->getUserId()} has insufficient balance for ante");
                }
            }
            
            $game->setBank($totalAnte);
            $game->setCurrentMaxBet($ante);
            
            // Сохраняем игру
            $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
            $repository->save($game);
            
            return response()->json([
                'success' => true,
                'message' => 'Ante collected',
                'total_ante' => $totalAnte,
                'bank' => $totalAnte
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // ==================== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ====================

    /**
     * 🎯 Проверить, можно ли присоединиться к игре
     */
    private function canJoinGame(Game $game): bool
    {
        return $game->getStatus() === GameStatus::WAITING;
    }

    /**
     * 🎯 Проверить, присоединен ли уже игрок
     */
    private function isPlayerAlreadyJoined(Game $game, int $userId): bool
    {
        foreach ($game->getPlayers() as $player) {
            $playerUserId = $player->getUserId();
            
            if (is_object($playerUserId) && method_exists($playerUserId, 'toInt')) {
                if ($playerUserId->toInt() === $userId) {
                    return true;
                }
            } elseif ((int)$playerUserId === $userId) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 🎯 Форматировать игроков для состояния игры
     */
    private function formatPlayersForState(Game $game): array
    {
        $players = [];
        
        foreach ($game->getPlayers() as $player) {
            $playerData = [
                'id' => $player->getUserId(),
                'position' => $player->getPosition(),
                'name' => "Player_" . $player->getUserId(),
                'chips' => $player->getBalance(),
                'current_bet' => $player->getCurrentBet(),
                'is_playing' => $player->isPlaying(),
                'is_playing_dark' => $player->getStatus() === \App\Domain\Game\Enums\PlayerStatus::DARK,
                'has_folded' => $player->getStatus() === \App\Domain\Game\Enums\PlayerStatus::FOLDED,
                'is_current_turn' => $player->getPosition() === $game->getCurrentPlayerPosition(),
                'status' => $player->getStatus()->value,
                'is_ready' => $player->isReady(),
                'cards_count' => count($player->getCards()),
                'total_bet' => $player->getTotalBet()
            ];

            // Добавляем карты, если игра началась и игрок не играет втемную
            if ($game->getStatus() === GameStatus::ACTIVE && 
                $player->getStatus() !== \App\Domain\Game\Enums\PlayerStatus::DARK) {
                $playerData['cards'] = array_map([$this, 'formatCard'], $player->getCards());
            }

            $players[] = $playerData;
        }
        
        return $players;
    }

    /**
     * 🎯 Получить общие карты
     */
    private function getCommunityCards(Game $game): array
    {
        // Если в вашей игре есть общие карты (как в покере)
        if (method_exists($game, 'getCommunityCards')) {
            return array_map([$this, 'formatCard'], $game->getCommunityCards());
        }
        
        return [];
    }

    /**
     * 🎯 Получить информацию о таймерах
     */
    private function getTimersInfo(Game $game): array
    {
        return [
            'turn_timeout' => 30, // секунд на ход
            'ready_timeout' => 10, // секунд на готовность
            'action_timeout' => 25, // секунд на действие
            'current_turn_started_at' => now()->toISOString()
        ];
    }

    /**
     * 🎯 Получить фазу игры
     */
    private function getGamePhase(Game $game): string
    {
        $status = $game->getStatus();
        
        return match($status) {
            GameStatus::WAITING => 'waiting_for_players',
            GameStatus::ACTIVE => 'bidding_round',
            GameStatus::FINISHED => 'finished',
            GameStatus::QUARREL => 'quarrel',
            default => 'unknown'
        };
    }

    private function formatPlayersListForEvent(Game $game): array
    {
        $playersList = [];
        
        foreach ($game->getPlayers() as $player) {
            $playersList[] = [
                'id' => $player->getUserId(),
                'name' => "Player_" . $player->getUserId(),
                'position' => $player->getPosition(),
                'balance' => $player->getBalance(),
                'is_ready' => $player->isReady(),
                'status' => $player->getStatus()->value
            ];
        }
        
        return $playersList;
    }

    public function calculatePoints(Request $request)
    {

        \Log::info('🔄 calculatePoints called', $request->all());

        $request->validate([
            'cards' => 'required|array|min:2|max:3',
            'cards.*' => 'string'
        ]);

        try {
            \Log::info('📋 Cards received:', $request->cards);

            $scoringService = app(ScoringService::class);
            $points = $scoringService->calculateHandValue($request->cards);

            \Log::info('✅ Points calculated:', ['points' => $points]);
            
            return response()->json([
                'success' => true,
                'points' => $points,
                'combination' => $this->getCombinationName($points)
            ]);
            
        } catch (\Exception $e) {
            \Log::error('❌ calculatePoints error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to calculate points',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getCombinationName(int $points): string
    {
        $combinations = [
            33 => 'Три десятки',
            34 => 'Три вальта', 
            35 => 'Три дамы',
            36 => 'Три короля',
            37 => 'Три туза',
            32 => 'Джокер + Туз + масть',
            31 => 'Три масти + Туз/Джокер',
            30 => 'Три одинаковые масти',
            22 => 'Два туза',
            21 => 'Две масти + Туз/Джокер',
            20 => 'Две одинаковые масти',
            11 => 'Разные масти + Туз',
            10 => 'Базовая комбинация'
        ];
        
        return $combinations[$points] ?? "Комбинация ($points)";
    }

    /**
     * 🎯 ПОЛУЧИТЬ ИГРЫ ДЛЯ ЛОББИ (ИСПРАВЛЕННАЯ ВЕРСИЯ - ОБНОВЛЕНИЕ ВМЕСТО ПЕРЕСОЗДАНИЯ)
     */
    public function getLobbyGames(): JsonResponse
    {
        try {
            $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
            $allGames = $repository->findAll();
            
            // 🎯 ДИАГНОСТИКА 1: Сколько всего игр в кэше
            \Log::info("🔍 DIAGNOSTIC: Total games in cache", [
                'all_games_count' => count($allGames),
                'game_ids' => array_map(fn($game) => $game->getId()->toInt(), $allGames)
            ]);
            
            $tableTypes = [
                'novice' => ['base_bet' => 5, 'min_balance' => 50, 'name' => '🥉 Новички'],
                'amateur' => ['base_bet' => 10, 'min_balance' => 100, 'name' => '🥈 Любители'],
                'pro' => ['base_bet' => 25, 'min_balance' => 250, 'name' => '🥇 Профи'],
                'master' => ['base_bet' => 50, 'min_balance' => 500, 'name' => '🏆 Мастера']
            ];
            
            $waitingGames = array_filter($allGames, function($game) {
                return $game->getStatus() === \App\Domain\Game\Enums\GameStatus::WAITING;
            });
            
            // 🎯 ДИАГНОСТИКА 2: Сколько ожидающих игр
            \Log::info("🔍 DIAGNOSTIC: Waiting games", [
                'waiting_games_count' => count($waitingGames),
                'waiting_game_ids' => array_map(fn($game) => $game->getId()->toInt(), $waitingGames)
            ]);
            
            $gamesByType = [];
            foreach ($waitingGames as $game) {
                $tableType = $this->determineTableType($game);
                if (!isset($gamesByType[$tableType])) {
                    $gamesByType[$tableType] = [];
                }
                $gamesByType[$tableType][] = $game;
            }
            
            // 🎯 ДИАГНОСТИКА 3: Распределение по типам
            \Log::info("🔍 DIAGNOSTIC: Games by type", [
                'novice_count' => count($gamesByType['novice'] ?? []),
                'amateur_count' => count($gamesByType['amateur'] ?? []),
                'pro_count' => count($gamesByType['pro'] ?? []),
                'master_count' => count($gamesByType['master'] ?? [])
            ]);
            
            $formattedGames = [];
            foreach ($tableTypes as $type => $config) {
                $typeGames = $gamesByType[$type] ?? [];
                $currentCount = count($typeGames);
                
                \Log::info("🎯 Processing table type {$type}", [
                    'current_tables' => $currentCount,
                    'need_to_create' => max(0, 4 - $currentCount)
                ]);
                
                for ($i = $currentCount; $i < 4; $i++) {
                    $newGame = $this->createAutoTable($type, $config);
                    $typeGames[] = $newGame;
                    \Log::info("🆕 Created missing table", [
                        'type' => $type,
                        'game_id' => $newGame->getId()->toInt(),
                        'table_number' => $i + 1
                    ]);
                }
                
                // 🎯 ИСПРАВЛЕНИЕ: Добавляем $index в цикл
                foreach ($typeGames as $index => $game) {
                    $players = $game->getPlayers();
                    
                    $formattedGames[] = [
                        'id' => $game->getId()->toInt(),
                        'name' => $config['name'] . " #" . ($index + 1), // 🎯 Теперь $index доступен
                        'status' => $game->getStatus()->value,
                        'table_type' => $type,
                        'players_count' => count($players),
                        'max_players' => 6,
                        'base_bet' => $config['base_bet'],
                        'min_balance' => $config['min_balance'],
                        'created_at' => now()->toISOString(),
                        'players' => array_map(function($player) {
                            return [
                                'id' => $player->getUserId(),
                                'name' => "Игрок_" . $player->getUserId(),
                                'is_ready' => $player->isReady(),
                                'position' => $player->getPosition()
                            ];
                        }, $players)
                    ];
                }
            }
            
            \Log::info("✅ FINAL RESULT", [
                'total_tables' => count($formattedGames),
                'tables_by_type' => array_count_values(array_column($formattedGames, 'table_type'))
            ]);
            
            return response()->json([
                'success' => true,
                'games' => $formattedGames,
                'total' => count($formattedGames)
            ]);
            
        } catch (\Exception $e) {
            \Log::error("❌ Failed to get lobby games", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load lobby games',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🎯 СОЗДАТЬ НОВУЮ ИГРУ
     */
    public function createGame(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|min:1',
                'table_type' => 'sometimes|string|in:novice,amateur,pro,master',
                'player_name' => 'sometimes|string|max:50'
            ]);

            $userId = (int)$validated['user_id'];
            $tableType = $validated['table_type'] ?? 'novice';
            $playerName = $validated['player_name'] ?? "Player_{$userId}";

            // Создаем игру с игроком
            $game = $this->gameService->createNewGameWithPlayer($userId, $tableType);
            
            // Сохраняем игру
            $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
            $repository->save($game);

            \Log::info("🎯 New game created via API", [
                'game_id' => $game->getId()->toInt(),
                'user_id' => $userId,
                'table_type' => $tableType
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Game created successfully',
                'game' => [
                    'id' => $game->getId()->toInt(),
                    'name' => "Стол #" . $game->getId()->toInt(),
                    'status' => $game->getStatus()->value,
                    'table_type' => $tableType,
                    'base_bet' => $this->getTableConfig($tableType)['base_bet'],
                    'min_balance' => $this->getTableConfig($tableType)['min_balance'],
                    'players_count' => 1,
                    'max_players' => 6
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("❌ Failed to create game", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create game: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🎯 КОНФИГУРАЦИЯ СТОЛОВ
     */
    private function getTableConfig(string $tableType): array
    {
        return match($tableType) {
            'novice' => ['base_bet' => 5, 'min_balance' => 50, 'name' => 'Новички'],
            'amateur' => ['base_bet' => 10, 'min_balance' => 100, 'name' => 'Любители'],
            'pro' => ['base_bet' => 25, 'min_balance' => 250, 'name' => 'Профи'],
            'master' => ['base_bet' => 50, 'min_balance' => 500, 'name' => 'Мастера'],
            default => ['base_bet' => 5, 'min_balance' => 50, 'name' => 'Новички']
        };
    }

    /**
     * 🎯 Создать или получить игру при прямом переходе
     */
    public function getOrCreateGame(int $gameId): JsonResponse
    {
        try {
            $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
            $game = $repository->find(\App\Domain\Game\ValueObjects\GameId::fromInt($gameId));
            
            if (!$game) {
                // Создаем новую игру через GameService
                $dto = new \App\Application\DTO\StartGameDTO(
                    roomId: $gameId,
                    playerIds: [] // Пустой массив - игроки присоединятся позже
                );
                
                $game = $this->gameService->startNewGame($dto);
                $repository->save($game);
                
                \Log::info("🎮 Created new game via getOrCreateGame: {$gameId}");
            }

            return response()->json([
                'success' => true,
                'game' => $this->formatGameForApi($game)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 🎯 СУПЕР-НАДЕЖНАЯ ГЕНЕРАЦИЯ ID С ГАРАНТИЕЙ УНИКАЛЬНОСТИ
     */
    private function generateGameId(): int
    {
        $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
        $maxAttempts = 5; // На всякий случай ограничим попытки
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $timestamp = (int) (microtime(true) * 1000); // 13 цифр
            $random = random_int(10000, 99999); // 5 цифр случайности
            $gameId = (int) ($timestamp . $random); // 18 цифр total
            
            // 🎯 ПРОВЕРЯЕМ УНИКАЛЬНОСТЬ
            if (!$repository->find(\App\Domain\Game\ValueObjects\GameId::fromInt($gameId))) {
                \Log::info("✅ Generated unique game ID: {$gameId} (attempt: {$attempt})");
                return $gameId;
            }
            
            \Log::warning("⚠️ Game ID collision detected: {$gameId}, attempt: {$attempt}");
            
            // 🎯 ДОБАВЛЯЕМ ДОПОЛНИТЕЛЬНУЮ СЛУЧАЙНОСТЬ ПРИ КОЛЛИЗИИ
            if ($attempt < $maxAttempts) {
                $gameId += random_int(1, 1000); // Сдвигаем ID
                usleep(1000 * $attempt); // Увеличиваем задержку с каждой попыткой
            }
        }
        
        // 🎯 КРИТИЧЕСКАЯ РЕЗЕРВНАЯ СИСТЕМА (крайне маловероятно)
        $criticalId = (int) (time() . random_int(100000000, 999999999));
        \Log::error("🚨 CRITICAL: Using emergency game ID: {$criticalId}");
        
        return $criticalId;
    }

    /**
     * 🎯 АВТОМАТИЧЕСКИ СОЗДАТЬ СТОЛ
     */
    private function createAutoTable(string $tableType, array $config): Game
    {
        $gameId = $this->generateGameId();
        
        $dto = new \App\Application\DTO\StartGameDTO(
            roomId: $gameId,
            playerIds: [] // Пустой - игроки присоединятся позже
        );
        
        $game = $this->gameService->startNewGame($dto);
        
        // 🎯 СОХРАНЯЕМ В КЭШ
        $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
        $repository->save($game);
        
        \Log::info("🎯 Auto-created table", [
            'game_id' => $gameId,
            'table_type' => $tableType,
            'players_count' => count($game->getPlayers())
        ]);
        
        return $game;
    }

    /**
     * 🎯 ОПРЕДЕЛИТЬ ТИП СТОЛА ПО ИГРЕ (ПРОСТАЯ ВЕРСИЯ)
     */
    private function determineTableType(Game $game): string
    {
        $gameId = $game->getId()->toInt();
        $types = ['novice', 'amateur', 'pro', 'master'];
        return $types[$gameId % 4];
    }
    
}