<?php

namespace Tests\Unit\Application\Services;

use Tests\TestCase;
use App\Application\Services\DistributionService;
use App\Application\Services\BiddingService;
use App\Domain\Game\Entities\Game;
use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\GameMode;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\Enums\PlayerStatus;
use Mockery;

class DistributionServiceTest extends TestCase
{
    private DistributionService $distributionService;
    private $biddingServiceMock;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // СОЗДАЕМ И НАСТРАИВАЕМ МОК BiddingService
        $this->biddingServiceMock = Mockery::mock(BiddingService::class);
        
        // ДОБАВЬТЕ ЭТУ СТРОКУ - настройка ожидаемого вызова
        $this->biddingServiceMock->shouldReceive('startBiddingRound')
            ->andReturn(null);
        
        $this->distributionService = new DistributionService($this->biddingServiceMock);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /** @test */
    public function it_creates_simplified_deck_with_21_cards()
    {
        $deckInfo = $this->distributionService->getDeckInfo();
        
        $this->assertEquals(21, $deckInfo['total_cards']);
        $this->assertTrue($deckInfo['has_joker']);
        
        // Проверяем распределение по мастям (по 5 карт каждой масти + джокер)
        foreach ($deckInfo['cards_per_suit'] as $suit => $count) {
            $this->assertEquals(5, $count, "Suit {$suit} should have 5 cards");
        }
    }
    
    /** @test */
    public function it_distributes_3_cards_to_each_player()
    {
        $game = $this->createTestGameWithPlayers(3);
        $game->startBidding();

        echo "Before distribution - Game status: " . $game->getStatus()->value . "\n";
        echo "Before distribution - Active players: " . count($game->getActivePlayers()) . "\n";
        
        // Проверим, есть ли у игроков карты до распределения
        foreach ($game->getActivePlayers() as $index => $player) {
            $initialCards = count($player->getCards());
            echo "Player {$index} initial cards: {$initialCards}\n";
            $this->assertCount(0, $player->getCards(), "Player should have 0 cards before distribution");
        }
        
        $result = $this->distributionService->distributeCards($game);
        
        echo "After distribution - Active players: " . count($game->getActivePlayers()) . "\n";
        
        foreach ($game->getActivePlayers() as $index => $player) {
            $cardCount = count($player->getCards());
            echo "Player {$index} has {$cardCount} cards\n";
            
            $this->assertCount(3, $player->getCards(), "Player should have 3 cards but has {$cardCount}");
        }
        
        // 🎯 Дополнительная проверка результата
        $this->assertArrayHasKey('player_cards', $result);
        $this->assertCount(3, $result['player_cards']);
    }
    
    /** @test */
    public function it_redistributes_cards_for_quarrel()
    {
        $players = [
            $this->createTestPlayer(1),
            $this->createTestPlayer(2)
        ];
        
        foreach ($players as $player) {
            $player->receiveCards([$this->createTestCard('hearts', 'ace')]);
        }
        
        $this->distributionService->redistributeForQuarrel($players);
        
        foreach ($players as $player) {
            $this->assertCount(3, $player->getCards());
        }
    }
    
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
    
    private function createTestCard(string $suit, string $rank)
    {
        return new \App\Domain\Game\Entities\Card(
            \App\Domain\Game\Enums\CardSuit::from($suit),
            \App\Domain\Game\Enums\CardRank::from($rank)
        );
    }
}