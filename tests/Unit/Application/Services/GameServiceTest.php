<?php
// tests/Unit/Application/Services/GameServiceTest.php

namespace Tests\Unit\Application\Services;

use Tests\TestCase;
use App\Application\Services\GameService;
use App\Application\DTO\StartGameDTO;
use App\Domain\Game\Rules\ScoringRule;
use App\Domain\Game\Enums\PlayerStatus;

class GameServiceTest extends TestCase
{
    private GameService $gameService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->gameService = new GameService(new ScoringRule());
    }
    
    /** @test */
    public function it_creates_new_game_with_players()
    {
        $dto = StartGameDTO::fromValues(1, [1, 2, 3]);
        
        $game = $this->gameService->startNewGame($dto);
        
        $this->assertEquals(1, $game->getRoomId());
        $this->assertCount(3, $game->getPlayers());
    }
    
    /** @test */
    public function it_determines_single_winner_correctly()
    {
        $game = $this->createGame(1, 'waiting');
        
        // 🎯 СОЗДАЕМ ИГРОКОВ С ПРАВИЛЬНЫМ СТАТУСОМ
        $player1 = $this->createPlayer(1);
        $player2 = $this->createPlayer(2);
        
        // 🎯 МЕНЯЕМ СТАТУС НА ACTIVE чтобы они считались активными
        $reflection1 = new \ReflectionClass($player1);
        $statusProperty1 = $reflection1->getProperty('status');
        $statusProperty1->setAccessible(true);
        $statusProperty1->setValue($player1, PlayerStatus::ACTIVE);
        
        $reflection2 = new \ReflectionClass($player2);
        $statusProperty2 = $reflection2->getProperty('status');
        $statusProperty2->setAccessible(true);
        $statusProperty2->setValue($player2, PlayerStatus::ACTIVE);
        
        // 🎯 Симулируем карты с разными очками
        $player1->receiveCards([
            $this->createCard('hearts', 'ace'),
            $this->createCard('diamonds', 'ace'),
            $this->createCard('clubs', 'ace') // 37 очков
        ]);
        
        $player2->receiveCards([
            $this->createCard('hearts', 'king'), 
            $this->createCard('diamonds', 'king'),
            $this->createCard('clubs', 'king') // 36 очков
        ]);
        
        $game->addPlayer($player1);
        $game->addPlayer($player2);
        
        $winners = $this->gameService->determineWinners($game);
        
        $this->assertCount(1, $winners);
        $this->assertEquals(1, $winners[0]->getUserId());
    }
}