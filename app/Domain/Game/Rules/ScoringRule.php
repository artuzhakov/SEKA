<?php
// app/Domain/Game/Rules/ScoringRule.php

declare(strict_types=1);

namespace App\Domain\Game\Rules;

use App\Domain\Game\Entities\Card;
use App\Domain\Game\Enums\CardRank;

class ScoringRule
{
    public function calculateScore(array $cards): int
    {
        // 🎯 ДОБАВЛЯЕМ проверку на пустые карты
        if (empty($cards)) {
            return 0;
        }
        
        // 🎯 ДОБАВЛЯЕМ проверку что карты валидны
        $validCards = array_filter($cards, function ($card) {
            if ($card instanceof Card) {
                return true;
            }
            return isset($card['rank']) || isset($card['value']) || isset($card['suit']);
        });
        
        if (empty($validCards)) {
            return 0;
        }
        
        $jokerCount = $this->countJokers($validCards);
        $aceCount = $this->countAces($validCards);
        $sameSuitCount = $this->countSameSuit($validCards);
        $sameRankCount = $this->countSameRank($validCards);

        // 🎯 СЕКА ТУЗОВ (37 очков)
        if ($this->isSekaAces($cards, $jokerCount, $aceCount)) {
            return 37;
        }
        
        // 🎯 СЕКА КОРОЛЕЙ (36 очков)
        if ($this->isSekaKings($cards, $jokerCount)) {
            return 36;
        }
        
        // 🎯 СЕКА ДАМ (35 очков)
        if ($this->isSekaQueens($cards, $jokerCount)) {
            return 35;
        }
        
        // 🎯 СЕКА ВАЛЬТОВ (34 очков)
        if ($this->isSekaJacks($cards, $jokerCount)) {
            return 34;
        }
        
        // 🎯 СЕКА ДЕСЯТОК (33 очков)
        if ($this->isSekaTens($cards, $jokerCount)) {
            return 33;
        }
        
        // 🎯 Три карты одной масти + Туз + Джокер (32 очка)
        if ($sameSuitCount === 3 && $aceCount > 0 && $jokerCount > 0) {
            return 32;
        }
        
        // 🎯 Три карты одной масти + Туз ИЛИ Джокер (31 очко)
        if ($sameSuitCount === 3 && ($aceCount > 0 || $jokerCount > 0)) {
            return 31;
        }
        
        // 🎯 СЕКА МАСТЕЙ (30 очков)
        if ($sameSuitCount === 3) {
            return 30;
        }
        
        // 🎯 ДВА ЛБА (22 очка) - два туза
        if ($aceCount === 2) {
            return 22;
        }
        
        // 🎯 21 очко - Туз + Джокер ИЛИ две карты одной масти + Туз
        if (($aceCount === 1 && $jokerCount === 1) || 
            ($sameSuitCount === 2 && $aceCount === 1)) {
            return 21;
        }
        
        // 🎯 20 очков - две карты одной масти
        if ($sameSuitCount === 2) {
            return 20;
        }
        
        // 🎯 11 очков - есть туз
        if ($aceCount > 0) {
            return 11;
        }
        
        // 🎯 10 очков - базовые
        return 10;
    }
    
    private function isSekaAces(array $cards, int $jokerCount, int $aceCount): bool
    {
        return ($aceCount === 3) || ($aceCount === 2 && $jokerCount === 1);
    }
    
    private function isSekaKings(array $cards, int $jokerCount): bool
    {
        $kingCount = $this->countRank($cards, CardRank::KING);
        return ($kingCount === 3) || ($kingCount === 2 && $jokerCount === 1);
    }
    
    private function isSekaQueens(array $cards, int $jokerCount): bool
    {
        $queenCount = $this->countRank($cards, CardRank::QUEEN);
        return ($queenCount === 3) || ($queenCount === 2 && $jokerCount === 1);
    }
    
    private function isSekaJacks(array $cards, int $jokerCount): bool
    {
        $jackCount = $this->countRank($cards, CardRank::JACK);
        return ($jackCount === 3) || ($jackCount === 2 && $jokerCount === 1);
    }
    
    private function isSekaTens(array $cards, int $jokerCount): bool
    {
        $tenCount = $this->countRank($cards, CardRank::TEN);
        return ($tenCount === 3) || ($tenCount === 2 && $jokerCount === 1);
    }
    
    private function countJokers(array $cards): int
    {
        if (empty($cards)) return 0;
        
        return count(array_filter($cards, function ($card) {
            if ($card instanceof Card) {
                return $card->isJoker();
            }
            // 🎯 Если карта представлена как массив
            return ($card['is_joker'] ?? $card['joker'] ?? false) === true;
        }));
    }
    
    private function countAces(array $cards): int
    {
        if (empty($cards)) return 0;
        
        return count(array_filter($cards, function ($card) {
            if ($card instanceof Card) {
                return $card->isAce();
            }
            // 🎯 Если карта представлена как массив
            $rank = $card['rank'] ?? $card['value'] ?? null;
            return $rank === 'A' || $rank === 'ACE';
        }));
    }
    
    private function countSameSuit(array $cards): int
    {
        $suits = [];
        foreach ($cards as $card) {
            // 🎯 ПРАВИЛЬНОЕ получение масти карты
            if ($card instanceof Card) {
                $suit = $card->getSuit()->value ?? null;
            } else {
                $suit = $card['suit'] ?? null;
            }
            
            if ($suit) {
                $suits[$suit] = ($suits[$suit] ?? 0) + 1;
            }
        }
        
        // 🎯 ИСПРАВЛЕНИЕ: проверяем что массив не пустой
        if (empty($suits)) {
            return 0;
        }
        
        return max($suits);
    }
    
    private function countSameRank(array $cards): int
    {
        $ranks = [];
        foreach ($cards as $card) {
            // 🎯 ПРАВИЛЬНОЕ получение ранга карты
            if ($card instanceof Card) {
                $rank = $card->getRank()->value ?? null;
            } else {
                $rank = $card['rank'] ?? $card['value'] ?? null;
            }
            
            if ($rank) {
                $ranks[] = $rank;
            }
        }
        
        // 🎯 ИСПРАВЛЕНИЕ: проверяем что массив не пустой
        if (empty($ranks)) {
            return 0;
        }
        
        $counts = array_count_values($ranks);
        
        // 🎯 ДОПОЛНИТЕЛЬНАЯ проверка на пустой массив
        if (empty($counts)) {
            return 0;
        }
        
        return max($counts);
    }
    
    private function countRank(array $cards, CardRank $rank): int
    {
        if (empty($cards)) return 0;
        
        $targetRank = $rank->value;
        
        return count(array_filter($cards, function ($card) use ($targetRank) {
            if ($card instanceof Card) {
                return $card->getRank()->value === $targetRank;
            }
            // 🎯 Если карта представлена как массив
            $cardRank = $card['rank'] ?? $card['value'] ?? null;
            return $cardRank === $targetRank;
        }));
    }
}