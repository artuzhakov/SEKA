<?php
// app/Http/Controllers/GameController.php

declare(strict_types=1);

namespace App\Http\Controllers;

// use App\Domain\Game\Repositories\TestGameRepository;
use App\Domain\Game\Repositories\InMemoryGameRepository;
use App\Application\Services\GameService;
use App\Application\Services\DistributionService;
use App\Application\Services\BiddingService;
use App\Application\Services\QuarrelService;
use App\Application\Services\ReadinessService;
use App\Application\DTO\StartGameDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Events\GameStarted;
use App\Events\PlayerReady;
use App\Domain\Game\Enums\GameStatus;
use App\Events\PlayerActionTaken;
use App\Events\CardsDistributed;
use App\Events\GameFinished;
use Illuminate\Support\Facades\Auth;

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
        
        // 🎯 ДИАГНОСТИКА: выведем текущий статус игры
        \Log::info("Game status before distribution: " . $game->getStatus()->value);
        \Log::info("Ready players count: " . $this->readinessService->getReadyPlayersCount($game));
        \Log::info("Total players: " . count($game->getPlayers()));
        
        // 🎯 Проверяем что игра активна
        if ($game->getStatus() !== \App\Domain\Game\Enums\GameStatus::ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'Game is not active. Current status: ' . $game->getStatus()->value,
                'current_status' => $game->getStatus()->value,
                'ready_players' => $this->readinessService->getReadyPlayersCount($game)
            ], 400);
        }
        
        $distributionResult = $this->distributionService->distributeCards($game);

        // 🎯 Отправляем событие CardsDistributed
        broadcast(new CardsDistributed(
            gameId: $gameId,
            playerCards: $this->formatPlayerCards($distributionResult),
            communityCards: $distributionResult['community_cards'] ?? [],
            round: $distributionResult['round'] ?? 'preflop'
        ));

        return response()->json([
            'success' => true,
            'message' => 'Cards distributed',
            'game_status' => $game->getStatus()->value
        ]);
    }

    /**
     * 🎯 Действие игрока (ставка, пас, вскрытие и т.д.)
     */
    public function playerAction(Request $request, int $gameId): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|integer',
            'action' => 'required|string',
            'bet_amount' => 'sometimes|integer|min:0'
        ]);

        $game = $this->getGameById($gameId);
        $player = $this->getPlayerById($game, (int)$validated['player_id']);
        $action = \App\Domain\Game\Enums\PlayerAction::from($validated['action']);

        $this->biddingService->processPlayerAction(
            $game, 
            $player, 
            $action, 
            $validated['bet_amount'] ?? null
        );

        broadcast(new PlayerActionTaken(
            gameId: $gameId,
            playerId: $player->getUserId(),
            action: $action->value,
            betAmount: $validated['bet_amount'] ?? null,
            newPlayerPosition: $game->getCurrentPlayerPosition(),
            bank: $game->getBank()
        ));

        return response()->json([
            'success' => true,
            'action' => $action->value,
            'player_id' => $player->getUserId(),
            'current_player_position' => $game->getCurrentPlayerPosition(),
            'game_status' => $game->getStatus()->value
        ]);
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

        return response()->json([
            'success' => true,
            'game_id' => $gameId,
            'status' => $game->getStatus()->value,
            'current_player_position' => $game->getCurrentPlayerPosition(),
            'ready_players_count' => $this->readinessService->getReadyPlayersCount($game),
            'total_players' => count($game->getPlayers()),
            'bank' => $game->getBank()
        ]);
    }

    /**
     * 🎯 Получить игру по ID (РЕАЛЬНАЯ РЕАЛИЗАЦИЯ)
     */
    // private function getGameById(int $gameId)
    // {
    //     // 🎯 Временное решение - создаем реальную игру с игроками
    //     // В реальности здесь будет GameRepository::find($gameId)
        
    //     $game = new \App\Domain\Game\Entities\Game(
    //         \App\Domain\Game\ValueObjects\GameId::fromInt($gameId),
    //         \App\Domain\Game\Enums\GameStatus::WAITING,
    //         $gameId,
    //         \App\Domain\Game\Enums\GameMode::OPEN
    //     );
        
    //     // 🎯 Добавляем тестовых игроков
    //     $players = [
    //         new \App\Domain\Game\Entities\Player(
    //             \App\Domain\Game\ValueObjects\PlayerId::fromInt(1),
    //             \App\Domain\Game\ValueObjects\UserId::fromInt(1),
    //             \App\Domain\Game\Enums\PlayerStatus::WAITING,
    //             1000,
    //             1
    //         ),
    //         new \App\Domain\Game\Entities\Player(
    //             \App\Domain\Game\ValueObjects\PlayerId::fromInt(2), 
    //             \App\Domain\Game\ValueObjects\UserId::fromInt(2),
    //             \App\Domain\Game\Enums\PlayerStatus::WAITING,
    //             1000,
    //             2
    //         ),
    //         new \App\Domain\Game\Entities\Player(
    //             \App\Domain\Game\ValueObjects\PlayerId::fromInt(3),
    //             \App\Domain\Game\ValueObjects\UserId::fromInt(3),
    //             \App\Domain\Game\Enums\PlayerStatus::WAITING,
    //             1000, 
    //             3
    //         )
    //     ];
        
    //     // 🎯 Используем рефлексию чтобы установить игроков (временное решение)
    //     $reflection = new \ReflectionClass($game);
    //     $playersProperty = $reflection->getProperty('players');
    //     $playersProperty->setAccessible(true);
    //     $playersProperty->setValue($game, $players);

    //     return $this->gameRepository->find(
    //         \App\Domain\Game\ValueObjects\GameId::fromInt($gameId)
    //     );
        
    //     // return $game;
    // }

    /**
     * 🎯 Получить игру по ID (ИСПРАВЛЕННАЯ ВЕРСИЯ С ДИАГНОСТИКОЙ)
     */
    private function getGameById(int $gameId)
    {
        $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
        $game = $repository->find(\App\Domain\Game\ValueObjects\GameId::fromInt($gameId));
        
        \Log::info("Game {$gameId} status: " . $game->getStatus()->value);
        \Log::info("Game {$gameId} players: " . count($game->getPlayers()));
        
        // 🎯 Детальная информация об игроках
        foreach ($game->getPlayers() as $player) {
            \Log::info("Player in game: {$player->getUserId()}, status: {$player->getStatus()->value}");
        }
        
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

}