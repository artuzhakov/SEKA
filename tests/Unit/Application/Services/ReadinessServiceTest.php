<?php

namespace Tests\Unit\Application\Services;

use Tests\TestCase;
use App\Application\Services\ReadinessService;
use App\Application\Services\BiddingService;
use App\Application\Services\ScoringService;
use App\Domain\Game\Entities\Game;
use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\GameMode;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\Enums\PlayerStatus;
use DomainException;

class ReadinessServiceTest extends TestCase
{
    private ReadinessService $readinessService;
    private BiddingService $biddingService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $scoringService = new ScoringService();
        $this->biddingService = new BiddingService($scoringService);
        
        $this->readinessService = new ReadinessService($this->biddingService);
    }
    
    /** @test */
    public function it_marks_player_as_ready()
    {
        $game = $this->createTestGameWithPlayers(3);
        $player = $game->getPlayers()[0];
        
        $this->readinessService->markPlayerReady($game, $player);
        
        $this->assertTrue($player->isReady());
        $this->assertNotNull($player->getReadyAt());
        $this->assertNotNull($player->getLastActionTime());
    }
    
    /** @test */
    public function it_throws_exception_when_marking_ready_in_active_game()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot mark ready when game is not in waiting state');
        
        $game = $this->createTestGameWithPlayers(3);
        $this->forceGameStatus($game, GameStatus::ACTIVE);
        
        $player = $game->getPlayers()[0];
        $this->readinessService->markPlayerReady($game, $player);
    }
    
    /** @test */
    public function it_starts_game_when_enough_players_are_ready()
    {
        $game = $this->createTestGameWithPlayers(3);
        $players = $game->getPlayers();
        
        // Отмечаем двух игроков готовыми
        $this->readinessService->markPlayerReady($game, $players[0]);
        $this->readinessService->markPlayerReady($game, $players[1]);
        
        $this->assertEquals(GameStatus::ACTIVE, $game->getStatus());
        $this->assertNotNull($game->getCurrentPlayerPosition());
    }
    
    /** @test */
    public function it_does_not_start_game_with_less_than_two_ready_players()
    {
        $game = $this->createTestGameWithPlayers(3);
        $player = $game->getPlayers()[0];
        
        $this->readinessService->markPlayerReady($game, $player);
        
        $this->assertEquals(GameStatus::WAITING, $game->getStatus());
        $this->assertNull($game->getCurrentPlayerPosition());
    }
    
    /** @test */
    public function it_detects_ready_timeouts()
    {
        $game = $this->createTestGameWithPlayers(3);
        $players = $game->getPlayers();
        
        // Симулируем прошедшее время для одного игрока
        $this->simulateTimePassed($players[0], 15); // 15 секунд прошло
        
        $timedOutPlayers = $this->readinessService->checkReadyTimeouts($game);
        
        $this->assertCount(1, $timedOutPlayers);
        $this->assertEquals(PlayerStatus::FOLDED, $players[0]->getStatus());
    }
    
    /** @test */
    public function it_detects_turn_timeouts()
    {
        $game = $this->createTestGameWithPlayers(3);
        $this->forceGameStatus($game, GameStatus::BIDDING);
        
        $players = $game->getPlayers();
        $game->setCurrentPlayerPosition($players[0]->getPosition());
        
        // Симулируем прошедшее время для хода
        $this->simulateTimePassed($players[0], 35); // 35 секунд прошло
        
        $timedOutPlayers = $this->readinessService->checkTurnTimeouts($game);
        
        $this->assertCount(1, $timedOutPlayers);
        $this->assertEquals(PlayerStatus::FOLDED, $players[0]->getStatus());
    }
    
    /** @test */
    public function it_returns_timers_info()
    {
        $game = $this->createTestGameWithPlayers(3);
        $players = $game->getPlayers();
        
        $this->readinessService->markPlayerReady($game, $players[0]);
        $game->setCurrentPlayerPosition($players[1]->getPosition());
        
        $timers = $this->readinessService->getTimersInfo($game);
        
        $this->assertCount(3, $timers);
        $this->assertTrue($timers[$players[0]->getUserId()]['is_ready']);
        $this->assertTrue($timers[$players[1]->getUserId()]['is_current_turn']);
        $this->assertFalse($timers[$players[2]->getUserId()]['is_current_turn']);
    }
    
    /** @test */
    public function it_returns_ready_players_count()
    {
        $game = $this->createTestGameWithPlayers(3);
        $players = $game->getPlayers();
        
        $this->readinessService->markPlayerReady($game, $players[0]);
        $this->readinessService->markPlayerReady($game, $players[1]);
        
        $readyCount = $this->readinessService->getReadyPlayersCount($game);
        
        $this->assertEquals(2, $readyCount);
    }
    
    /** @test */
    public function it_cancels_game_when_insufficient_players_after_timeout()
    {
        $game = $this->createTestGameWithPlayers(2);
        $players = $game->getPlayers();
        
        // Симулируем прошедшее время для обоих игроков
        $this->simulateTimePassed($players[0], 15);
        $this->simulateTimePassed($players[1], 15);
        
        $timedOutPlayers = $this->readinessService->checkReadyTimeouts($game);
        
        $this->assertCount(2, $timedOutPlayers);
        $this->assertEquals(GameStatus::CANCELLED, $game->getStatus());
    }
    
    /** @test */
    public function it_returns_time_until_game_start()
    {
        $game = $this->createTestGameWithPlayers(3);
        $players = $game->getPlayers();
        
        $timeUntilStart = $this->readinessService->getTimeUntilGameStart($game);
        
        $this->assertGreaterThan(0, $timeUntilStart);
        $this->assertLessThanOrEqual(10, $timeUntilStart);
    }
    
    /** @test */
    public function it_returns_zero_time_when_all_players_ready()
    {
        $game = $this->createTestGameWithPlayers(2);
        $players = $game->getPlayers();
        
        $this->readinessService->markPlayerReady($game, $players[0]);
        $this->readinessService->markPlayerReady($game, $players[1]);
        
        $timeUntilStart = $this->readinessService->getTimeUntilGameStart($game);
        
        $this->assertEquals(0, $timeUntilStart);
    }
    
    // Вспомогательные методы
    private function createTestGameWithPlayers(int $playerCount): Game
    {
        $game = new Game(
            GameId::fromInt(1),
            GameStatus::WAITING,
            1,
            GameMode::OPEN
        );
        
        for ($i = 1; $i <= $playerCount; $i++) {
            $player = $this->createTestPlayer($i);
            $game->addPlayer($player);
        }
        
        return $game;
    }
    
    private function createTestPlayer(int $id): Player
    {
        return new Player(
            PlayerId::fromInt($id),
            $id,
            $id,
            PlayerStatus::ACTIVE,
            1000
        );
    }
    
    private function forceGameStatus(Game $game, GameStatus $status): void
    {
        $reflection = new \ReflectionClass($game);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setAccessible(true);
        $statusProperty->setValue($game, $status);
    }
    
    private function simulateTimePassed(Player $player, int $seconds): void
    {
        $reflection = new \ReflectionClass($player);
        
        // Симулируем прошедшее время для готовности
        if (!$player->isReady()) {
            $readyAtProperty = $reflection->getProperty('readyAt');
            $readyAtProperty->setAccessible(true);
            $readyAtProperty->setValue($player, time() - $seconds);
        }
        
        // Симулируем прошедшее время для последнего действия
        $lastActionProperty = $reflection->getProperty('lastActionAt');
        $lastActionProperty->setAccessible(true);
        $lastActionProperty->setValue($player, time() - $seconds);
    }
}