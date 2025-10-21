<?php
// app/Application/Services/ScoringService.php

namespace App\Application\Services;

class ScoringService
{
    const JOKER = '6♣';
    
    public function calculateHandValue(array $cards): int
    {
        $cardCount = count($cards);
        
        if ($cardCount === 3) {
            return $this->calculateThreeCardHand($cards);
        } elseif ($cardCount === 2) {
            return $this->calculateTwoCardHand($cards);
        }
        
        throw new \InvalidArgumentException("Invalid number of cards: " . $cardCount);
    }
    
    private function calculateThreeCardHand(array $cards): int
    {
        $hasJoker = $this->hasJoker($cards);
        $suits = $this->getSuits($cards);
        $ranks = $this->getRanks($cards);
        
        // Проверяем специальные комбинации СЕКА сначала
        $specialCombo = $this->checkSpecialCombinations($ranks, $hasJoker);
        if ($specialCombo > 0) {
            return $specialCombo;
        }
        
        // Проверяем комбинации с мастями
        $suitCombo = $this->checkSuitCombinations($suits, $hasJoker, $ranks);
        if ($suitCombo > 0) {
            return $suitCombo;
        }
        
        // Базовая комбинация
        return $this->getBaseCombination($suits, $hasJoker, $ranks);
    }
    
    private function calculateTwoCardHand(array $cards): int
    {
        $hasJoker = $this->hasJoker($cards);
        $suits = $this->getSuits($cards);
        $ranks = $this->getRanks($cards);
        
        return $this->getTwoCardCombination($suits, $hasJoker, $ranks);
    }
    
    private function checkSpecialCombinations(array $ranks, bool $hasJoker): int
    {
        $rankCounts = array_count_values($ranks);
        
        // Убираем джокер из подсчета для специальных комбинаций
        if ($hasJoker) {
            unset($rankCounts['6']);
        }
        
        // Три десятки (33)
        if (($rankCounts['10'] ?? 0) === 3) {
            return 33;
        }
        if ($hasJoker && ($rankCounts['10'] ?? 0) === 2) {
            return 33;
        }
        
        // Три вальта (34)
        if (($rankCounts['J'] ?? 0) === 3) {
            return 34;
        }
        if ($hasJoker && ($rankCounts['J'] ?? 0) === 2) {
            return 34;
        }
        
        // Три дамы (35)
        if (($rankCounts['Q'] ?? 0) === 3) {
            return 35;
        }
        if ($hasJoker && ($rankCounts['Q'] ?? 0) === 2) {
            return 35;
        }
        
        // Три короля (36)
        if (($rankCounts['K'] ?? 0) === 3) {
            return 36;
        }
        if ($hasJoker && ($rankCounts['K'] ?? 0) === 2) {
            return 36;
        }
        
        // Три туза (37)
        if (($rankCounts['A'] ?? 0) === 3) {
            return 37;
        }
        if ($hasJoker && ($rankCounts['A'] ?? 0) === 2) {
            return 37;
        }
        
        return 0;
    }
    
    private function checkSuitCombinations(array $suits, bool $hasJoker, array $ranks): int
    {
        $suitCounts = array_count_values($suits);
        $maxSameSuit = max($suitCounts);
        $hasAce = in_array('A', $ranks);
        
        // Джокер + Туз + карта той же масти (32)
        if ($hasJoker && $hasAce) {
            // Находим масть туза
            $aceSuit = $suits[array_search('A', $ranks)];
            // Проверяем есть ли еще карта той же масти (не джокер)
            $aceSuitCount = 0;
            foreach ($suits as $index => $suit) {
                if ($suit === $aceSuit && $ranks[$index] !== '6') {
                    $aceSuitCount++;
                }
            }
            if ($aceSuitCount >= 2) { // Туз + еще одна карта той же масти + джокер
                return 32;
            }
        }
        
        // Три одинаковые масти (30)
        if ($maxSameSuit === 3 && !$hasJoker && !$hasAce) {
            return 30;
        }
        
        // Три одинаковые + Туз (31) ИЛИ Джокер + две одинаковые (31)
        if (($maxSameSuit === 3 && $hasAce) || ($hasJoker && $maxSameSuit === 2)) {
            return 31;
        }
        
        return 0;
    }
    
    private function getBaseCombination(array $suits, bool $hasJoker, array $ranks): int
    {
        $uniqueSuits = count(array_unique($suits));
        $hasAce = in_array('A', $ranks);
        
        if ($uniqueSuits === 3 && !$hasJoker && !$hasAce) {
            return 10; // Разные масти, нет джокера, нет туза
        }
        
        if ($uniqueSuits === 3 && $hasAce && !$hasJoker) {
            return 11; // Туз + разные масти, нет джокера
        }
        
        // Если есть джокер, но нет особых комбинаций - минимальная
        if ($hasJoker) {
            return 10;
        }
        
        return 10; // Минимальная комбинация по умолчанию
    }
    
    private function getTwoCardCombination(array $suits, bool $hasJoker, array $ranks): int
    {
        $uniqueSuits = count(array_unique($suits));
        $hasAce = in_array('A', $ranks);
        $aceCount = array_count_values($ranks)['A'] ?? 0;
        
        // Два туза (22)
        if ($aceCount === 2) {
            return 22;
        }
        
        // Туз + Джокер (22)
        if ($hasJoker && $hasAce) {
            return 22;
        }
        
        // Две одинаковые масти, нет джокера, нет туза (20)
        if ($uniqueSuits === 1 && !$hasJoker && !$hasAce) {
            return 20;
        }
        
        // Две одинаковые + Туз (21) ИЛИ Джокер + карта (21)
        if (($uniqueSuits === 1 && $hasAce) || $hasJoker) {
            return 21;
        }
        
        return 20; // Минимальная для двух карт
    }
    
    // Вспомогательные методы
    private function hasJoker(array $cards): bool
    {
        return in_array(self::JOKER, $cards);
    }
    
    private function getSuits(array $cards): array
    {
        return array_map(function($card) {
            // Масть - последний символ (эмоджи или символ)
            return mb_substr($card, -1);
        }, $cards);
    }
    
    private function getRanks(array $cards): array
    {
        return array_map(function($card) {
            $rank = mb_substr($card, 0, -1);
            return $this->normalizeRank($rank);
        }, $cards);
    }
    
    private function normalizeRank(string $rank): string
    {
        $map = [
            '6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10',
            'В' => 'J', 'Д' => 'Q', 'К' => 'K', 'Т' => 'A'
        ];
        
        return $map[$rank] ?? $rank;
    }
}