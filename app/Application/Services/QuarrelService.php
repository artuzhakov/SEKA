<?php
// app/Application/Services/QuarrelService.php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\Rules\QuarrelRule;

class QuarrelService
{
    public function __construct(
        private QuarrelRule $quarrelRule,
        private DistributionService $distributionService
    ) {}

    /**
     * 🎯 Инициировать свару
     */
    public function initiateQuarrel(Game $game, array $winningPlayers): bool
    {
        if (!$this->quarrelRule->canInitiateQuarrel($game, $winningPlayers)) {
            return false;
        }

        // 🎯 Голосование победителей за свару
        $votes = $this->collectVotes($winningPlayers);
        $quarrelApproved = $this->quarrelRule->winnersVoteForQuarrel($winningPlayers, $votes);

        if ($quarrelApproved) {
            $game->initiateQuarrel($winningPlayers);
            return true;
        }

        return false;
    }

    /**
     * 🎯 Начать свару (после голосования)
     */
    public function startQuarrel(Game $game, array $participants): void
    {
        // 🎯 Перераздаем карты участникам свары
        $this->distributionService->redistributeForQuarrel($participants);

        // 🎯 Рассчитываем ставку входа в свару
        $entryBet = $this->quarrelRule->calculateQuarrelEntryBet($game, $participants);
        
        // 🎯 Игроки делают ставки для входа в свару
        foreach ($participants as $player) {
            $player->placeBet($entryBet);
        }
    }

    /**
     * 🎯 Собрать голоса игроков
     */
    private function collectVotes(array $players): array
    {
        $votes = [];
        
        // 🎯 В реальности здесь будет запрос к игрокам через WebSocket
        // Сейчас симулируем случайные голоса для тестов
        foreach ($players as $player) {
            $votes[] = (bool) random_int(0, 1);
        }

        return $votes;
    }

    /**
     * 🎯 Завершить свару и определить окончательных победителей
     */
    public function resolveQuarrel(Game $game, array $quarrelParticipants): array
    {
        // 🎯 Определяем победителей в сваре
        $scoringRule = new \App\Domain\Game\Rules\ScoringRule();
        $quarrelWinners = [];
        $highestScore = 0;

        foreach ($quarrelParticipants as $player) {
            $score = $scoringRule->calculateScore($player->getCards());
            
            if ($score > $highestScore) {
                $highestScore = $score;
                $quarrelWinners = [$player];
            } elseif ($score === $highestScore) {
                $quarrelWinners[] = $player;
            }
        }

        return $quarrelWinners;
    }
}