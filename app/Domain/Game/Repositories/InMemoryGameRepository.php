<?php

namespace App\Domain\Game\Repositories;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\Enums\PlayerStatus;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\GameMode;

class InMemoryGameRepository
{
    private static $instance = null;
    private $games = [];

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function find(GameId $gameId): ?Game
    {
        $id = $gameId->toInt();
        
        if (!isset($this->games[$id])) {
            $this->games[$id] = $this->createNewGame($id);
            \Log::info("Created NEW game {$id}");
        } else {
            \Log::info("Returning EXISTING game {$id}");
        }
        
        return $this->games[$id];
    }

    public function save(Game $game): void
    {
        $id = $game->getId()->toInt();
        $this->games[$id] = $game;
        \Log::info("Saved game {$id} to repository");
    }

    private function createNewGame(int $gameId): Game
    {
        $game = new Game(
            GameId::fromInt($gameId),
            GameStatus::WAITING,
            $gameId,
            GameMode::OPEN
        );

        // Добавляем тестовых игроков
        $players = [
            new Player(PlayerId::fromInt(1), 1, 1, PlayerStatus::ACTIVE, 1000),
            new Player(PlayerId::fromInt(2), 2, 2, PlayerStatus::ACTIVE, 1000),
            new Player(PlayerId::fromInt(3), 3, 3, PlayerStatus::ACTIVE, 1000)
        ];

        // Устанавливаем игроков через рефлексию
        $reflection = new \ReflectionClass($game);
        $playersProperty = $reflection->getProperty('players');
        $playersProperty->setAccessible(true);
        $playersProperty->setValue($game, $players);

        return $game;
    }

    public function clear(): void
    {
        $this->games = [];
        \Log::info("Repository cleared");
    }

    // 🎯 Запрещаем клонирование
    private function __clone() {}
}