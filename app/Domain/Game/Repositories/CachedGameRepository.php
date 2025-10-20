<?php

namespace App\Domain\Game\Repositories;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\Enums\PlayerStatus;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\GameMode;
use Illuminate\Support\Facades\Cache;

class CachedGameRepository
{
    private const CACHE_KEY_PREFIX = 'game_';
    private const CACHE_TTL = 3600; // 1 час

    public function find(GameId $gameId): ?Game
    {
        $id = $gameId->toInt();
        $cacheKey = self::CACHE_KEY_PREFIX . $id;
        
        \Log::info("Looking for game {$id} in cache...");
        
        // 🎯 Пытаемся получить игру из кэша
        $game = Cache::get($cacheKey);
        
        if ($game) {
            \Log::info("✅ Found EXISTING game {$id} in cache");
            return $game;
        }
        
        \Log::info("❌ Game {$id} not found in cache, creating NEW game");
        $newGame = $this->createNewGame($id);
        $this->save($newGame);
        
        return $newGame;
    }

    public function save(Game $game): void
    {
        $id = $game->getId()->toInt();
        $cacheKey = self::CACHE_KEY_PREFIX . $id;
        
        Cache::put($cacheKey, $game, self::CACHE_TTL);
        \Log::info("💾 Saved game {$id} to cache");
    }

    private function createNewGame(int $gameId): Game
    {
        // 🎯 Создаем ПУСТУЮ игру без игроков
        $game = new Game(
            GameId::fromInt($gameId),
            GameStatus::WAITING,
            $gameId,
            GameMode::OPEN
        );

        // 🎯 НЕ добавляем игроков здесь - они будут добавлены через GameService
        \Log::info("Created EMPTY game {$gameId}");

        return $game;
    }

    public function clear(int $gameId): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $gameId;
        Cache::forget($cacheKey);
        \Log::info("🗑️ Cleared game {$gameId} from cache");
    }

    public function clearAll(): void
    {
        // 🎯 Очищаем все игры (для тестирования)
        for ($i = 1; $i <= 10; $i++) {
            Cache::forget(self::CACHE_KEY_PREFIX . $i);
        }
        \Log::info("🧹 Cleared ALL games from cache");
    }
}