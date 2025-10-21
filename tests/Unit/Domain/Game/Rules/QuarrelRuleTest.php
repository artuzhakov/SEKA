<?php
// tests/Unit/Domain/Game/Rules/QuarrelRuleTest.php

namespace Tests\Unit\Domain\Game\Rules;

use Tests\TestCase;
use App\Domain\Game\Rules\QuarrelRule;
use App\Domain\Game\Entities\Game;
use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\GameMode;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\Enums\PlayerStatus;

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
        // Создаем игру в WAITING статусе
        $game = new Game(
            GameId::fromInt(1),
            GameStatus::WAITING,
            1,
            GameMode::OPEN,
            0 // начальный банк
        );
        
        $player1 = $this->createTestPlayer(1);
        $player2 = $this->createTestPlayer(2);
        $player3 = $this->createTestPlayer(3);
        
        // Добавляем игроков
        $game->addPlayer($player1);
        $game->addPlayer($player2);
        $game->addPlayer($player3);
        
        // Устанавливаем ставки игроков (банк = сумма ставок)
        $player1->placeBet(100);
        $player2->placeBet(100);
        $player3->placeBet(100);
        
        // Теперь банк игры должен быть 300
        $participants = [$player1, $player2, $player3];
        
        $bet = $this->quarrelRule->calculateQuarrelEntryBet($game, $participants);
        
        $this->assertEquals(100, $bet, 'Ставка входа = банк / кол-во участников (300/3=100)');
    }
    
    // Вспомогательные методы - ИСПРАВЛЕНО: protected вместо private
    protected function createTestPlayer(int $id): Player
    {
        return new Player(
            PlayerId::fromInt($id),
            $id,
            $id,
            PlayerStatus::ACTIVE,
            1000
        );
    }
    
    protected function createTestGame(int $id, string $status): Game
    {
        return new Game(
            GameId::fromInt($id),
            GameStatus::from($status),
            1,
            GameMode::OPEN,
            0
        );
    }
}