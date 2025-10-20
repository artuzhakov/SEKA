<?php
// app/Domain/Game/Enums/GameStatus.php
declare(strict_types=1);

namespace App\Domain\Game\Enums;

enum GameStatus: string
{
    case WAITING = 'waiting';
    case ACTIVE = 'active';
    case DISTRIBUTION = 'distribution';
    case BIDDING = 'bidding';
    case QUARREL = 'quarrel';
    case FINISHED = 'finished';
    case CANCELLED = 'cancelled'; // 🎯 Добавляем новый статус

    public function canStart(): bool
    {
        return $this === self::WAITING;
    }

    public function canInitiateQuarrel(): bool
    {
        return in_array($this, [self::ACTIVE, self::BIDDING]);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::ACTIVE, self::BIDDING, self::DISTRIBUTION]);
    }
}