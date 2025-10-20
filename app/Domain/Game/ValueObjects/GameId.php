<?php
// app/Domain/Game/ValueObjects/GameId.php
declare(strict_types=1);

namespace App\Domain\Game\ValueObjects;

use InvalidArgumentException;

final class GameId
{
    private function __construct(private int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('Game ID must be positive');
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}