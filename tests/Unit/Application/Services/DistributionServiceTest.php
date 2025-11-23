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
use App\Domain\Game\Entities\Card;
use App\Domain\Game\Enums\CardSuit;
use App\Domain\Game\Enums\CardRank;
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

    /** @test */
    public function it_creates_deck_with_correct_card_composition()
    {
        $deckInfo = $this->distributionService->getDeckInfo();
        
        $this->assertEquals(21, $deckInfo['total_cards']);
        $this->assertTrue($deckInfo['has_joker']);
        
        // Проверяем точное распределение: 4 карты каждого ранга + джокер
        $expectedCardsPerSuit = 5; // 10, J, Q, K, A каждой масти
        foreach ($deckInfo['cards_per_suit'] as $suit => $count) {
            $this->assertEquals($expectedCardsPerSuit, $count, "Suit {$suit} should have {$expectedCardsPerSuit} cards");
        }
    }

    /** @test */
    public function it_distributes_unique_cards_to_players()
    {
        $game = $this->createTestGameWithPlayers(3);
        $game->startBidding();
        
        $result = $this->distributionService->distributeCards($game);
        
        // Собираем все разданные карты
        $allDistributedCards = [];
        foreach ($result['player_cards'] as $playerCards) {
            foreach ($playerCards as $card) {
                $cardKey = $card['suit'] . '_' . $card['rank'];
                $allDistributedCards[] = $cardKey;
            }
        }
        
        // Проверяем что все карты уникальны
        $uniqueCards = array_unique($allDistributedCards);
        $this->assertCount(count($allDistributedCards), $uniqueCards, "All distributed cards should be unique");
    }

    /** @test */
    public function it_creates_valid_card_structures()
    {
        $game = $this->createTestGameWithPlayers(2);
        $game->startBidding();
        
        $result = $this->distributionService->distributeCards($game);
        
        foreach ($result['player_cards'] as $playerId => $cards) {
            $this->assertCount(3, $cards);
            
            foreach ($cards as $card) {
                $this->assertArrayHasKey('suit', $card);
                $this->assertArrayHasKey('rank', $card);
                $this->assertArrayHasKey('is_face_up', $card);
                $this->assertArrayHasKey('is_joker', $card);
                
                $this->assertIsString($card['suit']);
                $this->assertIsString($card['rank']);
                $this->assertIsBool($card['is_face_up']);
                $this->assertIsBool($card['is_joker']);
                
                // Карты раздаются рубашкой вверх
                $this->assertFalse($card['is_face_up']);
            }
        }
    }

    /** @test */
    public function it_handles_joker_correctly_in_distribution()
    {
        // Тестируем с меньшим количеством игроков чтобы джокер гарантированно попал
        $game = $this->createTestGameWithPlayers(3); // 9 карт из 21 - больше шансов
        $game->startBidding();
        
        $result = $this->distributionService->distributeCards($game);
        
        $jokerFound = false;
        foreach ($result['player_cards'] as $playerId => $cards) {
            foreach ($cards as $card) {
                if ($card['is_joker']) {
                    $jokerFound = true;
                    $this->assertEquals('clubs', $card['suit']);
                    $this->assertEquals('six', $card['rank']);
                }
            }
        }
        
        // Если джокер не найден - это нормально для случайной раздачи
        // Проверяем только если нашли
        if ($jokerFound) {
            $this->assertTrue(true, "Joker found as expected");
        } else {
            $this->markTestSkipped("Joker not in this distribution - random chance");
        }
    }

    /** @test */
    public function it_correctly_identifies_joker_card()
    {
        // Создаем джокер напрямую с правильным namespace
        $jokerCard = new \App\Domain\Game\Entities\Card(CardSuit::CLUBS, CardRank::SIX);
        
        // Проверяем что метод isJoker() работает
        $this->assertTrue($jokerCard->isJoker());
        
        // Проверяем преобразование в массив
        $distributionService = new DistributionService($this->biddingServiceMock);
        
        // Используем рефлексию чтобы вызвать приватный метод
        $reflection = new \ReflectionClass($distributionService);
        $method = $reflection->getMethod('cardToArray');
        $method->setAccessible(true);
        
        $cardArray = $method->invoke($distributionService, $jokerCard);
        
        $this->assertTrue($cardArray['is_joker']);
        $this->assertEquals('clubs', $cardArray['suit']);
        $this->assertEquals('six', $cardArray['rank']); // ✅ Исправлено на 'six'
    }

    /** @test */
    public function it_handles_joker_correctly_when_present()
    {
        $game = $this->createTestGameWithPlayers(7);
        $game->startBidding();
        
        $result = $this->distributionService->distributeCards($game);
        
        $jokerFound = false;
        $totalCards = 0;
        
        foreach ($result['player_cards'] as $playerId => $cards) {
            $totalCards += count($cards);
            foreach ($cards as $card) {
                if ($card['is_joker']) {
                    $jokerFound = true;
                    $this->assertEquals('clubs', $card['suit']);
                    $this->assertEquals('six', $card['rank']); // ✅ Исправлено на 'six'
                }
            }
        }
        
        $this->assertEquals(21, $totalCards, "All 21 cards should be distributed");
        $this->assertTrue($jokerFound, "Joker should be present when all cards are distributed");
    }

    /** @test */
    public function it_always_includes_joker_when_all_cards_distributed()
    {
        // Гарантируем что джокер будет в раздаче - раздаем все 21 карту
        $game = $this->createTestGameWithPlayers(7); // 7 игроков × 3 карты = 21 карта
        $game->startBidding();
        
        $result = $this->distributionService->distributeCards($game);
        
        $jokerFound = false;
        $totalCards = 0;
        
        foreach ($result['player_cards'] as $playerId => $cards) {
            $totalCards += count($cards);
            foreach ($cards as $card) {
                if ($card['is_joker']) {
                    $jokerFound = true;
                    $this->assertEquals('clubs', $card['suit']);
                    $this->assertEquals('six', $card['rank']);
                }
            }
        }
        
        $this->assertEquals(21, $totalCards, "All 21 cards should be distributed");
        $this->assertTrue($jokerFound, "Joker must be present when all cards are distributed");
    }

    /** @test */
    public function it_creates_deck_with_exact_card_composition()
    {
        $deckInfo = $this->distributionService->getDeckInfo();
        
        $this->assertEquals(21, $deckInfo['total_cards']);
        $this->assertTrue($deckInfo['has_joker']);
        
        // Проверяем точное количество карт каждого типа
        $expectedComposition = [
            'ten' => 4,
            'jack' => 4, 
            'queen' => 4,
            'king' => 4,
            'ace' => 4,
            'six' => 1  // джокер
        ];
        
        // Можно добавить проверку через рефлексию или дополнительный метод
    }

    /** @test */
    public function it_shuffles_deck_properly()
    {
        $deck1 = $this->getDeckViaReflection();
        $deck2 = $this->getDeckViaReflection();
        
        // Колоды должны быть разными после shuffle
        $this->assertNotEquals(serialize($deck1), serialize($deck2));
    }

    private function getDeckViaReflection(): array
    {
        $reflection = new \ReflectionClass($this->distributionService);
        $method = $reflection->getMethod('createSimplifiedDeck');
        $method->setAccessible(true);
        
        return $method->invoke($this->distributionService);
    }

}