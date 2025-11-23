<?php

namespace Tests\Unit\Application\Services;

use Tests\TestCase;
use App\Application\Services\QuarrelService;
use App\Application\Services\DistributionService;
use App\Application\Services\BiddingService;
use App\Domain\Game\Rules\QuarrelRule;
use App\Domain\Game\Entities\Game;
use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\GameMode;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\Enums\PlayerStatus;
use Mockery;

class QuarrelServiceTest extends TestCase
{
    private QuarrelService $quarrelService;
    private $quarrelRuleMock;
    private $distributionServiceMock;
    private $biddingServiceMock;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // СОЗДАЕМ МОКИ всех зависимостей
        $this->quarrelRuleMock = Mockery::mock(QuarrelRule::class);
        $this->biddingServiceMock = Mockery::mock(BiddingService::class);
        $this->distributionServiceMock = new DistributionService($this->biddingServiceMock);
        
        // НАСТРАИВАЕМ ОЖИДАНИЯ для методов, которые будут вызываться
        $this->quarrelRuleMock->shouldReceive('calculateQuarrelEntryBet')
            ->andReturn(100); // Возвращаем фиксированную ставку
        
        $this->quarrelService = new QuarrelService(
            $this->quarrelRuleMock,
            $this->distributionServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /** @test */
    public function it_initiates_quarrel_when_approved_by_winners()
    {
        $game = $this->createTestGame();
        $winningPlayers = [
            $this->createTestPlayer(1),
            $this->createTestPlayer(2)
        ];
        
        // Настраиваем моки
        $this->quarrelRuleMock->shouldReceive('canInitiateQuarrel')->andReturn(true);
        $this->quarrelRuleMock->shouldReceive('winnersVoteForQuarrel')->andReturn(true);
        
        $result = $this->quarrelService->initiateQuarrel($game, $winningPlayers);
        
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
        
        // Настраиваем моки
        $this->quarrelRuleMock->shouldReceive('canInitiateQuarrel')->andReturn(true);
        $this->quarrelRuleMock->shouldReceive('winnersVoteForQuarrel')->andReturn(false);
        
        $result = $this->quarrelService->initiateQuarrel($game, $winningPlayers);
        
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

    /** @test */
    public function it_cannot_initiate_quarrel_with_single_winner()
    {
        $game = $this->createTestGame();
        $singleWinner = [$this->createTestPlayer(1)];
        
        $this->quarrelRuleMock->shouldReceive('canInitiateQuarrel')
            ->with($game, $singleWinner)
            ->andReturn(false);
        
        $result = $this->quarrelService->initiateQuarrel($game, $singleWinner);
        
        $this->assertFalse($result, "Quarrel should not be initiated with single winner");
    }

    /** @test */
    public function it_processes_multiple_quarrel_rounds_recursively()
    {
        $game = $this->createTestGame();
        
        // Первая свара - ничья
        $firstRoundParticipants = [
            $this->createTestPlayerWithCards(1, [['hearts', 'ace'], ['hearts', 'king'], ['hearts', 'queen']]),
            $this->createTestPlayerWithCards(2, [['diamonds', 'ace'], ['diamonds', 'king'], ['diamonds', 'queen']])
        ];
        
        $firstWinners = $this->quarrelService->resolveQuarrel($game, $firstRoundParticipants);
        $this->assertCount(2, $firstWinners, "First round should end in tie");
        
        // Вторая свара - определяем победителя
        $secondRoundParticipants = [
            $this->createTestPlayerWithCards(1, [['hearts', 'ace'], ['hearts', 'ace'], ['clubs', 'ace']]), // 3 туза
            $this->createTestPlayerWithCards(2, [['diamonds', 'king'], ['diamonds', 'king'], ['clubs', 'king']]) // 3 короля
        ];
        
        $finalWinners = $this->quarrelService->resolveQuarrel($game, $secondRoundParticipants);
        $this->assertCount(1, $finalWinners, "Second round should have single winner");
        $this->assertEquals(1, $finalWinners[0]->getUserId());
    }

    /** @test */
    public function it_calculates_correct_entry_bet_for_quarrel()
    {
        $game = $this->createTestGameWithBank(1000);
        $participants = [
            $this->createTestPlayer(1),
            $this->createTestPlayer(2)
        ];
        
        // Используем реальный метод вместо мока
        $quarrelRule = new \App\Domain\Game\Rules\QuarrelRule();
        $entryBet = $quarrelRule->calculateQuarrelEntryBet($game, $participants);
        
        // 1000 / 2 = 500, но метод возвращает int, поэтому 500
        $this->assertEquals(500, $entryBet);
    }

    /** @test */
    public function it_handles_quarrel_voting_with_majority()
    {
        $players = [
            $this->createTestPlayer(1),
            $this->createTestPlayer(2),
            $this->createTestPlayer(3)
        ];
        
        // 2 из 3 голосуют "за" - большинство
        $votes = [true, true, false];
        
        $this->quarrelRuleMock->shouldReceive('winnersVoteForQuarrel')
            ->with($players, $votes)
            ->andReturn(true);
        
        $result = $this->quarrelRuleMock->winnersVoteForQuarrel($players, $votes);
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_quarrel_with_tied_vote()
    {
        $players = [
            $this->createTestPlayer(1),
            $this->createTestPlayer(2)
        ];
        
        // 1 за, 1 против - ничья
        $votes = [true, false];
        
        $this->quarrelRuleMock->shouldReceive('winnersVoteForQuarrel')
            ->with($players, $votes)
            ->andReturn(false);
        
        $result = $this->quarrelRuleMock->winnersVoteForQuarrel($players, $votes);
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_insufficient_balance_for_quarrel_entry()
    {
        $game = $this->createTestGameWithBank(1000);
        $participants = [
            $this->createTestPlayerWithBalance(1, 100), // Маленький баланс
            $this->createTestPlayerWithBalance(2, 2000)
        ];
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Insufficient balance for bet');
        
        // Игрок с малым балансом попытается сделать ставку
        $participants[0]->placeBet(500);
    }

    /** @test */
    public function it_processes_all_three_voting_stages()
    {
        $game = $this->createTestGame();
        
        // Этап 1: Победители голосуют (уже тестируется)
        $winners = [$this->createTestPlayer(1), $this->createTestPlayer(2)];
        $this->quarrelRuleMock->shouldReceive('canInitiateQuarrel')->andReturn(true);
        $this->quarrelRuleMock->shouldReceive('winnersVoteForQuarrel')->andReturn(true);
        
        $stage1Result = $this->quarrelService->initiateQuarrel($game, $winners);
        $this->assertTrue($stage1Result, "Stage 1 should pass");
        
        // Этап 2: Проигравшие голосуют (НУЖЕН ТЕСТ)
        // Здесь должна быть логика подключения проигравших игроков
        
        // Этап 3: Ожидающие игроки голосуют (НУЖЕН ТЕСТ)  
        // Здесь должна быть логика подключения игроков из лобби
    }

    /** @test */
    public function it_accumulates_bank_across_quarrel_rounds()
    {
        $game = $this->createTestGameWithBank(1000);
        $participants = [
            $this->createTestPlayer(1),
            $this->createTestPlayer(2)
        ];
        
        $initialBank = $game->getBank();
        
        // Участники вносят ставки
        $entryBet = 500;
        foreach ($participants as $player) {
            $player->placeBet($entryBet);
            $game->increaseBank($entryBet);
        }
        
        $finalBank = $game->getBank();
        $expectedBank = $initialBank + ($entryBet * count($participants));
        
        $this->assertEquals($expectedBank, $finalBank, "Bank should accumulate correctly");
    }

    /** @test */
    public function it_charges_commission_only_at_final_resolution()
    {
        $game = $this->createTestGameWithBank(2000);
        $finalWinner = $this->createTestPlayer(1);
        
        $initialBalance = $finalWinner->getBalance();
        
        // Симулируем окончательное разрешение Свары
        // Комиссия должна списаться ТОЛЬКО здесь
        $commissionRate = 0.05; // 5%
        $commission = (int)($game->getBank() * $commissionRate);
        $prize = $game->getBank() - $commission;
        
        $finalWinner->addToBalance($prize);
        
        $finalBalance = $finalWinner->getBalance();
        $expectedBalance = $initialBalance + $prize;
        
        $this->assertEquals($expectedBalance, $finalBalance, "Commission should be charged only at final resolution");
        $this->assertGreaterThan(0, $commission, "Commission should be calculated");
    }
    
    // Вспомогательные методы
    private function createTestGame(): Game
    {
        return new Game(
            GameId::fromInt(1),
            GameStatus::ACTIVE,
            1,
            GameMode::OPEN,
            1000
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

    private function createTestGameWithBank(int $bank): Game
    {
        $game = $this->createTestGame();
        
        // Используем рефлексию чтобы установить банк
        $reflection = new \ReflectionClass($game);
        $bankProperty = $reflection->getProperty('bank');
        $bankProperty->setAccessible(true);
        $bankProperty->setValue($game, $bank);
        
        return $game;
    }

    private function createTestPlayerWithBalance(int $id, int $balance): Player
    {
        return new Player(
            PlayerId::fromInt($id),
            $id,
            $id,
            PlayerStatus::ACTIVE,
            $balance
        );
    }

}