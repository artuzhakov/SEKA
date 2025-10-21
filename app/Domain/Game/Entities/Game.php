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

    public function __construct(
        private GameId $id,
        private GameStatus $status,
        private int $roomId,
        private GameMode $mode,
        private int $bank = 0,
        private ?int $currentPlayerPosition = null
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

        if ($this->status->isActive()) {
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
    public function getCurrentPlayerId(): PlayerId
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
     * 🎯 Получить текущую максимальную ставку
     */
    public function getCurrentMaxBet(): int
    {
        return $this->currentMaxBet ?? 0;
    }

    /**
     * 🎯 Установить позицию текущего игрока
     */
    public function setCurrentPlayerPosition(int $position): void
    {
        $this->currentPlayerPosition = $position;
    }

    // Геттеры
    public function getId(): GameId { return $this->id; }
    public function getStatus(): GameStatus { return $this->status; }
    public function getMode(): GameMode { return $this->mode; }
    public function getPlayers(): array { return $this->players; }
    /**
     * 🎯 Получить общий банк игры
     */
    public function getBank(): int
    {
        $bank = 0;
        foreach ($this->players as $player) {
            $bank += $player->getCurrentBet();
        }
        return $bank;
    }
    public function getCurrentPlayerPosition(): ?int { return $this->currentPlayerPosition; }
    
    /**
     * 🎯 Получить активных игроков
     */
    public function getActivePlayers(): array
    {
        return array_filter($this->players, fn(Player $player) => $player->isPlaying());
    }
}