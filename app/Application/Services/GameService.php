<?php
// app/Application/Services/GameService.php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\GameMode;
use App\Domain\Game\Enums\PlayerStatus;
use App\Domain\Game\Rules\ScoringRule;
use App\Application\DTO\StartGameDTO;

class GameService
{
    public function __construct(
        private ?ScoringRule $scoringRule = null
    ) {
        $this->scoringRule = $scoringRule ?? new ScoringRule();
    }

    /**
     * 🎯 Начать новую игру
     */
    public function startNewGame(StartGameDTO $dto): Game
    {
        $game = new Game(
            GameId::fromInt(1), // 🎯 В реальности генерируем ID
            GameStatus::WAITING,
            $dto->roomId,
            GameMode::OPEN
        );

        // 🎯 ИСПРАВЛЕНИЕ: создаем игроков со статусом ACTIVE
        foreach ($dto->playerIds as $index => $playerId) {
            $player = new Player(
                \App\Domain\Game\ValueObjects\PlayerId::fromInt($playerId),
                $playerId,
                $index + 1, // позиция за столом
                \App\Domain\Game\Enums\PlayerStatus::ACTIVE, // 🎯 ИСПРАВЛЕНО: ACTIVE вместо WAITING
                1000 // начальный баланс
            );
            $game->addPlayer($player);
        }

        \Log::info("GameService: Created game with " . count($game->getPlayers()) . " ACTIVE players");

        return $game;
    }

    /**
     * 🎯 ДОБАВИТЬ ИГРОКА В СУЩЕСТВУЮЩУЮ ИГРУ
     */
    public function addPlayerToGame(Game $game, int $userId, string $playerName = null): Player
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
            if ($existingPlayer->getUserId() === $userId) {
                throw new \DomainException('Player already joined this game');
            }
        }

        // Создаем нового игрока
        $playerId = PlayerId::fromInt($userId);
        $position = count($game->getPlayers()) + 1;
        $initialBalance = 1000; // начальный баланс

        $player = new Player(
            id: $playerId,
            userId: $userId,
            position: $position,
            status: PlayerStatus::WAITING, // 🎯 Новые игроки в статусе WAITING
            balance: $initialBalance
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
        $playerIndex = null;

        // Находим игрока для удаления
        foreach ($players as $index => $player) {
            if ($player->getUserId() === $userId) {
                $playerToRemove = $player;
                $playerIndex = $index;
                break;
            }
        }

        if (!$playerToRemove) {
            throw new \DomainException('Player not found in game');
        }

        // Удаляем игрока из массива
        array_splice($players, $playerIndex, 1);
        
        // Обновляем список игроков в игре через рефлексию
        $this->setGamePlayers($game, $players);

        \Log::info("🎯 Player removed successfully", [
            'game_id' => $game->getId()->toInt(),
            'user_id' => $userId,
            'remaining_players' => count($game->getPlayers())
        ]);
    }

    /**
     * 🎯 ВСПОМОГАТЕЛЬНЫЙ МЕТОД: Установить игроков в игру
     */
    private function setGamePlayers(Game $game, array $players): void
    {
        // Используем рефлексию для установки players
        $reflection = new \ReflectionClass($game);
        $property = $reflection->getProperty('players');
        $property->setAccessible(true);
        $property->setValue($game, $players);
    }

    /**
     * 🎯 СОЗДАТЬ НОВУЮ ИГРУ С ОДНИМ ИГРОКОМ (для лобби)
     */
    public function createNewGameWithPlayer(int $userId, string $tableType = 'novice'): Game
    {
        \Log::info("🎯 Creating new game with player", [
            'user_id' => $userId,
            'table_type' => $tableType
        ]);

        // Конфигурация столов
        $tableConfig = $this->getTableConfig($tableType);
        $baseBet = $tableConfig['base_bet'];

        // Создаем ID игры
        $gameId = $this->generateGameId();

        // 🎯 СОЗДАЕМ ИГРУ С ПРАВИЛЬНОЙ БАЗОВОЙ СТАВКОЙ
        $game = new Game(
            GameId::fromInt($gameId),
            GameStatus::WAITING,
            $gameId,
            GameMode::OPEN,
            $baseBet // 🎯 Устанавливаем базовую ставку
        );

        // Добавляем создателя игры (если userId не 0)
        if ($userId > 0) {
            $player = $this->addPlayerToGame($game, $userId, "Player_{$userId}");
        }

        \Log::info("🎯 New game created successfully", [
            'game_id' => $gameId,
            'user_id' => $userId,
            'table_type' => $tableType,
            'base_bet' => $baseBet,
            'players_count' => count($game->getPlayers())
        ]);

        return $game;
    }

    /**
     * 🎯 КОНФИГУРАЦИЯ СТОЛОВ
     */
    public function getTableConfig(string $tableType): array
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
     * 🎯 Определить победителей игры
     */
    public function determineWinners(Game $game): array
    {
        $winners = [];
        $highestScore = 0;

        foreach ($game->getActivePlayers() as $player) {
            $score = $this->scoringRule->calculateScore($player->getCards());
            
            if ($score > $highestScore) {
                $highestScore = $score;
                $winners = [$player];
            } elseif ($score === $highestScore) {
                $winners[] = $player;
            }
        }

        return $winners;
    }

    /**
     * 🎯 Может ли игра начаться (достаточно игроков)
     */
    public function canGameStart(Game $game): bool
    {
        $readyPlayers = array_filter($game->getPlayers(), function($player) {
            return $player->isReady() && $player->getStatus() === PlayerStatus::ACTIVE;
        });
        
        return count($readyPlayers) >= 2; // 🎯 Минимум 2 готовых игрока
    }

    /**
     * 🎯 Завершить игру и определить результаты
     */
    public function finishGame(Game $game): array
    {
        $winners = $this->determineWinners($game);
        
        // 🎯 Логика распределения банка
        $bank = $game->getBank();
        $winnerCount = count($winners);
        $prizePerWinner = $winnerCount > 0 ? (int)($bank / $winnerCount) : 0;

        return [
            'winners' => $winners,
            'prize_per_winner' => $prizePerWinner,
            'total_prize' => $bank
        ];
    }

    /**
     * 🎯 ГЕНЕРАЦИЯ УНИКАЛЬНОГО ID ИГРЫ
     */
    public function generateGameId(): int
    {
        $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
        
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $gameId = random_int(100000, 999999);
            
            $existingGame = $repository->find(\App\Domain\Game\ValueObjects\GameId::fromInt($gameId));
            if (!$existingGame) {
                return $gameId;
            }
            usleep(10000);
        }
        
        return (int) (microtime(true) * 1000) % 1000000;
    }

}