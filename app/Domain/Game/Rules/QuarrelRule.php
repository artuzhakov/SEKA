<?php
// app/Domain/Game/Rules/QuarrelRule.php
declare(strict_types=1);

namespace App\Domain\Game\Rules;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\Entities\Player;

class QuarrelRule
{
    public function canInitiateQuarrel(Game $game, array $winningPlayers): bool
    {
        return count($winningPlayers) >= 2;
    }
    
    public function winnersVoteForQuarrel(array $winningPlayers, array $votes): bool
    {
        $yesVotes = count(array_filter($votes, fn($vote) => $vote === true));
        return ($yesVotes / count($winningPlayers)) > 0.5;
    }
    
    public function calculateQuarrelEntryBet(Game $game, array $participants): int
    {
        $totalBank = $game->getBank();
        $winnerCount = count($participants);
        
        return $winnerCount > 0 ? (int)($totalBank / $winnerCount) : 0;
    }

    public function calculateScore(array $cards): int
    {
        // Простая заглушка для тестов
        // В реальности здесь будет сложная логика подсчета очков
        $score = 0;
        
        foreach ($cards as $card) {
            // Простой подсчет для тестов
            $rank = $card->getRank()->value;
            switch ($rank) {
                case 'ace': $score += 11; break;
                case 'king': $score += 4; break;
                case 'queen': $score += 3; break;
                case 'jack': $score += 2; break;
                case 'ten': $score += 10; break;
                case '6': $score += 6; break; // Джокер
                default: $score += 0;
            }
        }
        
        return $score;
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
}