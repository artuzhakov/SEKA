<?php
// app/Domain/Game/Repositories/GameRepositoryInterface.php

namespace App\Domain\Game\Repositories;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\ValueObjects\GameId;

interface GameRepositoryInterface
{
    public function find(GameId $gameId): ?Game;
    public function findById(int $gameId): ?Game;  // 🎯 ДОБАВЛЯЕМ ДЛЯ СОВМЕСТИМОСТИ
    public function save(Game $game): void;
    public function delete(int $gameId): void;     // 🎯 ДОБАВЛЯЕМ
    public function findActiveGames(): array;      // 🎯 ДОБАВЛЯЕМ
    public function clear(): void;                 // 🎯 ДОБАВЛЯЕМ
}