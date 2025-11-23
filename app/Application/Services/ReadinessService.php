<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\Entities\Game;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\PlayerStatus;
use App\Domain\Game\Repositories\CachedGameRepository;
use App\Domain\Game\Repositories\GameRepositoryInterface;
use DomainException;

class ReadinessService
{
    public function __construct(
        private BiddingService $biddingService,
        private GameRepositoryInterface $gameRepository
    ) {}

    /**
     * 🎯 Отметить игрока как готового
     */
    public function markPlayerReady(Game $game, Player $player): void
    {
        if ($game->getStatus() !== GameStatus::WAITING) {
            throw new DomainException('Cannot mark ready when game is not in waiting state');
        }

        $player->markReady();

        \Log::info("=== READINESS DIAGNOSTICS ===");
        \Log::info("Player {$player->getUserId()} marked as ready");
        \Log::info("Game status: " . $game->getStatus()->value);
        \Log::info("Total players: " . count($game->getPlayers()));
        \Log::info("Ready players count: " . $this->getReadyPlayersCount($game));
        \Log::info("Active players count: " . count($game->getActivePlayers()));
        
        // 🎯 Детальная информация о каждом игроке
        foreach ($game->getPlayers() as $p) {
            \Log::info("Player {$p->getUserId()}: ready={$p->isReady()}, playing={$p->isPlaying()}, status={$p->getStatus()->value}");
        }
        
        \Log::info("Can game start: " . ($this->canGameStart($game) ? 'YES' : 'NO'));

        // 🎯 Проверяем можно ли начать игру
        if ($this->canGameStart($game)) {
            \Log::info("🎯 Starting game automatically...");
            $this->startGame($game);
            \Log::info("🎯 Game started! New status: " . $game->getStatus()->value);
        } else {
            \Log::info("❌ Game cannot start yet");
        }
        
        // 🎯 СОХРАНЯЕМ игру после изменений
        $this->gameRepository->save($game);
        \Log::info("💾 Game saved to repository");
        \Log::info("=== END DIAGNOSTICS ===");
    }

    /**
     * 🎯 Сохранить игру в репозитории
     */
    private function saveGame(Game $game): void
    {
        $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
        $repository->save($game);
        \Log::info("💾 Game saved to repository after readiness change");
    }

    /**
     * 🎯 Проверить можно ли начать игру
     */
    public function canGameStart(Game $game): bool
    {
        $readyPlayers = array_filter(
            $game->getPlayers(), 
            fn(Player $player) => $player->isReady() && $player->isPlaying()
        );

        $canStart = count($readyPlayers) >= 2;
        
        \Log::info("CanGameStart check - Ready players: " . count($readyPlayers) . ", needed: 2, result: " . ($canStart ? 'YES' : 'NO'));
        
        return $canStart;
    }

    /**
     * 🎯 Получить активных игроков (временное решение)
     */
    private function getActivePlayingPlayers(Game $game): array
    {
        $players = $game->getPlayers();
        return array_filter($players, function($player) {
            return $player->isPlaying();
        });
    }

    /**
     * 🎯 Начать игру
     */
    public function startGame(Game $game): void
    {
        // 🎯 ИСПРАВЛЕНИЕ: реально меняем статус игры на ACTIVE
        $reflection = new \ReflectionClass($game);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setAccessible(true);
        $statusProperty->setValue($game, \App\Domain\Game\Enums\GameStatus::ACTIVE);
        
        // 🎯 Используем существующий метод getActivePlayers() из Game
        $activePlayers = $game->getActivePlayers();
        if (!empty($activePlayers)) {
            $game->setCurrentPlayerPosition($activePlayers[0]->getPosition());
            
            // 🎯 Обновляем время действия для текущего игрока
            $activePlayers[0]->updateLastActionTime();
        }
        
        \Log::info("Game started and set to ACTIVE status. Active players: " . count($activePlayers));
        
        // 🎯 СОХРАНЯЕМ игру после старта
        $this->saveGame($game);
    }

    /**
     * 🎯 Получить игру по ID
     */
    public function getGame(int $gameId): Game
    {
        if ($gameId <= 0) {
            throw new \InvalidArgumentException('Game ID must be positive integer');
        }
        
        $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
        
        // 🎯 ИСПРАВЛЕНИЕ: Используем find() вместо findById()
        $game = $repository->find(\App\Domain\Game\ValueObjects\GameId::fromInt($gameId));
        
        if (!$game) {
            throw new \DomainException("Game with ID {$gameId} not found");
        }
        
        return $game;
    }

    /**
     * 🎯 Проверить таймауты готовности
     */
    public function checkReadyTimeouts(Game $game): array
    {
        $timedOutPlayers = [];
        
        foreach ($game->getPlayers() as $player) {
            if ($player->isPlaying() && !$player->isReady() && $player->isReadyTimedOut()) {
                $this->removePlayerForReadyTimeout($game, $player);
                $timedOutPlayers[] = $player;
            }
        }

        return $timedOutPlayers;
    }

    /**
     * 🎯 Удалить игрока по таймауту готовности
     */
    private function removePlayerForReadyTimeout(Game $game, Player $player): void
    {
        // 🎯 Устанавливаем специальный статус или просто удаляем из игры
        $player->fold();
        
        // 🎯 Если игроков стало меньше 2, отменяем игру
        if (count($game->getActivePlayers()) < 2) {
            $this->cancelGame($game);
        }
    }

