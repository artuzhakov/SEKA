<?php
// app/Application/Services/GameService.php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\GameMode;
use App\Domain\Game\Rules\ScoringRule;
use App\Application\DTO\StartGameDTO;

class GameService
{
    public function __construct(
        private ?ScoringRule $scoringRule = null
    ) {
        $this->scoringRule = $scoringRule ?? new ScoringRule();
    }

    /**
     * 🎯 Начать новую игру
     */
    public function startNewGame(StartGameDTO $dto): Game
    {
        $game = new Game(
            GameId::fromInt(1), // 🎯 В реальности генерируем ID
            GameStatus::WAITING,
            $dto->roomId,
            GameMode::OPEN
        );

        // 🎯 ИСПРАВЛЕНИЕ: создаем игроков со статусом ACTIVE
        foreach ($dto->playerIds as $index => $playerId) {
            $player = new Player(
                \App\Domain\Game\ValueObjects\PlayerId::fromInt($playerId),
                $playerId,
                $index + 1, // позиция за столом
                \App\Domain\Game\Enums\PlayerStatus::ACTIVE, // 🎯 ИСПРАВЛЕНО: ACTIVE вместо WAITING
                1000 // начальный баланс
            );
            $game->addPlayer($player);
        }

        \Log::info("GameService: Created game with " . count($game->getPlayers()) . " ACTIVE players");

        return $game;
    }

    /**
     * 🎯 Определить победителей игры
     */
    public function determineWinners(Game $game): array
    {
        $winners = [];
        $highestScore = 0;

        foreach ($game->getActivePlayers() as $player) {
            $score = $this->scoringRule->calculateScore($player->getCards());
            
            if ($score > $highestScore) {
                $highestScore = $score;
                $winners = [$player];
            } elseif ($score === $highestScore) {
                $winners[] = $player;
            }
        }

        return $winners;
    }

    /**
     * 🎯 Может ли игра начаться (достаточно игроков)
     */
    public function canGameStart(Game $game): bool
    {
        return count($game->getActivePlayers()) >= 2;
    }

    /**
     * 🎯 Завершить игру и определить результаты
     */
    public function finishGame(Game $game): array
    {
        $winners = $this->determineWinners($game);
        
        // 🎯 Логика распределения банка
        $bank = $game->getBank();
        $winnerCount = count($winners);
        $prizePerWinner = $winnerCount > 0 ? (int)($bank / $winnerCount) : 0;

        return [
            'winners' => $winners,
            'prize_per_winner' => $prizePerWinner,
            'total_prize' => $bank
        ];
    }
}