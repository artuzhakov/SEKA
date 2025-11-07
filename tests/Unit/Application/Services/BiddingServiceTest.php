<?php

namespace Tests\Unit\Application\Services;

use Tests\TestCase;
use App\Application\Services\BiddingService;
use App\Domain\Game\Entities\Game;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\GameMode;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\ValueObjects\GameId; // ДОБАВИТЬ этот импорт
use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\Enums\PlayerStatus;
use App\Domain\Game\Enums\PlayerAction;

class BiddingServiceTest extends TestCase
{
    private BiddingService $biddingService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->biddingService = new BiddingService();
    }
    
    /** @test */
    public function it_processes_fold_action()
    {
        $game = $this->createTestGameWithPlayers(3);
        $player = $game->getActivePlayers()[0];
        
        $this->biddingService->processPlayerAction($game, $player, PlayerAction::FOLD);
        
        $this->assertEquals(PlayerStatus::FOLDED, $player->getStatus());
        $this->assertEmpty($player->getCards());
    }
    
    /** @test */
    public function it_processes_raise_action()
    {
        $game = $this->createTestGameWithPlayers(3);
        $player = $game->getActivePlayers()[0];
        $initialBalance = $player->getBalance();
        
        $this->biddingService->processPlayerAction($game, $player, PlayerAction::RAISE, 100);
        
        $this->assertEquals($initialBalance - 100, $player->getBalance());
        $this->assertEquals(100, $player->getCurrentBet());
        $this->assertEquals(100, $game->getCurrentMaxBet());
    }
    
    /** @test */
    public function it_processes_dark_action()
    {
        $game = $this->createTestGameWithPlayers(3);
        $player = $game->getActivePlayers()[0];
        
        $this->biddingService->processPlayerAction($game, $player, PlayerAction::DARK);
        
        $this->assertEquals(PlayerStatus::DARK, $player->getStatus());
    }
    
    /** @test */
    public function it_throws_exception_when_raising_without_bet_amount()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Bet amount required for raise');
        
        $game = $this->createTestGameWithPlayers(3);
        $player = $game->getActivePlayers()[0];
        
        $this->biddingService->processPlayerAction($game, $player, PlayerAction::RAISE);
    }
    
    /** @test */
    public function it_throws_exception_when_insufficient_funds_for_raise()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Insufficient balance for bet');
        
        $game = $this->createTestGameWithPlayers(3);
        $player = $game->getActivePlayers()[0];
        
        // Пытаемся поставить больше чем есть баланс
        $this->biddingService->processPlayerAction($game, $player, PlayerAction::RAISE, 2000);
    }
    
    /** @test */
    public function it_moves_to_next_player_after_action()
    {
        $game = $this->createTestGameWithPlayers(3);
        $initialPosition = $game->getCurrentPlayerPosition();
        
        // Находим игрока по текущей позиции
        $player = $this->findPlayerByPosition($game, $initialPosition);
        $this->assertNotNull($player, "Player with position {$initialPosition} should exist");
        
        $this->biddingService->processPlayerAction($game, $player, PlayerAction::CHECK);
        
        $newPosition = $game->getCurrentPlayerPosition();
        $this->assertNotEquals($initialPosition, $newPosition, "Player position should change after action");
        
        // Проверяем что новый текущий игрок активен
        $newCurrentPlayer = $this->findPlayerByPosition($game, $newPosition);
        $this->assertNotNull($newCurrentPlayer, "New current player with position {$newPosition} should exist");
        $this->assertTrue($newCurrentPlayer->isPlaying(), "New current player should be active");
    }
    
    /** @test */
    public function test_round_1_available_actions()
    {
        $game = $this->createGameWithDealer(2); // Дилер на позиции 2
        $rightPlayer = $game->getPlayerRightOfDealer();
        
        $actions = $this->biddingService->getAvailableActions($game, $rightPlayer);
        
        $this->assertContains(PlayerAction::CHECK, $actions);
        $this->assertContains(PlayerAction::DARK, $actions);
    }
    
    /** @test */
    public function test_round_2_available_actions()
    {
        $game = $this->createGameWithDealer(2);
        $game->setCurrentRound(2);
        
        $player = $game->getPlayers()[0];
        
        // ДОБАВЬТЕ ЭТИ СТРОКИ - более точная симуляция
        $player->setPlayedDark(false); // 🎯 ИСПРАВЛЕНИЕ: Игрок НЕ играл в темную
        $player->setChecked(false);
        
        // 🎯 Устанавливаем равные ставки для возможности CHECK
        $game->setCurrentMaxBet(100);
        $player->setCurrentBet(100);
        
        $actions = $this->biddingService->getAvailableActions($game, $player);
        
        echo "Available actions for round 2: " . implode(', ', array_map(fn($a) => $a->value, $actions)) . "\n";
        
        $this->assertContains(PlayerAction::REVEAL, $actions);
        $this->assertNotContains(PlayerAction::DARK, $actions);
        
        // 🎯 В раунде 2 CHECK должен быть доступен если игрок не играл в темную
        if ($player->hasPlayedDark()) {
            $this->assertNotContains(PlayerAction::CHECK, $actions);
        } else {
            $this->assertContains(PlayerAction::CHECK, $actions);
        }
    }
    
    // Вспомогательные методы
    private function createTestGameWithPlayers(int $playerCount): Game
    {
        // Сначала создаем игру в статусе WAITING
        $game = new Game(
            GameId::fromInt(1), // Теперь GameId распознается
            GameStatus::WAITING,
            1,
            GameMode::OPEN
        );
        
        // Добавляем игроков (это разрешено в WAITING статусе)
        for ($i = 1; $i <= $playerCount; $i++) {
            $player = $this->createTestPlayer($i);
            $game->addPlayer($player);
        }
        
        // Переводим игру в статус BIDDING после добавления игроков
        $game->startBidding();
        
        // Устанавливаем первого игрока текущим
        $firstPlayer = $game->getActivePlayers()[0];
        $game->setCurrentPlayerPosition($firstPlayer->getPosition());
        
        return $game;
    }
    
    private function createTestPlayer(int $id): Player
    {
        return new Player(
            PlayerId::fromInt($id),
            $id,
            $id, // position = id
            PlayerStatus::ACTIVE,
            1000 // начальный баланс
        );
    }
    
    private function findPlayerByPosition(Game $game, int $position): ?Player
    {
        foreach ($game->getPlayers() as $player) {
            if ($player->getPosition() === $position) {
                return $player;
            }
        }
        return null;
    }

    private function createGameWithDealer(int $dealerPosition): Game
    {
        // ИСПРАВЛЕНО: создаем игру в статусе WAITING
        $game = new Game(
            GameId::fromInt(1), // Теперь GameId распознается
            GameStatus::WAITING, // WAITING чтобы можно было добавлять игроков
            1,
            GameMode::OPEN
        );
        
        // Добавляем 3 игроков
        for ($i = 1; $i <= 3; $i++) {
            $player = new Player(PlayerId::fromInt($i), $i, $i, PlayerStatus::ACTIVE, 1000);
            $game->addPlayer($player);
        }
        
        // Переводим в статус BIDDING после добавления игроков
        $game->startBidding();
        
        $game->setDealerPosition($dealerPosition);
        $game->setCurrentPlayerPosition($dealerPosition);
        
        return $game;
    }
}