<?php
// app/Domain/Game/Enums/PlayerAction.php
declare(strict_types=1);

namespace App\Domain\Game\Enums;

enum PlayerAction: string
{
    case FOLD = 'fold';      // Пас
    case RAISE = 'raise';    // Повышение ставки
    case CALL = 'call';      // Поддержка ставки
    case CHECK = 'check';    // Пропуск хода (без ставки)
    case REVEAL = 'reveal';  // Вскрытие
    case DARK = 'dark';      // Игра в темную
    case OPEN = 'open';      // Открытие карт (после темной)
    
    public function isAvailableInFirstRound(): bool
    {
        return match($this) {
            self::CHECK, self::DARK, self::FOLD, self::RAISE => true,
            default => false
        };
    }
    
    public function requiresBetAmount(): bool
    {
        return in_array($this, [self::RAISE, self::REVEAL, self::CALL]);
    }
}