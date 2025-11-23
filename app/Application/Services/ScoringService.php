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
            // ✅ Специальный кейс: Джокер + Туз + карта той же масти (32)
            if ($this->isJokerAceSameSuitCombo($cards)) {
                return 32;
            }
            return $this->calculateThreeCardHand($cards);
        } elseif ($cardCount === 2) {
            return $this->calculateTwoCardHand($cards);
        }
        
        throw new \InvalidArgumentException("Invalid number of cards: " . $cardCount);
    }

    public function calculateDomainHandValue(array $domainCards): int
    {
        // Конвертируем Domain карты в строковое представление
        $stringCards = array_map(function(Card $card) {
            return $this->convertDomainCardToString($card);
        }, $domainCards);
        
        // Используем существующую логику
        return $this->calculateHandValue($stringCards);
    }

    private function convertDomainCardToString(Card $card): string
    {
        $rankMap = [
            CardRank::TEN->value => '10',
            CardRank::JACK->value => 'J', 
            CardRank::QUEEN->value => 'Q',
            CardRank::KING->value => 'K',
            CardRank::ACE->value => 'A',
            CardRank::SIX->value => '6',
        ];
        
        $suitMap = [
            CardSuit::HEARTS->value => '♥',
            CardSuit::DIAMONDS->value => '♦',
            CardSuit::CLUBS->value => '♣',
            CardSuit::SPADES->value => '♠',
        ];
        
        $rankStr = $rankMap[$card->getRank()->value] ?? '?';
        $suitStr = $suitMap[$card->getSuit()->value] ?? '?';
        
        return $rankStr . $suitStr;
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
            
            return $this->calculateWithoutJokerLogic($cards);
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
        
        return $this->calculateWithOptimalJoker($cards);
    }

    private function calculateWithOptimalJoker(array $cards): int
    {
        $bestScore = 10; // Минимальный счет
        
        // Вместо перебора 20 вариантов, анализируем логически
        $optimalReplacements = $this->getOptimalJokerReplacements($cards);
        
        foreach ($optimalReplacements as $replacement) {
            $replacedCards = $this->replaceJoker($cards, $replacement);
            $score = $this->calculateWithoutJokerLogic($replacedCards);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                
                // Если нашли максимальную комбинацию - останавливаемся
                if ($bestScore >= 37) break;
            }
        }
        
        return $bestScore;
    }

    private function getOptimalJokerReplacements(array $cards): array
    {
        $suits = $this->getSuits($cards);
        $ranks = $this->getRanks($cards);
        
        $replacements = [];
        
        // 🎯 Стратегия 1: Попытаться сделать СЕКА комбинацию (37-33 очка)
        $existingRanks = array_filter($ranks, fn($rank) => $rank !== '6');
        if (count($existingRanks) === 2) {
            $rankCounts = array_count_values($existingRanks);
            $mostCommonRank = array_search(max($rankCounts), $rankCounts);
            
            // Становимся третьей картой того же ранга
            $commonSuits = array_count_values($suits);
            $mostCommonSuit = array_search(max($commonSuits), $commonSuits);
            $replacements[] = $mostCommonRank . $mostCommonSuit;
        }
        
        // 🎯 Стратегия 2: Попытаться сделать 32 очка (Джокер + Туз + карта той же масти)
        if (in_array('A', $ranks)) {
            $aceIndex = array_search('A', $ranks);
            $aceSuit = $suits[$aceIndex];
            $replacements[] = 'A' . $aceSuit; // Становимся вторым тузом той же масти
        }
        
        // 🎯 Стратегия 3: Попытаться сделать 31 очко (три одной масти)
        $suitCounts = array_count_values($suits);
        if (max($suitCounts) === 2) {
            $commonSuit = array_search(2, $suitCounts);
            $replacements[] = 'A' . $commonSuit; // Становимся тузом общей масти
            $replacements[] = 'K' . $commonSuit; // Или королем
            $replacements[] = 'Q' . $commonSuit; // Или дамой
            $replacements[] = 'J' . $commonSuit; // Или вальтом
            $replacements[] = '10' . $commonSuit; // Или десяткой
        }
        
        // 🎯 Стратегия 4: Для двух карт - становимся картой чтобы создать пару мастей
        if (count($cards) === 2) {
            $otherCards = array_values(array_filter($cards, fn($card) => $card !== '6♣'));
            
            // 🔧 ИСПРАВЛЕНИЕ: array_values() сбрасывает ключи
            if (!empty($otherCards)) {
                $otherCard = $otherCards[0];
                $otherRank = mb_substr($otherCard, 0, -1);
                $otherSuit = mb_substr($otherCard, -1);
                
                // Становимся картой той же масти для 21 очка
                $replacements[] = 'A' . $otherSuit; // Туз той же масти = 21 очко
                $replacements[] = 'K' . $otherSuit; // Король той же масти = 21 очко
                $replacements[] = $otherRank . $otherSuit; // Та же карта = 21 очко
            } else {
                // Если только джокер - становимся тузом
                $replacements[] = 'A♥';
                $replacements[] = 'A♠';
                $replacements[] = 'A♦';
                $replacements[] = 'A♣';
            }
        }
        
        // 🎯 Стратегия 5: Для трех карт с разными мастями - становимся тузом
        if (count($cards) === 3 && count(array_unique($suits)) === 3) {
            // Выбираем масть которая даст нам туза для 21 очка
            $replacements[] = 'A♥';
            $replacements[] = 'A♠'; 
            $replacements[] = 'A♦';
            $replacements[] = 'A♣';
        }
        
        // 🎯 Стратегия 6: Базовые варианты на всякий случай
        $replacements[] = 'A♥'; // Туз черви
        $replacements[] = 'K♥'; // Король черви
        $replacements[] = 'Q♥'; // Дама черви
        $replacements[] = 'J♥'; // Вальт черви
        $replacements[] = '10♥'; // Десятка черви
        
        return array_unique($replacements);
    }

    private function calculateWithoutJokerLogic(array $cards): int
    {
        // Выносим логику подсчета без джокера в отдельный метод
        $suits = $this->getSuits($cards);
        $ranks = $this->getRanks($cards);
        
        $specialCombo = $this->checkSpecialCombinations($ranks, false);
        if ($specialCombo > 0) return $specialCombo;
        
        $suitCombo = $this->checkSuitCombinations($suits, false, $ranks);
        if ($suitCombo > 0) return $suitCombo;
        
        return $this->getBaseCombination($suits, false, $ranks);
    }
    
    private function calculateTwoCardHand(array $cards): int
    {
        $hasJoker = $this->hasJoker($cards);
        
        if (!$hasJoker) {
            $suits = $this->getSuits($cards);
            $ranks = $this->getRanks($cards);
            return $this->getTwoCardCombination($suits, false, $ranks);
        }
        
        // 🎯 УЛУЧШЕННАЯ ЛОГИКА ДЖОКЕРА ДЛЯ ДВУХ КАРТ
        $bestScore = 20; // Минимальный счет для двух карт
        
        $optimalReplacements = $this->getOptimalJokerReplacements($cards);
        
        foreach ($optimalReplacements as $replacement) {
            $replacedCards = $this->replaceJoker($cards, $replacement);
            $suits = $this->getSuits($replacedCards);
            $ranks = $this->getRanks($replacedCards);
            
            $score = $this->getTwoCardCombination($suits, false, $ranks);
            if ($score > $bestScore) {
                $bestScore = $score;
            }
            
            // Если нашли максимальную комбинацию - останавливаемся
            if ($bestScore >= 22) break;
        }
        
        return $bestScore;
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

    /**
     * Джокер + Туз + карта той же масти, что и туз.
     */
    private function isJokerAceSameSuitCombo(array $cards): bool
    {
        // формат карт в тестах: '6♣', 'A♥', '10♥'
        // Теперь используем английские обозначения: A вместо Т

        if (!in_array('6♣', $cards, true)) {
            return false;
        }

        // убираем джокера, работаем с оставшимися двумя
        $others = array_values(array_filter($cards, fn ($c) => $c !== '6♣'));

        if (count($others) !== 2) {
            return false;
        }

        [$c1, $c2] = $others;

        // Разбираем строки, предполагая формат: [ранг][масть]
        // Теперь используем английские A вместо русских Т
        $rank1 = mb_substr($c1, 0, -1, 'UTF-8');
        $suit1 = mb_substr($c1, -1, null, 'UTF-8');

        $rank2 = mb_substr($c2, 0, -1, 'UTF-8');
        $suit2 = mb_substr($c2, -1, null, 'UTF-8');

        // Один из них должен быть Туз ('A'), другой — любая карта, но той же масти
        $isFirstAce  = ($rank1 === 'A');  // ← ИЗМЕНИЛ 'Т' на 'A'
        $isSecondAce = ($rank2 === 'A');  // ← ИЗМЕНИЛ 'Т' на 'A'

        if ($isFirstAce && !$isSecondAce && $suit1 === $suit2) {
            return true;
        }

        if ($isSecondAce && !$isFirstAce && $suit1 === $suit2) {
            return true;
        }

        return false;
    }


}