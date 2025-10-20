<?php
// app/Domain/Game/Services/TurnService.php
declare(strict_types=1);

namespace App\Domain\Game\Services;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\Entities\Player;

class TurnService
{
    public function getNextPlayer(Game $game, Player $currentPlayer): ?Player
    {
        $players = $game->getActivePlayers();
        
        if (count($players) === 0) {
            return null;
        }
        
        // Сортируем по позициям
        usort($players, fn(Player $a, Player $b) => $a->getPosition() <=> $b->getPosition());
        
        // Находим текущего игрока
        $currentIndex = -1;
        foreach ($players as $index => $player) {
            if ($player->getId()->equals($currentPlayer->getId())) {
                $currentIndex = $index;
                break;
            }
        }
        
        if ($currentIndex === -1) return $players[0] ?? null;
        
        // Ищем следующего активного игрока
        for ($i = 1; $i <= count($players); $i++) {
            $nextIndex = ($currentIndex + $i) % count($players);
            $nextPlayer = $players[$nextIndex];
            
            if ($nextPlayer->isPlaying()) {
                return $nextPlayer;
            }
        }
        
        return null;
    }
}