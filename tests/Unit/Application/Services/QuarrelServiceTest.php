<?php

namespace Tests\Unit\Application\Services;

use Tests\TestCase;
use App\Application\Services\QuarrelService;
use App\Application\Services\DistributionService;
use App\Domain\Game\Rules\QuarrelRule;
use App\Domain\Game\Entities\Game;
use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\GameMode;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\Enums\PlayerStatus;

class QuarrelServiceTest extends TestCase
{
    private QuarrelService $quarrelService;
    private QuarrelRule $quarrelRule;
    private DistributionService $distributionService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->quarrelRule = new QuarrelRule();
        $this->distributionService = new DistributionService();
        $this->quarrelService = new QuarrelService(
            $this->quarrelRule,
            $this->distributionService
        );
    }
    
    /** @test */
    public function it_initiates_quarrel_when_approved_by_winners()
    {
        $game = $this->createTestGame();
        $winningPlayers = [
            $this->createTestPlayer(1),
            $this->createTestPlayer(2)
        ];
        
        // Мокаем QuarrelRule чтобы всегда разрешать сварку
        $mockQuarrelRule = $this->createMock(QuarrelRule::class);
        $mockQuarrelRule->method('canInitiateQuarrel')->willReturn(true);
        $mockQuarrelRule->method('winnersVoteForQuarrel')->willReturn(true);
        
        $quarrelService = new QuarrelService($mockQuarrelRule, $this->distributionService);
        
        $result = $quarrelService->initiateQuarrel($game, $winningPlayers);
        
        $this->assertTrue($result);
    }
    
    /** @test */
    public function it_does_not_initiate_quarrel_when_not_approved()
    {
        $game = $this->createTestGame();
        $winningPlayers = [
            $this->createTestPlayer(1),
            $this->createTestPlayer(2)
        ];
        
        // Мокаем QuarrelRule чтобы отклонять сварку
        $mockQuarrelRule = $this->createMock(QuarrelRule::class);
        $mockQuarrelRule->method('canInitiateQuarrel')->willReturn(true);
        $mockQuarrelRule->method('winnersVoteForQuarrel')->willReturn(false);
        
        $quarrelService = new QuarrelService($mockQuarrelRule, $this->distributionService);
        
        $result = $quarrelService->initiateQuarrel($game, $winningPlayers);
        
        $this->assertFalse($result);
    }
    
    /** @test */
    public function it_starts_quarrel_with_redistribution_and_bets()
    {
        // Создаем игру в статусе WAITING чтобы можно было добавить игроков
        $game = new Game(
            GameId::fromInt(1),
            GameStatus::WAITING, // WAITING вместо ACTIVE
            1,
            GameMode::OPEN,
            1000
        );
        
        $participants = [
            $this->createTestPlayer(1),
            $this->createTestPlayer(2)
        ];
        
        // Добавляем игроков (разрешено в WAITING)
        foreach ($participants as $player) {
            $game->addPlayer($player);
        }
        
        // Теперь переводим в ACTIVE
        $game->start(); // или $game->startBidding() в зависимости от реализации
        
        // Даем игрокам начальные карты
        foreach ($participants as $player) {
            $player->receiveCards([$this->createTestCard('hearts', 'ace')]);
            $player->placeBet(50);
        }
        
        $this->quarrelService->startQuarrel($game, $participants);
        
        // Проверяем что карты переразданы
        foreach ($participants as $player) {
            $this->assertCount(3, $player->getCards());
        }
        
        // Проверяем что ставки сделаны
        $totalBets = 0;
        foreach ($participants as $player) {
            $totalBets += $player->getCurrentBet();
        }
        $this->assertGreaterThan(0, $totalBets, 'Total player bets should be greater than 0');
    }
    
    /** @test */
    public function it_resolves_quarrel_with_single_winner()
    {
        $game = $this->createTestGame();
        $participants = [
            $this->createTestPlayerWithCards(1, [['hearts', 'ace'], ['hearts', 'ace'], ['hearts', 'ace']]), // 3 туза = 37 очков
            $this->createTestPlayerWithCards(2, [['diamonds', 'ace'], ['diamonds', 'king'], ['clubs', 'king']]) // 2 короля + туз = 22 очков
        ];
        
        // Добавим отладочную информацию
        $scoringRule = new \App\Domain\Game\Rules\ScoringRule();
        foreach ($participants as $player) {
            $score = $scoringRule->calculateScore($player->getCards());
            $cards = array_map(fn($card) => $card->getValue(), $player->getCards());
            echo "Player {$player->getUserId()}: " . implode(', ', $cards) . " = {$score} points\n";
        }
        
        $winners = $this->quarrelService->resolveQuarrel($game, $participants);
        
        echo "Winners count: " . count($winners) . "\n";
        foreach ($winners as $winner) {
            echo "Winner: Player {$winner->getUserId()}\n";
        }
        
        $this->assertCount(1, $winners);
        $this->assertEquals(1, $winners[0]->getUserId());
    }
    
    /** @test */
    public function it_resolves_quarrel_with_multiple_winners_on_tie()
    {
        $game = $this->createTestGame();
        $participants = [
            $this->createTestPlayerWithCards(1, [['hearts', 'ace'], ['hearts', 'king'], ['hearts', 'queen']]), // 31 очков
            $this->createTestPlayerWithCards(2, [['diamonds', 'ace'], ['diamonds', 'king'], ['diamonds', 'queen']]) // 31 очков
        ];
        
        $winners = $this->quarrelService->resolveQuarrel($game, $participants);
        
        $this->assertCount(2, $winners);
    }
    
    // Вспомогательные методы
    private function createTestGame(): Game
    {
        return new Game(
            GameId::fromInt(1),
            GameStatus::ACTIVE,
            1,
            GameMode::OPEN,
            1000 // банк
        );
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
    
    private function createTestPlayerWithCards(int $id, array $cardsData): Player
    {
        $player = $this->createTestPlayer($id);
        
        $cards = [];
        foreach ($cardsData as $cardData) {
            $cards[] = $this->createTestCard($cardData[0], $cardData[1]);
        }
        
        $player->receiveCards($cards);
        return $player;
    }
    
    private function createTestCard(string $suit, string $rank)
    {
        return new \App\Domain\Game\Entities\Card(
            \App\Domain\Game\Enums\CardSuit::from($suit),
            \App\Domain\Game\Enums\CardRank::from($rank)
        );
    }
}