<?php
declare(strict_types=1);

namespace App\Domain\Game\Rules;

class ScoringRule
{
    public function calculateScore(array $cards): int
    {
        $score = 0;
        foreach ($cards as $card) {
            $rank = $card->getRank()->value;
            switch ($rank) {
                case 'ace': $score += 11; break;
                case 'king': $score += 4; break;
                case 'queen': $score += 3; break;
                case 'jack': $score += 2; break;
                case 'ten': $score += 10; break;
                case '6': $score += 6; break;
                default: $score += 0;
            }
        }
        return $score;
    }
}