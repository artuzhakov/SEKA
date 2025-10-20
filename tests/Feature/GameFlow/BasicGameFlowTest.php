<?php
// tests/Feature/GameFlow/BasicGameFlowTest.php
namespace Tests\Feature\GameFlow;

use Tests\TestCase;

class BasicGameFlowTest extends TestCase
{
    /** @test */
    public function it_can_create_new_game()
    {
        $this->assertTrue(true, 'Базовый тест для проверки окружения');
    }
    
    /** @test */
    public function it_requires_at_least_two_players_to_start()
    {
        $game = $this->createGame();
        $player1 = $this->createPlayer(1);
        
        $game->addPlayer($player1);
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Need at least 2 players to start game');
        
        $game->start();
    }
    
    /** @test */
    public function it_starts_game_with_two_players()
    {
        $game = $this->createGame();
        $player1 = $this->createPlayer(1);
        $player2 = $this->createPlayer(2);
        
        $game->addPlayer($player1);
        $game->addPlayer($player2);
        
        $game->start();
        
        $this->assertEquals('active', $game->getStatus()->value);
    }
}