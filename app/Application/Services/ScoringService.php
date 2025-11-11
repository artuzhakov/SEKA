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
        
        if (!$hasJoker) {
            // Существующая логика без джокера
            $suits = $this->getSuits($cards);
            $ranks = $this->getRanks($cards);
            
            $specialCombo = $this->checkSpecialCombinations($ranks, false);
            if ($specialCombo > 0) return $specialCombo;
            
            $suitCombo = $this->checkSuitCombinations($suits, false, $ranks);
            if ($suitCombo > 0) return $suitCombo;
            
            return $this->getBaseCombination($suits, false, $ranks);
        }
        
        // 🎯 НОВАЯ ЛОГИКА С ДЖОКЕРОМ
        $bestScore = 10; // Минимальный счет
        
        $possibleCards = $this->getPossibleJokerReplacements();
        
        foreach ($possibleCards as $replacement) {
            $replacedCards = $this->replaceJoker($cards, $replacement);
            $suits = $this->getSuits($replacedCards);
            $ranks = $this->getRanks($replacedCards);
            
            $score = $this->calculateWithoutJoker($suits, $ranks);
            if ($score > $bestScore) {
                $bestScore = $score;
            }
        }
        
        return $bestScore;
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
        
        // 🎯 ДВА ТУЗА = 22 очка
        if (($rankCounts['A'] ?? 0) === 2) {
            return 22;
        }
        
        // 🎯 ТУЗ + ДЖОКЕР = 22 очка
        if ($hasJoker && ($rankCounts['A'] ?? 0) === 1) {
            return 22;
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
        
        // 🎯 ДЖОКЕР + ТУЗ + карта той же масти (32)
        if ($hasJoker && $hasAce) {
            // Находим масть туза
            $aceIndex = array_search('A', $ranks);
            $aceSuit = $suits[$aceIndex];
            
            // Считаем карты той же масти что и туз (кроме джокера)
            $sameSuitAsAce = 0;
            foreach ($suits as $index => $suit) {
                if ($suit === $aceSuit && $ranks[$index] !== '6') {
                    $sameSuitAsAce++;
                }
            }
            
            // Туз + минимум одна карта той же масти + джокер
            if ($sameSuitAsAce >= 2) {
                return 32;
            }
        }
        
        // 🎯 ТРИ ОДИНАКОВЫЕ МАСТИ (30)
        if ($maxSameSuit === 3 && !$hasJoker && !$hasAce) {
            return 30;
        }
        
        // 🎯 ТРИ ОДИНАКОВЫЕ МАСТИ + ТУЗ (31)
        if ($maxSameSuit === 3 && $hasAce && !$hasJoker) {
            return 31;
        }
        
        // 🎯 ДЖОКЕР + ДВЕ ОДИНАКОВЫЕ МАСТИ (31)
        if ($hasJoker && $maxSameSuit === 2) {
            return 31;
        }
        
        return 0;
    }

    private function getBaseCombination(array $suits, bool $hasJoker, array $ranks): int
    {
        $uniqueSuits = count(array_unique($suits));
        $hasAce = in_array('A', $ranks);
        
        // Подсчитываем максимальное количество одинаковых мастей
        $suitCounts = array_count_values($suits);
        $maxSameSuit = max($suitCounts);
        
        // 🎯 ДВЕ ОДИНАКОВЫЕ МАСТИ + ТУЗ = 21 очко
        // Туз дает бонус только если находится в паре с картой той же масти
        if ($maxSameSuit === 2 && $hasAce && !$hasJoker) {
            $aceIndex = array_search('A', $ranks);
            $aceSuit = $suits[$aceIndex];
            
            $sameSuitAsAce = 0;
            foreach ($suits as $suit) {
                if ($suit === $aceSuit) {
                    $sameSuitAsAce++;
                }
            }
            
            // Туз дает бонус только если у него есть пара той же масти
            if ($sameSuitAsAce >= 2) {
                return 21;
            }
        }
        
        // 🎯 ДВЕ ОДИНАКОВЫЕ МАСТИ БЕЗ ТУЗА = 20 очков
        if ($maxSameSuit === 2 && !$hasAce && !$hasJoker) {
            return 20;
        }
        
        // 🎯 ТРИ РАЗНЫЕ МАСТИ + ТУЗ = 11 очков
        if ($uniqueSuits === 3 && $hasAce && !$hasJoker) {
            return 11;
        }
        
        // 🎯 ТРИ РАЗНЫЕ МАСТИ БЕЗ ТУЗА = 10 очков
        if ($uniqueSuits === 3 && !$hasJoker && !$hasAce) {
            return 10;
        }
        
        // 🎯 ДВЕ МАСТИ (когда туз не дает бонус) = 20 очков
        if ($uniqueSuits === 2 && !$hasJoker) {
            return 20;
        }
        
        // Если есть джокер = 10 очков
        if ($hasJoker) {
            return 10;
        }
        
        return 10; // Минимальная комбинация
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

    private function getPossibleJokerReplacements(): array
    {
        // 🎯 Джокер может стать любой картой от 10 до туза
        $suits = ['♥', '♦', '♣', '♠'];
        $ranks = ['10', 'J', 'Q', 'K', 'A'];
        
        $replacements = [];
        foreach ($suits as $suit) {
            foreach ($ranks as $rank) {
                $replacements[] = $rank . $suit;
            }
        }
        return $replacements;
    }

    private function replaceJoker(array $cards, string $replacement): array
    {
        return array_map(function($card) use ($replacement) {
            return $card === self::JOKER ? $replacement : $card;
        }, $cards);
    }

    private function calculateWithoutJoker(array $suits, array $ranks): int
    {
        $specialCombo = $this->checkSpecialCombinations($ranks, false);
        if ($specialCombo > 0) return $specialCombo;
        
        $suitCombo = $this->checkSuitCombinations($suits, false, $ranks);
        if ($suitCombo > 0) return $suitCombo;
        
        return $this->getBaseCombination($suits, false, $ranks);
    }

}