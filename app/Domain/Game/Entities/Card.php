<?php

namespace App\Domain\Game\Entities;

use App\Domain\Game\Enums\CardSuit;
use App\Domain\Game\Enums\CardRank;

class Card
{
    public function __construct(
        private CardSuit $suit,
        private CardRank $rank
    ) {}

    public function isJoker(): bool
    {
        // Джокер - это 6 крестей
        return $this->suit === CardSuit::CLUBS && $this->rank === CardRank::SIX;
    }

    public function isAce(): bool
    {
        return $this->rank === CardRank::ACE;
    }

    public function equals(Card $other): bool
    {
        return $this->suit === $other->suit && $this->rank === $other->rank;
    }

    public function getSuit(): CardSuit
    {
        return $this->suit;
    }

    public function getRank(): CardRank
    {
        return $this->rank;
    }

    public function getValue(): string
    {
        if ($this->isJoker()) {
            return 'Joker';
        }
        
        return $this->rank->value . ' of ' . $this->suit->value;
    }
}