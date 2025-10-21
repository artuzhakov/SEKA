<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\Enums\PlayerAction;
use App\Domain\Game\Enums\PlayerStatus;
use DomainException;

class BiddingService
{
    /**
     * 🎯 Обработать действие игрока в торгах
     */
    public function processPlayerAction(
        Game $game, 
        Player $player, 
        PlayerAction $action, 
        ?int $betAmount = null
    ): void {
        // 🎯 Проверяем что игрок может сделать ход
        if (!$player->isPlaying()) {
            throw new DomainException('Player cannot make moves');
        }

        // 🎯 Проверяем что сейчас ход этого игрока
        if (!$this->isPlayerTurn($game, $player)) {
            throw new DomainException('Not your turn');
        }

        // 🎯 ОБНОВЛЯЕМ время последнего действия перед обработкой
        $player->updateLastActionTime();

        match ($action) {
            PlayerAction::FOLD => $this->processFold($player),
            PlayerAction::RAISE => $this->processRaise($player, $betAmount, $game),
            PlayerAction::CALL => $this->processCall($player, $game),
            PlayerAction::CHECK => $this->processCheck($player, $game),
            PlayerAction::REVEAL => $this->processReveal($player, $game),
            PlayerAction::DARK => $this->processDark($player),
            PlayerAction::OPEN => $this->processOpen($player),
            default => throw new DomainException('Unknown player action')
        };

        // 🎯 Переходим к следующему игроку
        $this->moveToNextPlayer($game);
    }

    /**
     * 🎯 Проверить очередь хода игрока
     */
    private function isPlayerTurn(Game $game, Player $player): bool
    {
        $currentPosition = $game->getCurrentPlayerPosition();
        return $currentPosition === $player->getPosition();
    }

    /**
     * 🎯 Пас - игрок выбывает из текущего раунда
     */
    private function processFold(Player $player): void
    {
        $player->fold();
    }

    /**
     * 🎯 Повышение ставки
     */
    private function processRaise(Player $player, ?int $betAmount, Game $game): void
    {
        if ($betAmount === null) {
            throw new DomainException('Bet amount required for raise');
        }

        // 🎯 Для темнящих игроков ставка в 2 раза меньше
        $effectiveBet = $player->getStatus() === PlayerStatus::DARK 
            ? (int)($betAmount / 2)
            : $betAmount;

        // 🎯 Проверяем что у игрока достаточно денег
        if ($player->getBalance() < $effectiveBet) {
            throw new DomainException('Insufficient funds');
        }

        $player->placeBet($effectiveBet);
        $game->setCurrentMaxBet($effectiveBet);
    }

    /**
     * 🎯 Поддержка ставки
     */
    private function processCall(Player $player, Game $game): void
    {
        $currentMaxBet = $game->getCurrentMaxBet();
        $playerBet = $player->getCurrentBet();
        
        if ($currentMaxBet > $playerBet) {
            $amountToCall = $currentMaxBet - $playerBet;
            
            // 🎯 Для темнящих игроков ставка в 2 раза меньше
            if ($player->getStatus() === PlayerStatus::DARK) {
                $amountToCall = (int)($amountToCall / 2);
            }

            if ($player->getBalance() < $amountToCall) {
                throw new DomainException('Insufficient funds to call');
            }

            $player->placeBet($amountToCall);
        }
    }

    /**
     * 🎯 Пропуск хода (только если нет текущих ставок)
     */
    private function processCheck(Player $player, Game $game): void
    {
        $currentMaxBet = $game->getCurrentMaxBet();
        $playerBet = $player->getCurrentBet();
        
        if ($currentMaxBet > $playerBet) {
            throw new DomainException('Cannot check when there is a bet to call');
        }
        
        // 🎯 Check не требует действий, просто пропускаем ход
    }

