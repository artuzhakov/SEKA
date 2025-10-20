<?php
// tests/Unit/Domain/Game/Rules/QuarrelRuleTest.php
namespace Tests\Unit\Domain\Game\Rules;

use Tests\TestCase;
use App\Domain\Game\Rules\QuarrelRule;

class QuarrelRuleTest extends TestCase
{
    private QuarrelRule $quarrelRule;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->quarrelRule = new QuarrelRule();
    }
    
    /** @test */
    public function it_allows_quarrel_when_tie_occurs_with_multiple_winners()
    {
        $game = $this->createGame(1, 'active');
        $player1 = $this->createPlayer(1);
        $player2 = $this->createPlayer(2);
        
        $this->assertTrue(
            $this->quarrelRule->canInitiateQuarrel($game, [$player1, $player2]),
            'Свара должна быть возможна при ничьей с 2+ победителями'
        );
    }
    
    /** @test */
    public function it_denies_quarrel_with_single_winner()
    {
        $game = $this->createGame(1, 'active');
        $player1 = $this->createPlayer(1);
        
        $this->assertFalse(
            $this->quarrelRule->canInitiateQuarrel($game, [$player1]),
            'Свара не должна быть возможна с одним победителем'
        );
    }
    
    /** @test */
    public function it_approves_quarrel_with_majority_vote()
    {
        $players = [
            $this->createPlayer(1),
            $this->createPlayer(2), 
            $this->createPlayer(3)
        ];
        
        $votes = [true, true, false];
        
        $this->assertTrue(
            $this->quarrelRule->winnersVoteForQuarrel($players, $votes),
            'Свара должна начаться при >50% голосов за'
        );
    }
    
    /** @test */
    public function it_denies_quarrel_with_minority_vote()
    {
        $players = [
            $this->createPlayer(1),
            $this->createPlayer(2),
            $this->createPlayer(3)
        ];
        
        $votes = [true, false, false];
        
        $this->assertFalse(
            $this->quarrelRule->winnersVoteForQuarrel($players, $votes),
            'Свара не должна начаться при <50% голосов за'
        );
    }
    
    /** @test */
    public function it_calculates_quarrel_entry_bet_correctly()
    {
        $game = $this->createGame(1, 'active');
        // Устанавливаем банк через рефлексию, так как нет сеттера
        $reflection = new \ReflectionClass($game);
        $property = $reflection->getProperty('bank');
        $property->setAccessible(true);
        $property->setValue($game, 300);
        
        $participants = [
            $this->createPlayer(1),
            $this->createPlayer(2),
            $this->createPlayer(3)
        ];
        
        $bet = $this->quarrelRule->calculateQuarrelEntryBet($game, $participants);
        
        $this->assertEquals(100, $bet, 'Ставка входа = банк / кол-во участников (300/3=100)');
    }
}