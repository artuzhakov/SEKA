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
        $cards2 = ['Т♥', 'J♠', '8♦'];
        $this->assertEquals(11, $this->scoringService->calculateHandValue($cards2));
        
        // Тест 3: Три одинаковые масти, нет джокера, нет туза (30)
        $cards3 = ['10♥', 'J♥', '8♥'];
        $this->assertEquals(30, $this->scoringService->calculateHandValue($cards3));
        
        // Тест 4: Три одинаковые + Туз (31)
        $cards4 = ['Т♥', 'J♥', '8♥'];
        $this->assertEquals(31, $this->scoringService->calculateHandValue($cards4));
        
        // Тест 5: Джокер + две одинаковые (31)
        $cards5 = ['6♣', '10♥', 'J♥'];
        $this->assertEquals(31, $this->scoringService->calculateHandValue($cards5));
    }
    
    public function test_special_seka_combinations()
    {
        echo "\n=== Testing Special SEKA Combinations ===\n";
        
        // Тест СЕКА ДЕСЯТОК (33) - три десятки
        $cards1 = ['10♥', '10♠', '10♦'];
        $result1 = $this->scoringService->calculateHandValue($cards1);
        echo "Cards: " . implode(', ', $cards1) . " | Result: $result1 | Expected: 33\n";
        $this->assertEquals(33, $result1);
        
        // Тест СЕКА ДЕСЯТОК с джокером (33) - две десятки + джокер
        $cards2 = ['10♥', '10♠', '6♣'];
        $result2 = $this->scoringService->calculateHandValue($cards2);
        echo "Cards: " . implode(', ', $cards2) . " | Result: $result2 | Expected: 33\n";
        $this->assertEquals(33, $result2);
        
        // Тест СЕКА ВАЛЬТОВ (34) - три вальта
        $cards3 = ['В♥', 'В♠', 'В♦'];
        $result3 = $this->scoringService->calculateHandValue($cards3);
        echo "Cards: " . implode(', ', $cards3) . " | Result: $result3 | Expected: 34\n";
        $this->assertEquals(34, $result3);
        
        // Тест СЕКА ДАМ (35) - три дамы
        $cards4 = ['Д♥', 'Д♠', 'Д♦'];
        $result4 = $this->scoringService->calculateHandValue($cards4);
        echo "Cards: " . implode(', ', $cards4) . " | Result: $result4 | Expected: 35\n";
        $this->assertEquals(35, $result4);
        
        // Тест СЕКА КОРОЛЕЙ (36) - три короля
        $cards5 = ['К♥', 'К♠', 'К♦'];
        $result5 = $this->scoringService->calculateHandValue($cards5);
        echo "Cards: " . implode(', ', $cards5) . " | Result: $result5 | Expected: 36\n";
        $this->assertEquals(36, $result5);
        
        // Тест СЕКА ТУЗОВ (37) - три туза
        $cards6 = ['Т♥', 'Т♠', 'Т♦'];
        $result6 = $this->scoringService->calculateHandValue($cards6);
        echo "Cards: " . implode(', ', $cards6) . " | Result: $result6 | Expected: 37\n";
        $this->assertEquals(37, $result6);
        
        // Тест СЕКА ТУЗОВ с джокером (37) - два туза + джокер
        $cards7 = ['Т♥', 'Т♠', '6♣'];
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
        $cards2 = ['Т♥', 'J♥'];
        $result2 = $this->scoringService->calculateHandValue($cards2);
        echo "Cards: " . implode(', ', $cards2) . " | Result: $result2 | Expected: 21\n";
        $this->assertEquals(21, $result2);
        
        // Тест 3: Джокер + карта (21)
        $cards3 = ['6♣', 'J♥'];
        $result3 = $this->scoringService->calculateHandValue($cards3);
        echo "Cards: " . implode(', ', $cards3) . " | Result: $result3 | Expected: 21\n";
        $this->assertEquals(21, $result3);
        
        // Тест 4: Два туза (22)
        $cards4 = ['Т♥', 'Т♠'];
        $result4 = $this->scoringService->calculateHandValue($cards4);
        echo "Cards: " . implode(', ', $cards4) . " | Result: $result4 | Expected: 22\n";
        $this->assertEquals(22, $result4);
        
        // Тест 5: Туз + Джокер (22)
        $cards5 = ['Т♥', '6♣'];
        $result5 = $this->scoringService->calculateHandValue($cards5);
        echo "Cards: " . implode(', ', $cards5) . " | Result: $result5 | Expected: 22\n";
        $this->assertEquals(22, $result5);
    }
    
    public function test_joker_specific_combinations()
    {
        echo "\n=== Testing Joker Specific Combinations ===\n";
        
        // Тест: Джокер + Туз + карта той же масти (32)
        $cards1 = ['6♣', 'Т♥', '10♥'];
        $result1 = $this->scoringService->calculateHandValue($cards1);
        echo "Cards: " . implode(', ', $cards1) . " | Result: $result1 | Expected: 32\n";
        $this->assertEquals(32, $result1);
        
        // Тест: Джокер с разными мастями (10 - минимальная)
        $cards2 = ['6♣', '10♠', '8♦'];
        $result2 = $this->scoringService->calculateHandValue($cards2);
        echo "Cards: " . implode(', ', $cards2) . " | Result: $result2 | Expected: 10\n";
        $this->assertEquals(10, $result2);
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
}