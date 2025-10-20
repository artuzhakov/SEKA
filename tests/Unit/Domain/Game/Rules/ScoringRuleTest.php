<?php
// tests/Unit/Domain/Game/Rules/ScoringRuleTest.php

namespace Tests\Unit\Domain\Game\Rules;

use Tests\TestCase;
use App\Domain\Game\Rules\ScoringRule;

class ScoringRuleTest extends TestCase
{
    private ScoringRule $scoringRule;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->scoringRule = new ScoringRule();
    }
    
    /** @test */
    public function it_calculates_seka_aces_with_three_aces()
    {
        $cards = [
            $this->createCard('hearts', 'ace'),
            $this->createCard('diamonds', 'ace'), 
            $this->createCard('clubs', 'ace')
        ];
        
        $score = $this->scoringRule->calculateScore($cards);
        
        $this->assertEquals(37, $score, '3 туза = СЕКА ТУЗОВ (37 очков)');
    }
    
    /** @test */
    public function it_calculates_seka_aces_with_two_aces_and_joker()
    {
        $cards = [
            $this->createCard('hearts', 'ace'),
            $this->createCard('diamonds', 'ace'),
            $this->createCard('clubs', 'six')
        ];
        
        $score = $this->scoringRule->calculateScore($cards);
        
        $this->assertEquals(37, $score, '2 туза + джокер = СЕКА ТУЗОВ (37 очков)');
    }
    
    /** @test */
    public function it_calculates_seka_kings_with_three_kings()
    {
        $cards = [
            $this->createCard('hearts', 'king'),
            $this->createCard('diamonds', 'king'),
            $this->createCard('clubs', 'king')
        ];
        
        $score = $this->scoringRule->calculateScore($cards);
        
        $this->assertEquals(36, $score, '3 короля = СЕКА КОРОЛЕЙ (36 очков)');
    }
    
    /** @test */
    public function it_calculates_seka_mastey_with_three_same_suit_cards()
    {
        $cards = [
            $this->createCard('hearts', 'queen'),
            $this->createCard('hearts', 'jack'),
            $this->createCard('hearts', 'ten')
        ];
        
        $score = $this->scoringRule->calculateScore($cards);
        
        $this->assertEquals(30, $score, '3 карты одной масти = СЕКА МАСТЕЙ (30 очков)');
    }
    
    /** @test */
    public function it_calculates_31_points_with_three_same_suit_and_ace()
    {
        $cards = [
            $this->createCard('clubs', 'ace'),
            $this->createCard('clubs', 'queen'),
            $this->createCard('clubs', 'jack')
        ];
        
        $score = $this->scoringRule->calculateScore($cards);
        
        $this->assertEquals(31, $score, '3 карты одной масти + туз = 31 очко');
    }
    
    /** @test */
    public function it_calculates_22_points_with_two_aces()
    {
        $cards = [
            $this->createCard('hearts', 'ace'),
            $this->createCard('diamonds', 'ace'),
            $this->createCard('clubs', 'ten')
        ];
        
        $score = $this->scoringRule->calculateScore($cards);
        
        $this->assertEquals(22, $score, '2 туза = ДВА ЛБА (22 очка)');
    }
    
    /** @test */
    public function it_calculates_11_points_with_ace_and_different_suits()
    {
        $cards = [
            $this->createCard('hearts', 'ace'),
            $this->createCard('diamonds', 'queen'),
            $this->createCard('clubs', 'jack')
        ];
        
        $score = $this->scoringRule->calculateScore($cards);
        
        $this->assertEquals(11, $score, 'Туз + разные масти = 11 очков');
    }
    
    /** @test */
    public function it_calculates_10_points_with_no_special_combinations()
    {
        $cards = [
            $this->createCard('hearts', 'queen'),
            $this->createCard('diamonds', 'jack'),
            $this->createCard('clubs', 'ten')
        ];
        
        $score = $this->scoringRule->calculateScore($cards);
        
        $this->assertEquals(10, $score, 'Нет комбинаций = 10 очков');
    }
}