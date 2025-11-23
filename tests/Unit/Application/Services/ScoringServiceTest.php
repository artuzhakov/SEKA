<?php
// tests/Unit/Application/Services/ScoringServiceTest.php

namespace Tests\Unit\Application\Services;

use Tests\TestCase;
use App\Application\Services\ScoringService;

class ScoringServiceTest extends TestCase
{
    private ScoringService $scoringService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->scoringService = new ScoringService();
    }
    
    public function test_three_card_combinations()
    {
        // Тест 1: Разные масти, нет джокера, нет туза (10)
        $cards1 = ['10♥', 'J♠', '8♦'];
        $this->assertEquals(10, $this->scoringService->calculateHandValue($cards1));
        
        // Тест 2: Туз + разные масти, нет джокера (11)
        $cards2 = ['A♥', 'J♠', '8♦'];
        $this->assertEquals(11, $this->scoringService->calculateHandValue($cards2));
        
        // Тест 3: Три одинаковые масти, нет джокера, нет туза (30)
        $cards3 = ['10♥', 'J♥', '8♥'];
        $this->assertEquals(30, $this->scoringService->calculateHandValue($cards3));
        
        // Тест 4: Три одинаковые + Туз (31)
        $cards4 = ['A♥', 'J♥', '8♥'];
        $this->assertEquals(31, $this->scoringService->calculateHandValue($cards4));
        
        // Тест 5: Джокер + две одинаковые (31)
        $cards5 = ['6♣', '10♥', 'J♥'];
        $this->assertEquals(31, $this->scoringService->calculateHandValue($cards5));
    }
    
    public function test_special_seka_combinations()
    {
        echo "\n=== Testing Special SEKA Combinations ===\n";
        
        // СЕКА ДЕСЯТОК (33)
        $cards1 = ['10♥', '10♠', '10♦'];
        $result1 = $this->scoringService->calculateHandValue($cards1);
        echo "Cards: " . implode(', ', $cards1) . " | Result: $result1 | Expected: 33\n";
        $this->assertEquals(33, $result1);
        
        $cards2 = ['10♥', '10♠', '6♣'];
        $result2 = $this->scoringService->calculateHandValue($cards2);
        echo "Cards: " . implode(', ', $cards2) . " | Result: $result2 | Expected: 33\n";
        $this->assertEquals(33, $result2);
        
        // СЕКА ВАЛЬТОВ (34)
        $cards3 = ['J♥', 'J♠', 'J♦']; // ← ЗАМЕНИЛ 'В' на 'J'
        $result3 = $this->scoringService->calculateHandValue($cards3);
        echo "Cards: " . implode(', ', $cards3) . " | Result: $result3 | Expected: 34\n";
        $this->assertEquals(34, $result3);
        
        // СЕКА ДАМ (35)
        $cards4 = ['Q♥', 'Q♠', 'Q♦']; // ← ЗАМЕНИЛ 'Д' на 'Q'
        $result4 = $this->scoringService->calculateHandValue($cards4);
        echo "Cards: " . implode(', ', $cards4) . " | Result: $result4 | Expected: 35\n";
        $this->assertEquals(35, $result4);
        
        // СЕКА КОРОЛЕЙ (36)
        $cards5 = ['K♥', 'K♠', 'K♦']; // ← ЗАМЕНИЛ 'К' на 'K'
        $result5 = $this->scoringService->calculateHandValue($cards5);
        echo "Cards: " . implode(', ', $cards5) . " | Result: $result5 | Expected: 36\n";
        $this->assertEquals(36, $result5);
        
        // СЕКА ТУЗОВ (37)
        $cards6 = ['A♥', 'A♠', 'A♦']; // ← ЗАМЕНИЛ 'Т' на 'A'
        $result6 = $this->scoringService->calculateHandValue($cards6);
        echo "Cards: " . implode(', ', $cards6) . " | Result: $result6 | Expected: 37\n";
        $this->assertEquals(37, $result6);
        
        $cards7 = ['A♥', 'A♠', '6♣']; // ← ЗАМЕНИЛ 'Т' на 'A'
        $result7 = $this->scoringService->calculateHandValue($cards7);
        echo "Cards: " . implode(', ', $cards7) . " | Result: $result7 | Expected: 37\n";
        $this->assertEquals(37, $result7);
    }

    public function test_two_card_combinations()
    {
        echo "\n=== Testing Two Card Combinations ===\n";
        
        // Тест 1: Две одинаковые масти, нет джокера, нет туза (20)
        $cards1 = ['10♥', 'J♥'];
        $result1 = $this->scoringService->calculateHandValue($cards1);
        echo "Cards: " . implode(', ', $cards1) . " | Result: $result1 | Expected: 20\n";
        $this->assertEquals(20, $result1);
        
        // Тест 2: Две одинаковые + Туз (21)
        $cards2 = ['A♥', 'J♥'];
        $result2 = $this->scoringService->calculateHandValue($cards2);
        echo "Cards: " . implode(', ', $cards2) . " | Result: $result2 | Expected: 21\n";
        $this->assertEquals(21, $result2);
        
        // 🔧 ДОБАВЛЯЕМ ПРОВЕРКУ НА ДЖОКЕР + КАРТУ
        // Тест 3: Джокер + карта (21)
        $cards3 = ['6♣', 'J♥'];
        $result3 = $this->scoringService->calculateHandValue($cards3);
        echo "Cards: " . implode(', ', $cards3) . " | Result: $result3 | Expected: 21\n";
        $this->assertEquals(21, $result3);
        
        // Тест 4: Два туза (22)
        $cards4 = ['A♥', 'A♠'];
        $result4 = $this->scoringService->calculateHandValue($cards4);
        echo "Cards: " . implode(', ', $cards4) . " | Result: $result4 | Expected: 22\n";
        $this->assertEquals(22, $result4);
        
        // Тест 5: Туз + Джокер (22)
        $cards5 = ['A♥', '6♣'];
        $result5 = $this->scoringService->calculateHandValue($cards5);
        echo "Cards: " . implode(', ', $cards5) . " | Result: $result5 | Expected: 22\n";
        $this->assertEquals(22, $result5);
    }
    
    public function test_joker_specific_combinations()
    {
        echo "\n=== Testing Joker Specific Combinations ===\n";
        
        // Тест: Джокер + Туз + карта той же масти (32)
        $cards1 = ['6♣', 'A♥', '10♥']; // ← ЗАМЕНИЛ 'A♥' на 'A♥'
        $result1 = $this->scoringService->calculateHandValue($cards1);
        echo "Cards: " . implode(', ', $cards1) . " | Result: $result1 | Expected: 32\n";
        $this->assertEquals(32, $result1);
        
        // Тест: Джокер с разными мастями (должно быть 21, а не 10)
        $cards2 = ['6♣', '10♠', 'Q♦']; // ← ЗАМЕНИЛ '8♦' на 'Q♦' (в SEKA нет 8)
        $result2 = $this->scoringService->calculateHandValue($cards2);
        echo "Cards: " . implode(', ', $cards2) . " | Result: $result2 | Expected: 21\n";
        $this->assertEquals(21, $result2);
    }
    
    public function test_invalid_card_count_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid number of cards: 1");
        
        $cards = ['10♥'];
        $this->scoringService->calculateHandValue($cards);
    }
    
    public function test_four_cards_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $cards = ['10♥', 'J♠', '8♦', '9♥'];
        $this->scoringService->calculateHandValue($cards);
    }

    public function test_joker_ace_same_suit_32_points()
    {
        echo "\n=== Testing Joker + Ace + Same Suit = 32 Points ===\n";
        
        // 🎯 Должно быть 32 очка!
        $cards = ['6♣', 'A♥', '10♥'];
        $result = $this->scoringService->calculateHandValue($cards);
        echo "Cards: " . implode(', ', $cards) . " | Result: $result | Expected: 32\n";
        $this->assertEquals(32, $result, "Джокер + туз + карта той же масти должно быть 32 очка");
        
        // Другой пример
        $cards2 = ['6♣', 'A♦', 'J♦']; 
        $result2 = $this->scoringService->calculateHandValue($cards2);
        echo "Cards: " . implode(', ', $cards2) . " | Result: $result2 | Expected: 32\n";
        $this->assertEquals(32, $result2);
    }

    public function test_edge_cases_with_joker()
    {
        echo "\n=== Testing Edge Cases With Joker ===\n";
        
        // Тест: Джокер + карта (должно быть 21)
        $cards1 = ['6♣', 'J♥'];
        $result1 = $this->scoringService->calculateHandValue($cards1);
        echo "Cards: " . implode(', ', $cards1) . " | Result: $result1 | Expected: 21\n";
        $this->assertEquals(21, $result1);
        
        // Тест: Туз + Джокер (должно быть 22)
        $cards2 = ['A♥', '6♣'];
        $result2 = $this->scoringService->calculateHandValue($cards2);
        echo "Cards: " . implode(', ', $cards2) . " | Result: $result2 | Expected: 22\n";
        $this->assertEquals(22, $result2);
        
        // 🔧 ИСПРАВЛЕНИЕ: 32 очка это ПРАВИЛЬНО!
        $cards3 = ['6♣', 'A♥', 'K♥']; 
        // Джокер становится A♥ → A♥, A♥, K♥ → Джокер + Туз + карта той же масти = 32 очка
        $result3 = $this->scoringService->calculateHandValue($cards3);
        echo "Cards: " . implode(', ', $cards3) . " | Result: $result3 | Expected: 32\n";
        $this->assertEquals(32, $result3);
    }

    public function test_joker_optimal_seka_combination()
    {
        echo "\n=== Testing Joker Optimal SEKA Combination ===\n";
        
        // Тест: Джокер должен создать СЕКА тузов (37 очков)
        $cards1 = ['6♣', 'A♥', 'A♠']; 
        // Джокер становится A♦ → A♥, A♠, A♦ → СЕКА тузов = 37 очков
        $result1 = $this->scoringService->calculateHandValue($cards1);
        echo "Cards: " . implode(', ', $cards1) . " | Result: $result1 | Expected: 37\n";
        $this->assertEquals(37, $result1);
        
        // Тест: Джокер должен создать СЕКА королей (36 очков)
        $cards2 = ['6♣', 'K♥', 'K♠'];
        $result2 = $this->scoringService->calculateHandValue($cards2);
        echo "Cards: " . implode(', ', $cards2) . " | Result: $result2 | Expected: 36\n";
        $this->assertEquals(36, $result2);
    }

}