    /**
     * 🎯 Вскрытие - ставка в 2x от текущей максимальной
     */
    private function processReveal(Player $player, Game $game): void
    {
        $currentMaxBet = $game->getCurrentMaxBet();
        $revealBet = $currentMaxBet * 2;
        
        if ($player->getBalance() < $revealBet) {
            throw new DomainException('Insufficient funds for reveal');
        }

        $player->placeBet($revealBet);
        $player->reveal();
        $game->setCurrentMaxBet($revealBet);
    }

    /**
     * 🎯 Игра в темную
     */
    private function processDark(Player $player): void
    {
        // Проверяем что игрок еще не делал ставок в этом раунде
        if ($player->getCurrentBet() > 0) {
            throw new DomainException('Cannot play dark after making a bet');
        }
        
        $player->playDark();
    }

    /**
     * 🎯 Открытие карт после темной игры
     */
    private function processOpen(Player $player): void
    {
        if ($player->getStatus() !== PlayerStatus::DARK) {
            throw new DomainException('Can only open cards after playing dark');
        }
        
        $player->openCards();
    }

    /**
     * 🎯 Переход к следующему игроку
     */
    private function moveToNextPlayer(Game $game): void
    {
        $activePlayers = $game->getActivePlayers();
        $currentPosition = $game->getCurrentPlayerPosition();
        
        if (empty($activePlayers)) {
            return;
        }
        
        // 🎯 Находим текущего игрока по позиции
        $currentIndex = null;
        foreach ($activePlayers as $index => $player) {
            if ($player->getPosition() === $currentPosition) {
                $currentIndex = $index;
                break;
            }
        }
        
        if ($currentIndex !== null) {
            // 🎯 Переходим к следующему активному игроку
            $nextIndex = ($currentIndex + 1) % count($activePlayers);
            $nextPlayer = $activePlayers[$nextIndex];
            $game->setCurrentPlayerPosition($nextPlayer->getPosition());
            
            // 🎯 ОБНОВЛЯЕМ время действия для нового текущего игрока
            $nextPlayer->updateLastActionTime();
        }
    }

    /**
     * 🎯 Проверить завершение раунда торгов
     */
    public function isBiddingRoundComplete(Game $game): bool
    {
        $activePlayers = $game->getActivePlayers();
        
        // Остался один игрок - круг завершен
        if (count($activePlayers) <= 1) {
            return true;
        }
        
        $currentBet = $game->getCurrentMaxBet();
        $allActionsCompleted = true;
        
        foreach ($activePlayers as $player) {
            // Игрок не завершил круг если:
            // - Его ставка не равна текущей И он не пропустил ход
            // - ИЛИ он может сделать ход (не все игроки сделали равные ставки)
            if ($player->getCurrentBet() !== $currentBet && !$player->hasChecked()) {
                $allActionsCompleted = false;
                break;
            }
        }
        
        return $allActionsCompleted;
    }

    /**
     * 🎯 Получить текущего игрока
     */
    public function getCurrentPlayer(Game $game): ?Player
    {
        $currentPosition = $game->getCurrentPlayerPosition();
        if (!$currentPosition) {
            return null;
        }

        foreach ($game->getPlayers() as $player) {
            if ($player->getPosition() === $currentPosition && $player->isPlaying()) {
                return $player;
            }
        }

        return null;
    }

    /**
     * 🎯 Получить оставшееся время хода для текущего игрока
     */
    public function getRemainingTurnTime(Game $game): ?int
    {
        $currentPlayer = $this->getCurrentPlayer($game);
        return $currentPlayer ? $currentPlayer->getRemainingTurnTime() : null;
    }

    /**
     * 🎯 Проверить таймаут хода текущего игрока
     */
    public function isCurrentPlayerTurnTimedOut(Game $game): bool
    {
        $currentPlayer = $this->getCurrentPlayer($game);
        return $currentPlayer ? $currentPlayer->isTurnTimedOut() : false;
    }

    /**
     * 🎯 Автоматически обработать таймаут хода
     */
    public function processTurnTimeout(Game $game): void
    {
        $currentPlayer = $this->getCurrentPlayer($game);
        
        if ($currentPlayer && $currentPlayer->isTurnTimedOut()) {
            $this->processPlayerAction($game, $currentPlayer, PlayerAction::FOLD);
        }
    }

