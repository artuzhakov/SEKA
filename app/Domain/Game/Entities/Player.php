<?php
// app/Domain/Game/Entities/Player.php
declare(strict_types=1);

namespace App\Domain\Game\Entities;

use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\Enums\PlayerStatus;
use DomainException;

class Player
{
    private array $cards = [];
    private int $currentBet = 0;

    private bool $hasChecked = false; // Пропустил ход в текущем круге
    private bool $hasPlayedDark = false; // Играл ли в темную в этой игре
    
    // 🎯 Таймауты (в секундах)
    private const READY_TIMEOUT = 10;
    private const TURN_TIMEOUT = 30;
    private const QUARREL_VOTE_TIMEOUT = 10;
    
    // 🎯 Временные метки
    private ?int $readyAt = null;
    private ?int $lastActionAt = null;
    private ?int $quarrelVoteAt = null;
    
    // 🎯 Статусы и голосования
    private bool $isReady = false;
    private ?bool $quarrelVote = null;

    public function __construct(
        private PlayerId $id,
        private int $userId,
        private int $position,
        private PlayerStatus $status,
        private int $balance
    ) {}

    /**
     * 🎯 Установить текущую ставку игрока
     */
    public function setCurrentBet(int $bet): void
    {
        $this->currentBet = $bet;
    }

    // 🎯 Методы готовности
    public function markReady(): void
    {
        $this->isReady = true;
        $this->readyAt = time();
        $this->updateLastActionTime();
    }

    public function isReady(): bool
    {
        return $this->isReady;
    }

    public function getReadyAt(): ?int
    {
        return $this->readyAt;
    }

    public function isReadyTimedOut(): bool
    {
        if (!$this->readyAt) {
            return false;
        }
        return (time() - $this->readyAt) > self::READY_TIMEOUT;
    }

    public function getRemainingReadyTime(): int
    {
        if (!$this->readyAt) {
            return self::READY_TIMEOUT;
        }
        return max(0, self::READY_TIMEOUT - (time() - $this->readyAt));
    }

    // 🎯 Методы времени действий
    public function updateLastActionTime(): void
    {
        $this->lastActionAt = time();
    }

    public function getLastActionTime(): ?int
    {
        return $this->lastActionAt;
    }

    public function isTurnTimedOut(): bool
    {
        if (!$this->lastActionAt) {
            return false;
        }
        return (time() - $this->lastActionAt) > self::TURN_TIMEOUT;
    }

    public function getRemainingTurnTime(): int
    {
        if (!$this->lastActionAt) {
            return self::TURN_TIMEOUT;
        }
        return max(0, self::TURN_TIMEOUT - (time() - $this->lastActionAt));
    }

    // 🎯 Методы голосования в сваре
    public function voteForQuarrel(bool $vote): void
    {
        $this->quarrelVote = $vote;
        $this->quarrelVoteAt = time();
    }

    public function getQuarrelVote(): ?bool
    {
        return $this->quarrelVote;
    }

    public function getQuarrelVoteAt(): ?int
    {
        return $this->quarrelVoteAt;
    }

    public function isQuarrelVoteTimedOut(): bool
    {
        if (!$this->quarrelVoteAt) {
            return false;
        }
        return (time() - $this->quarrelVoteAt) > self::QUARREL_VOTE_TIMEOUT;
    }

    public function getRemainingQuarrelVoteTime(): int
    {
        if (!$this->quarrelVoteAt) {
            return self::QUARREL_VOTE_TIMEOUT;
        }
        return max(0, self::QUARREL_VOTE_TIMEOUT - (time() - $this->quarrelVoteAt));
    }

    public function hasVotedForQuarrel(): bool
    {
        return $this->quarrelVote !== null;
    }

    // 🎯 Игровые методы
    public function placeBet(int $amount): void
    {
        if ($amount > $this->balance) {
            throw new DomainException('Insufficient balance for bet');
        }

        $this->currentBet += $amount;
        $this->balance -= $amount;
        $this->updateLastActionTime();
    }

    public function receiveCards(array $cards): void
    {
        $this->cards = $cards; // ЗАМЕНЯЕМ карты, а не добавляем
    }

    public function pass(): void
    {
        $this->status = PlayerStatus::FOLDED;
        $this->updateLastActionTime();
    }

    public function reveal(): void
    {
        $this->status = PlayerStatus::REVEALED;
        $this->updateLastActionTime();
    }

    public function fold(): void
    {
        $this->status = PlayerStatus::FOLDED; // 🎯 Должно быть FOLDED
        $this->cards = [];
        $this->updateLastActionTime();
    }

    public function setStatus(PlayerStatus $status): void
    {
        $this->status = $status;
        $this->updateLastActionTime();
    }

    public function playDark(): void
    {
        // 🎯 Игрок переходит в статус DARK и помечается как игравший в темную в этой игре
        $this->status = PlayerStatus::DARK;
        $this->hasPlayedDark = true;
        $this->updateLastActionTime();
    }

    public function openCards(): void
    {
        if ($this->status === PlayerStatus::DARK) {
            $this->status = PlayerStatus::ACTIVE;
        }
        $this->updateLastActionTime();
    }

    // 🎯 Геттеры
    public function getId(): PlayerId { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getPosition(): int { return $this->position; }
    public function getStatus(): PlayerStatus { return $this->status; }
    public function getBalance(): int { return $this->balance; }
    public function getCards(): array { return $this->cards; }
    public function getCurrentBet(): int { return $this->currentBet; }
    
    public function isPlaying(): bool 
    { 
        // 🎯 ИСПРАВЛЕНИЕ: Игрок играет только если он ACTIVE, READY или DARK
        // И НЕ FOLDED и НЕ REVEALED (если revealed означает завершение хода)
        return in_array($this->status, [
            PlayerStatus::ACTIVE, 
            PlayerStatus::READY,
            PlayerStatus::DARK
        ]);
    }

    // 🎯 Сброс ставки для нового раунда
    public function resetBet(): void
    {
        $this->currentBet = 0;
    }

    // 🎯 Получить эффективную ставку (для темнящих игроков)
    public function getEffectiveBet(): int
    {
        return $this->status === PlayerStatus::DARK 
            ? (int)($this->currentBet * 2)
            : $this->currentBet;
    }

    // 🎯 Методы для сброса состояния
    public function resetForNewRound(): void
    {
        $this->currentBet = 0;
        $this->lastActionAt = null;
    }

    public function resetQuarrelVote(): void
    {
        $this->quarrelVote = null;
        $this->quarrelVoteAt = null;
    }

    // 🎯 Метод для добавления выигрыша
    public function addToBalance(int $amount): void
    {
        $this->balance += $amount;
    }

    public function hasChecked(): bool { return $this->hasChecked; }
    public function setChecked(bool $checked): void { $this->hasChecked = $checked; }
    
    public function hasPlayedDark(): bool { return $this->hasPlayedDark; }
    public function setPlayedDark(bool $played): void { $this->hasPlayedDark = $played; }

    /**
     * 🎯 Сброс состояния для нового круга торгов
     */
    public function resetForNewBiddingRound(): void
    {
        $this->hasChecked = false;
        $this->lastActionAt = null;
        // currentBet НЕ сбрасываем - он накапливается
    }

    public function increaseCurrentBet(int $amount): void
    {
        // финальная
        $this->currentBet += $amount;
    }


}