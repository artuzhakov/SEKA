<?php

namespace App\Domain\Game\Repositories;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\ValueObjects\GameId;
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
        
        // ✅ ИСПРАВЛЕНИЕ: возвращаем null если игра не найдена
        \Log::info("❌ Game {$id} NOT found in cache - returning null");
        return null;
    }

    public function save(Game $game): void
    {
        $id = $game->getId()->toInt();
        $cacheKey = self::CACHE_KEY_PREFIX . $id;
        
        Cache::put($cacheKey, $game, self::CACHE_TTL);
        \Log::info("💾 Saved game {$id} to cache");
    }

    /**
     * 🎯 СОЗДАТЬ НОВУЮ ИГРУ (с правильной базовой ставкой)
     */
    public function createNewGame(int $gameId, int $baseBet = 5): Game
    {
        // 🎯 ПРАВИЛЬНЫЕ ИМПОРТЫ
        $game = new Game(
            \App\Domain\Game\ValueObjects\GameId::fromInt($gameId),
            \App\Domain\Game\Enums\GameStatus::WAITING,
            $gameId,
            \App\Domain\Game\Enums\GameMode::OPEN,
            $baseBet // 🎯 Устанавливаем правильную базовую ставку
        );

        \Log::info("🎯 Created NEW game {$gameId} with base bet: {$baseBet}");
        $this->save($game);

        return $game;
    }

    public function clear(int $gameId): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $gameId;
        Cache::forget($cacheKey);
        \Log::info("🗑️ Cleared game {$gameId} from cache");
    }

    /**
     * 🎯 Получить все игры из кэша
     */
    public function findAll(): array
    {
        $games = [];
        
        // 🎯 Ищем игры в диапазоне 1-100
        for ($i = 1; $i <= 100; $i++) {
            $cacheKey = self::CACHE_KEY_PREFIX . $i;
            $game = Cache::get($cacheKey);
            
            if ($game) {
                $games[] = $game;
            }
        }
        
        \Log::info("🎯 TOTAL GAMES IN CACHE: " . count($games));
        
        return $games;
    }
    
    /**
     * 🎯 Найти игру по ID или создать если не существует
     */
    public function findOrCreate(int $gameId, int $baseBet = 5): Game
    {
        $game = $this->find(GameId::fromInt($gameId));
        
        if (!$game) {
            $game = $this->createNewGame($gameId, $baseBet);
        }
        
        return $game;
    }

    /**
     * 🎯 Сохранить список ID игр для лобби
     */
    public function saveLobbyGameIds(array $gameIds): void
    {
        Cache::put('lobby_game_ids', $gameIds, self::CACHE_TTL);
        \Log::info("💾 Saved lobby game IDs: " . count($gameIds));
    }

    /**
     * 🎯 Получить список ID игр для лобби
     */
    public function getLobbyGameIds(): array
    {
        $gameIds = Cache::get('lobby_game_ids', []);
        \Log::info("📋 Retrieved lobby game IDs: " . count($gameIds));
        return $gameIds;
    }

}