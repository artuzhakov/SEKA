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
}