    /**
     * 🎯 Отменить игру из-за недостатка игроков
     */
    private function cancelGame(Game $game): void
    {
        // 🎯 Возвращаем игру в статус CANCELLED
        $reflection = new \ReflectionClass($game);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setAccessible(true);
        $statusProperty->setValue($game, \App\Domain\Game\Enums\GameStatus::CANCELLED);
        
        // 🎯 Возвращаем ставки игрокам
        foreach ($game->getPlayers() as $player) {
            if ($player->getCurrentBet() > 0) {
                $player->addToBalance($player->getCurrentBet());
                
                // Сбрасываем ставку через рефлексию
                $reflectionPlayer = new \ReflectionClass($player);
                $currentBetProperty = $reflectionPlayer->getProperty('currentBet');
                $currentBetProperty->setAccessible(true);
                $currentBetProperty->setValue($player, 0);
            }
        }
    }

    /**
     * 🎯 Проверить таймауты ходов
     */
    public function checkTurnTimeouts(Game $game): array
    {
        $timedOutPlayers = [];
        
        if ($game->getStatus() !== GameStatus::BIDDING) {
            return $timedOutPlayers;
        }

        $currentPlayer = $this->getCurrentPlayer($game);
        
        if ($currentPlayer && $currentPlayer->isTurnTimedOut()) {
            $this->processTurnTimeout($game, $currentPlayer);
            $timedOutPlayers[] = $currentPlayer;
        }

        return $timedOutPlayers;
    }

    /**
     * 🎯 Обработать таймаут хода
     */
    private function processTurnTimeout(Game $game, Player $player): void
    {
        // 🎯 Автоматически делаем FOLD при таймауте хода
        try {
            $this->biddingService->processPlayerAction($game, $player, \App\Domain\Game\Enums\PlayerAction::FOLD);
        } catch (DomainException $e) {
            // 🎯 Если не удалось обработать действие, просто выбываем игрока
            $player->fold();
        }
    }

    /**
     * 🎯 Получить текущего игрока
     */
    private function getCurrentPlayer(Game $game): ?Player
    {
        $currentPosition = $game->getCurrentPlayerPosition();
        if (!$currentPosition) {
            return null;
        }

        foreach ($game->getPlayers() as $player) {
            if ($player->getPosition() === $currentPosition && $player->isPlaying()) {
                return $player;
            }
        }

        return null;
    }

    /**
     * 🎯 Получить информацию о таймерах для фронтенда
     */
    public function getTimersInfo(Game $game): array
    {
        $timers = [];
        
        foreach ($game->getPlayers() as $player) {
            $isCurrentTurn = $this->isPlayerCurrentTurn($game, $player);
            
            $timers[$player->getUserId()] = [
                'player_id' => $player->getUserId(),
                'is_ready' => $player->isReady(),
                'ready_time_remaining' => $player->getRemainingReadyTime(),
                'turn_time_remaining' => $isCurrentTurn ? $player->getRemainingTurnTime() : null,
                'is_current_turn' => $isCurrentTurn,
                'status' => $player->getStatus()->value,
            ];
        }

        return $timers;
    }

    /**
     * 🎯 Проверить ход ли сейчас игрока
     */
    private function isPlayerCurrentTurn(Game $game, Player $player): bool
    {
        $currentPosition = $game->getCurrentPlayerPosition();
        return $currentPosition === $player->getPosition() && $player->isPlaying();
    }

    /**
     * 🎯 Получить список готовых игроков
     */
    public function getReadyPlayers(Game $game): array
    {
        return array_filter(
            $game->getPlayers(),
            fn(Player $player) => $player->isReady() && $player->isPlaying()
        );
    }

    /**
     * 🎯 Получить список неготовых игроков
     */
    public function getNotReadyPlayers(Game $game): array
    {
        return array_filter(
            $game->getPlayers(),
            fn(Player $player) => !$player->isReady() && $player->isPlaying()
        );
    }

    /**
     * 🎯 Получить количество готовых игроков
     */
    public function getReadyPlayersCount(Game $game): int
    {
        return count($this->getReadyPlayers($game));
    }

    /**
     * 🎯 Сбросить готовность всех игроков (для новой игры)
     */
    public function resetAllPlayersReadiness(Game $game): void
    {
        foreach ($game->getPlayers() as $player) {
            // 🎯 Используем рефлексию для сброса готовности
            $reflection = new \ReflectionClass($player);
            
            $isReadyProperty = $reflection->getProperty('isReady');
            $isReadyProperty->setAccessible(true);
            $isReadyProperty->setValue($player, false);
            
            $readyAtProperty = $reflection->getProperty('readyAt');
            $readyAtProperty->setAccessible(true);
            $readyAtProperty->setValue($player, null);
        }
    }

    /**
     * 🎯 Получить время до старта игры
     */
    public function getTimeUntilGameStart(Game $game): ?int
    {
        if ($game->getStatus() !== GameStatus::WAITING) {
            return null;
        }

        $notReadyPlayers = $this->getNotReadyPlayers($game);
        if (empty($notReadyPlayers)) {
            return 0;
        }

        // 🎯 Возвращаем минимальное оставшееся время среди неготовых игроков
        $minRemainingTime = min(
            array_map(
                fn(Player $player) => $player->getRemainingReadyTime(),
                $notReadyPlayers
            )
        );

        return max(0, $minRemainingTime);
    }
}