    /**
     * 🎯 Получить доступные действия с учетом круга
     */
    public function getAvailableActions(Game $game, Player $player): array
    {
        $currentRound = $game->getCurrentRound();
        $isRightOfDealer = $this->isPlayerRightOfDealer($game, $player);
        
        $actions = [PlayerAction::FOLD, PlayerAction::CALL, PlayerAction::RAISE];
        
        // Круг 1: ПРОПУСТИТЬ и ТЕМНАЯ только для игрока справа от дилера
        if ($currentRound === 1 && $isRightOfDealer && !$player->hasChecked()) {
            $actions[] = PlayerAction::CHECK;
            
            // ТЕМНАЯ доступна только если игрок еще не играл в темную в этой игре
            if (!$player->hasPlayedDark() && !$this->hasAnyPlayerPlayedDark($game)) {
                $actions[] = PlayerAction::DARK;
            }
        }
        
        // Круги 2-3: ВСКРЫТИЕ доступно, ПРОПУСТИТЬ/ТЕМНАЯ недоступны
        if ($currentRound >= 2) {
            $actions[] = PlayerAction::REVEAL;
            
            // ОТКРЫТЬ доступно только темнящим игрокам
            if ($player->getStatus() === PlayerStatus::DARK) {
                $actions[] = PlayerAction::OPEN;
            }
        }
        
        return $actions;
    }

    /**
     * 🎯 Проверить есть ли в игре темнящие игроки
     */
    private function hasAnyPlayerPlayedDark(Game $game): bool
    {
        foreach ($game->getPlayers() as $player) {
            if ($player->hasPlayedDark()) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 🎯 Проверить является ли игрок справа от дилера
     */
    private function isPlayerRightOfDealer(Game $game, Player $player): bool
    {
        $rightPlayer = $game->getPlayerRightOfDealer();
        return $rightPlayer && $rightPlayer->getId()->equals($player->getId());
    }

    public function shouldEndBiddingRound(Game $game): bool
    {
        $activePlayers = $game->getActivePlayers();
        
        // Остался один игрок
        if (count($activePlayers) <= 1) {
            return true;
        }
        
        // Все активные игроки сделали равные ставки И прошли полный круг
        $currentBet = $game->getCurrentMaxBet();
        $allBetsEqual = true;
        
        foreach ($activePlayers as $player) {
            if ($player->getCurrentBet() !== $currentBet && !$player->hasChecked()) {
                $allBetsEqual = false;
                break;
            }
        }
        
        return $allBetsEqual;
    }

    /**
     * 🎯 Переход к следующему кругу или завершение торгов
     */
    public function moveToNextRound(Game $game): void
    {
        $currentRound = $game->getCurrentRound();
        
        if ($currentRound < 3) {
            // Переход к следующему кругу
            $game->setCurrentRound($currentRound + 1);
            
            // Сброс состояний игроков для нового круга
            foreach ($game->getActivePlayers() as $player) {
                $player->resetForNewBiddingRound();
            }
            
            // Установка первого игрока нового круга (справа от дилера)
            $this->setFirstPlayerOfRound($game);
            
        } else {
            // Завершение торгов - переход к сравнению карт
            $this->finishBiddingPhase($game);
        }
    }

    /**
     * 🎯 Установить первого игрока круга (справа от дилера)
     */
    private function setFirstPlayerOfRound(Game $game): void
    {
        $rightPlayer = $game->getPlayerRightOfDealer();
        if ($rightPlayer) {
            $game->setCurrentPlayerPosition($rightPlayer->getPosition());
        }
    }

    /**
     * 🎯 Завершение фазы торгов
     */
    private function finishBiddingPhase(Game $game): void
    {
        // Здесь будет логика сравнения карт и определения победителя
        // Пока просто переводим в статус завершения
        $game->setStatus(GameStatus::FINISHED);
    }

}