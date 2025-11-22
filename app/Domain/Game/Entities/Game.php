<?php
// app/Domain/Game/Entities/Game.php
declare(strict_types=1);

namespace App\Domain\Game\Entities;

use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\GameMode;
use DomainException;

class Game
{
    private array $players = [];
    private array $events = [];
    private int $currentRound = 1; // Текущий круг: 1, 2, 3
    private int $tableLimit = 100; // Лимит стола (потолок)
    private int $ante = 10;        // Минимальная ставка входа
    private ?int $dealerPosition = null; // Позиция дилера
    private int $currentBiddingRound = 1;
    private int $currentMaxBet = 0;
    private int $bank = 0;
    private ?int $currentPlayerPosition = null;
    private ?PlayerId $currentPlayerId = null;

    public function __construct(
        private GameId $id,
        private GameStatus $status,
        private int $roomId,
        private GameMode $mode
        // Убрали bank и currentPlayerPosition из параметров конструктора
        // они инициализируются значениями по умолчанию выше
    ) {}

    public function start(): void
    {
        if (count($this->players) < 2) {
            throw new DomainException('Need at least 2 players to start game');
        }

        if (!$this->status->canStart()) {
            throw new DomainException('Game cannot be started in current status');
        }

        $this->status = GameStatus::ACTIVE;
    }

    public function startDistribution(): void
    {
        $this->status = GameStatus::DISTRIBUTION;
    }

    public function addPlayer(Player $player): void
    {
        if ($this->hasPlayer($player->getId())) {
            throw new DomainException('Player already in game');
        }

        // ИСПРАВЛЕНО: используем метод enum
        if (!$this->status->canAddPlayers()) {
            throw new DomainException('Cannot add player to active game');
        }

        $this->players[] = $player;
    }

    public function initiateQuarrel(array $winningPlayers): void
    {
        if (!$this->status->canInitiateQuarrel()) {
            throw new DomainException('Cannot initiate quarrel in current status');
        }

        $this->status = GameStatus::QUARREL;
    }

    private function hasPlayer(PlayerId $playerId): bool
    {
        foreach ($this->players as $player) {
            if ($player->getId()->equals($playerId)) {
                return true;
            }
        }
        return false;
    }

    private function recordEvent(object $event): void
    {
        $this->events[] = $event;
    }

    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }
    
    public function getRoomId(): int
    {
        return $this->roomId;
    }

    /**
     * 🎯 Начать фазу торгов
     */
    public function startBidding(): void
    {
        $this->status = GameStatus::BIDDING;
    }

    /**
     * 🎯 Установить текущего игрока
     */
    public function setCurrentPlayer(PlayerId $playerId): void
    {
        $this->currentPlayerId = $playerId;
    }

    /**
     * 🎯 Получить ID текущего игрока
     */
    public function getCurrentPlayerId(): ?PlayerId
    {
        return $this->currentPlayerId;
    }

    /**
     * 🎯 Установить текущую максимальную ставку
     */
    public function setCurrentMaxBet(int $bet): void
    {
        $this->currentMaxBet = $bet;
    }

    /**
     * 🎯 Установить текущий раунд торгов
     */
    public function setCurrentBiddingRound(int $round): void
    {
        $this->currentBiddingRound = $round;
    }

    /**
     * 🎯 Получить текущий раунд торгов
     */
    public function getCurrentBiddingRound(): int
    {
        return $this->currentBiddingRound;
    }

    /**
     * 🎯 Получить текущую максимальную ставку
     */
    public function getCurrentMaxBet(): int
    {
        return $this->currentMaxBet;
    }

    /**
     * 🎯 Установить позицию текущего игрока
     */
    public function setCurrentPlayerPosition(?int $position): void
    {
        $this->currentPlayerPosition = $position;
    }

    /**
     * 🎯 Установить банк игры
     */
    public function setBank(int $bank): void
    {
        $this->bank = $bank;
    }

    // Геттеры
    public function getId(): GameId { return $this->id; }
    public function getStatus(): GameStatus { return $this->status; }
    /**
     * 🎯 Установить статус игры
     */
    public function setStatus(GameStatus $status): void
    {
        $this->status = $status;
    }
    public function getMode(): GameMode { return $this->mode; }
    public function getPlayers(): array { return $this->players; }
    
    /**
     * 🎯 Получить общий банк игры
     */
    public function getBank(): int
    {
        return $this->bank;
    }
    
    public function getCurrentPlayerPosition(): ?int { return $this->currentPlayerPosition; }
    
    /**
     * 🎯 Получить активных игроков
     */
    public function getActivePlayers(): array
    {
        return array_filter($this->players, function(Player $player) {
            return $player->isPlaying();
        });
    }

    /**
     * 🎯 Получить текущий раунд (алиас для getCurrentBiddingRound)
     */
    public function getCurrentRound(): int
    {
        return $this->getCurrentBiddingRound();
    }
    
    /**
     * 🎯 Установить текущий раунд (алиас для setCurrentBiddingRound)
     */
    public function setCurrentRound(int $round): void
    {
        $this->setCurrentBiddingRound($round);
    }
    
    public function getTableLimit(): int { return $this->tableLimit; }
    public function setTableLimit(int $limit): void { $this->tableLimit = $limit; }
    
    public function getAnte(): int { return $this->ante; }
    public function setAnte(int $ante): void { $this->ante = $ante; }
    
    public function getDealerPosition(): ?int { return $this->dealerPosition; }
    public function setDealerPosition(int $position): void { $this->dealerPosition = $position; }

    /**
     * 🎯 Получить игрока справа от дилера
     */
    public function getPlayerRightOfDealer(): ?Player
    {
        $dealerPosition = $this->getDealerPosition(); // 🎯 ИСПРАВЛЕНИЕ: используем dealerPosition
        if (!$dealerPosition) {
            \Log::info("❌ No dealer position set");
            return null;
        }
        
        $activePlayers = $this->getActivePlayers();
        if (empty($activePlayers)) {
            \Log::info("❌ No active players");
            return null;
        }
        
        // Сортируем позиции активных игроков
        $positions = array_map(fn($player) => $player->getPosition(), $activePlayers);
        sort($positions);
        
        \Log::info("🔍 Dealer position: {$dealerPosition}, Active positions: " . implode(', ', $positions));
        
        // Находим индекс дилера среди активных игроков
        $currentIndex = array_search($dealerPosition, $positions);
        if ($currentIndex === false) {
            \Log::info("❌ Dealer not found in active players");
            // Если дилер не активен, начинаем с первого активного игрока
            return $activePlayers[0] ?? null;
        }
        
        // Переходим к следующему игроку по кругу
        $nextIndex = ($currentIndex + 1) % count($positions);
        $nextPosition = $positions[$nextIndex];
        
        \Log::info("✅ Next player position: {$nextPosition}");
        
        // Находим игрока по позиции
        foreach ($activePlayers as $player) {
            if ($player->getPosition() === $nextPosition) {
                return $player;
            }
        }
        
        return null;
    }

    /**
     * 🎯 Получить игрока по позиции
     */
    public function getPlayerByPosition(int $position): ?Player
    {
        foreach ($this->players as $player) {
            if ($player->getPosition() === $position) {
                return $player;
            }
        }
        return null;
    }

    public function increaseBank(int $amount): void
    {
        // финальная
        $this->bank += $amount;
    }

    /**
     * 🎯 Получить игрока по ID пользователя
     */
    public function getPlayerById(int $userId): ?Player
    {
        foreach ($this->players as $player) {
            if ($player->getUserId() === $userId) {
                return $player;
            }
        }
        return null;
    }

}