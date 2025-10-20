<?php

namespace App\Domain\Game\Repositories;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\GameMode;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\ValueObjects\UserId;
use App\Domain\Game\Enums\PlayerStatus;

class TestGameRepository
{
    private $games = [];

    public function find(GameId $gameId): ?Game
    {
        $id = $gameId->toInt();
        
        if (!isset($this->games[$id])) {
            $this->games[$id] = $this->createTestGame($id);
        }
        
        return $this->games[$id];
    }

    public function save(Game $game): void
    {
        $this->games[$game->getId()->toInt()] = $game;
    }

    private function createTestGame(int $gameId): Game
    {
        $game = new Game(
            GameId::fromInt($gameId),
            GameStatus::WAITING,
            $gameId,
            GameMode::OPEN
        );

        // Добавляем тестовых игроков
        $players = [
            new Player(PlayerId::fromInt(1), UserId::fromInt(1), PlayerStatus::WAITING, 1000, 1),
            new Player(PlayerId::fromInt(2), UserId::fromInt(2), PlayerStatus::WAITING, 1000, 2),
            new Player(PlayerId::fromInt(3), UserId::fromInt(3), PlayerStatus::WAITING, 1000, 3)
        ];

        // Устанавливаем игроков через рефлексию
        $reflection = new \ReflectionClass($game);
        $playersProperty = $reflection->getProperty('players');
        $playersProperty->setAccessible(true);
        $playersProperty->setValue($game, $players);

        return $game;
    }
}