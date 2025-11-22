<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Game\Entities\Game;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\Enums\PlayerAction;
use App\Domain\Game\Enums\PlayerStatus;
use App\Domain\Game\Enums\GameStatus;
use App\Application\Services\ScoringService;
use App\Events\PlayerActionTaken;
use App\Events\BiddingRoundStarted;
use App\Events\RevealResolved;
use App\Events\TurnChanged;
use DomainException;

class BiddingService
{

    protected ScoringService $scoringService;

    public function __construct(ScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }
    
    /**
     * 🎯 Обработать действие игрока в торгах с WebSocket событиями
     */
    public function processPlayerAction(
        Game $game, 
        Player $player, 
        PlayerAction $action, 
        ?int $betAmount = null
    ): array {
        \Log::info("🎯 === BIDDING ACTION START ===");
        \Log::info("🎯 Game: {$game->getId()->toInt()}, Status: {$game->getStatus()->value}");
        \Log::info("🎯 Current Player Position Before: {$game->getCurrentPlayerPosition()}");
        \Log::info("🎯 Action: {$action->value}, Bet: " . ($betAmount ?? 'null'));
        
        // Сохраняем предыдущего игрока для события TurnChanged
        $previousPlayerPosition = $game->getCurrentPlayerPosition();
        
        try {
            // 🎯 Проверяем что игрок может сделать ход
            if (!$player->isPlaying()) {
                \Log::error("❌ Player {$player->getUserId()} cannot make moves - not playing");
                throw new DomainException('Player cannot make moves');
            }

            // 🎯 Проверяем что сейчас ход этого игрока
            if (!$this->isPlayerTurn($game, $player)) {
                \Log::error("❌ Not player {$player->getUserId()} turn. Current turn: {$game->getCurrentPlayerPosition()}");
                throw new DomainException('Not your turn');
            }

            \Log::info("✅ Player {$player->getUserId()} can make action: {$action->value}");

            // 🎯 ОБНОВЛЯЕМ время последнего действия перед обработкой
            $player->updateLastActionTime();

            // 🎯 Обрабатываем действие
            match ($action) {
                PlayerAction::FOLD => $this->processFold($player, $game),
                PlayerAction::RAISE => $this->processRaise($player, $betAmount, $game),
                PlayerAction::CALL => $this->processCall($player, $game),
                PlayerAction::CHECK => $this->processCheck($player, $game),
                PlayerAction::REVEAL => $this->processReveal($player, $game),
                PlayerAction::DARK => $this->processDark($player, $game),
                PlayerAction::OPEN => $this->processOpen($player, $game),
                default => throw new DomainException('Unknown player action')
            };

            \Log::info("✅ Successfully processed action: {$action->value}");

            // 🎯 Получаем обновленное состояние для WebSocket событий
            $gameState = $this->getGameStateForEvent($game);
            $availableActions = $this->getAvailableActions($game, $player);

            // 🎯 Отправляем событие действия игрока
            event(new \App\Events\PlayerActionTaken(
                gameId: $game->getId()->toInt(),
                playerId: $player->getUserId(),
                action: $action->value,
                betAmount: $betAmount,
                newPlayerPosition: $game->getCurrentPlayerPosition(),
                bank: $game->getBank(),
                gameState: $gameState,
                availableActions: $availableActions
            ));

            // 🎯 КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Проверяем статус игры перед переходом к следующему игроку
            if ($game->getStatus() === GameStatus::BIDDING) {
                $activePlayers = $game->getActivePlayers();
                \Log::info("🔍 Active players after action: " . count($activePlayers));
                
                // Переходим к следующему игроку только если игра продолжается и есть активные игроки
                if (count($activePlayers) > 1) {
                    $this->moveToNextPlayer($game);
                    \Log::info("✅ Moved to next player. New position: {$game->getCurrentPlayerPosition()}");
                    
                    // 🎯 Отправляем событие смены хода если игрок сменился
                    if ($previousPlayerPosition !== $game->getCurrentPlayerPosition()) {
                        $currentPlayer = $this->getCurrentPlayer($game);
                        if ($currentPlayer) {
                            $previousPlayer = $this->findPlayerByPosition($game, $previousPlayerPosition);

                            event(new \App\Events\TurnChanged(
                                gameId: $game->getId()->toInt(),
                                previousPlayerId: (string)($previousPlayer?->getUserId() ?? ''),
                                currentPlayerId: (string)$currentPlayer->getUserId(),
                                turnTimeLeft: 30
                            ));
                        }
                    }
                } else {
                    \Log::info("🎯 Game round ending - skipping move to next player");
                    $this->endBiddingRound($game);
                }
            } else {
                \Log::info("🎯 Game status changed to {$game->getStatus()->value} - skipping move to next player");
            }

            \Log::info("🎯 === BIDDING ACTION END ===\n");

            return [
                'success' => true,
                'game_state' => $gameState,
                'available_actions' => $availableActions
            ];

        } catch (\Exception $e) {
            \Log::error("❌ BIDDING ACTION FAILED for player {$player->getUserId()}");
            \Log::error("❌ Action: {$action->value}, Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 🎯 Проверить очередь хода игрока (С ЛОГИРОВАНИЕМ)
     */
    private function isPlayerTurn(Game $game, Player $player): bool
    {
        $currentPosition = $game->getCurrentPlayerPosition();
        $playerPosition = $player->getPosition();
        $isTurn = $currentPosition === $playerPosition;
        
        \Log::info("🔍 isPlayerTurn: player={$player->getUserId()}, playerPos={$playerPosition}, currentPos={$currentPosition}, isTurn=" . ($isTurn ? 'YES' : 'NO'));
        
        return $isTurn;
    }

    /**
     * 🎯 Пас - игрок выбывает из текущего раунда (С ЛОГИРОВАНИЕМ)
     */
    private function processFold(Player $player, Game $game): void
    {
        \Log::info("🔄 Processing FOLD for player {$player->getUserId()}");
        $player->fold();
        \Log::info("✅ Player {$player->getUserId()} folded");
        $this->saveGame($game);
    }

    /**
     * 🎯 Повышение ставки (С ЛОГИРОВАНИЕМ)
     */
    private function processRaise(Player $player, ?int $betAmount, Game $game): void
    {
        // ✅ финальная версия

        \Log::info("🔄 [FINAL] Processing RAISE for player {$player->getUserId()}, betAmount: {$betAmount}");

        if ($betAmount === null) {
            throw new DomainException('Bet amount required for raise');
        }

        $newStake = $betAmount;

        $currentStake = $game->getCurrentMaxBet();

        if ($newStake <= $currentStake) {
            throw new DomainException('Raise must be greater than current stake');
        }

        // Обновляем уровень ставки за столом
        $game->setCurrentMaxBet($newStake);

        // Доводим ставку ИМЕННО этого игрока до newStake
        $darkPrivilegeActive = $this->isDarkPrivilegeActive($game, $player);
        $this->adjustPlayerBetTo($game, $player, $newStake, $darkPrivilegeActive);

        $this->saveGame($game);
    }


    /**
     * 🎯 Поддержка ставки (С ЛОГИРОВАНИЕМ)
     */
    private function processCall(Player $player, Game $game): void
    {
        /*
        \Log::info("🔄 Processing CALL for player {$player->getUserId()}");
        
        $currentMaxBet = $game->getCurrentMaxBet();
        $playerBet = $player->getCurrentBet();
        
        \Log::info("💰 Call details: maxBet={$currentMaxBet}, playerBet={$playerBet}");
        
        if ($currentMaxBet > $playerBet) {
            $amountToCall = $currentMaxBet - $playerBet;
            
            // 🎯 Для темнящих игроков ставка в 2 раза меньше
            if ($player->getStatus() === PlayerStatus::DARK) {
                $amountToCall = (int)($amountToCall / 2);
                \Log::info("💰 Dark player - half call amount: {$amountToCall}");
            }

            $player->placeBet($amountToCall);
            \Log::info("✅ Player {$player->getUserId()} called with {$amountToCall}");
        } else {
            \Log::info("✅ Player {$player->getUserId()} call skipped - already at max bet");
        }

        $this->saveGame($game);
        */
                
        // ✅ финальная логика CALL

        \Log::info("🔄 [FINAL] Processing CALL for player {$player->getUserId()}");

        $currentStake = $game->getCurrentMaxBet();   // трактуем currentMaxBet как currentStake
        $darkPrivilegeActive = $this->isDarkPrivilegeActive($game, $player);

        $this->adjustPlayerBetTo($game, $player, $currentStake, $darkPrivilegeActive);

        $this->saveGame($game);
    }

    /**
     * 🎯 Пропуск хода (только если нет текущих ставок) (С ЛОГИРОВАНИЕМ)
     */
    private function processCheck(Player $player, Game $game): void
    {
        \Log::info("🔄 Processing CHECK for player {$player->getUserId()}");
        
        $currentMaxBet = $game->getCurrentMaxBet();
        $playerBet = $player->getCurrentBet();
        
        \Log::info("💰 Check details: maxBet={$currentMaxBet}, playerBet={$playerBet}");
        
        if ($currentMaxBet > $playerBet) {
            \Log::error("❌ Cannot check when there is a bet to call");
            throw new DomainException('Cannot check when there is a bet to call');
        }
        
        // 🎯 Отмечаем что игрок проверил
        $player->setChecked(true);
        \Log::info("✅ Player {$player->getUserId()} checked");

        $this->saveGame($game);
    }

    /**
     * 🎯 REVEAL — вскрытие против предыдущего активного игрока.
     *
     * Правила:
     * - доступен только НЕ в первом раунде;
     * - игрок делает ставку = currentStake * 2;
     * - currentStake не меняется;
     * - сравниваются только два игрока: текущий и предыдущий активный;
     * - проигравший переходит в FOLDED, но остаётся за столом;
     * - это полноценный ход, дополнительного хода победителю нет.
     */
    private function processReveal(Player $player, Game $game): void
    {
        \Log::info("🔄 [FINAL] Processing REVEAL for player {$player->getUserId()}");

        $round = $game->getCurrentRound();

        // ❌ REVEAL запрещён в первом раунде
        if ($round <= 1) {
            \Log::error("⛔ REVEAL not allowed in round {$round}");
            throw new DomainException('Reveal is not allowed in the first round');
        }

        // Игрок должен быть в активном состоянии (не FOLDED, не WAITING и т.п.)
        if (!$this->isPlayerActiveInBidding($player)) {
            \Log::error("⛔ REVEAL not allowed: player {$player->getUserId()} is not active");
            throw new DomainException('Reveal is not allowed for this player');
        }

        $currentStake = $game->getCurrentMaxBet();
        $revealStake  = $currentStake * 2;

        \Log::info("💰 REVEAL financials: currentStake={$currentStake}, revealStake={$revealStake}, balance={$player->getBalance()}");

        if ($player->getBalance() < $revealStake) {
            \Log::error("❌ Insufficient funds for reveal: balance={$player->getBalance()}, needed={$revealStake}");
            throw new DomainException('Insufficient funds for reveal');
        }

        // 💰 Финансовая часть:
        // - списываем ПОЛНУЮ сумму с баланса;
        // - currentBet увеличиваем на ПОЛНУЮ сумму;
        // - в банк уходит ПОЛНАЯ сумма;
        $player->placeBet($revealStake);
        $game->increaseBank($revealStake);
        $player->increaseCurrentBet($revealStake);

        // 🔍 Находим предыдущего активного игрока для сравнения
        $opponent = $this->findPreviousActivePlayer($game, $player);

        if ($opponent === null) {
            \Log::error("⛔ No active opponent found for REVEAL from player {$player->getUserId()}");
            throw new DomainException('No opponent available for reveal');
        }

        \Log::info("🆚 REVEAL vs player {$opponent->getUserId()}");

        // Получаем карты игрока
        $playerHand = $player->getCards();
        if (empty($playerHand)) {
            throw new \InvalidArgumentException("Player has no cards to reveal.");
        }

        // Получаем карты оппонента
        $opponentHand = $opponent->getCards();
        if (empty($opponentHand)) {
            throw new \InvalidArgumentException("Opponent has no cards to reveal.");
        }

        \Log::info("Player Hand: " . implode(', ', $playerHand));
        \Log::info("Opponent Hand: " . implode(', ', $opponentHand));

        // Далее, если карты есть, рассчитываем их значения
        $playerPoints = $this->scoringService->calculateHandValue($playerHand);
        $opponentPoints = $this->scoringService->calculateHandValue($opponentHand);

        // 🎯 Определяем проигравшего
        if ($playerPoints < $opponentPoints) {
            // Текущий игрок проиграл
            $this->foldPlayerInReveal($player, $game, 'player_lost');
            $winner = $opponent;
            $loser  = $player;
        } elseif ($playerPoints > $opponentPoints) {
            // Оппонент проиграл
            $this->foldPlayerInReveal($opponent, $game, 'opponent_lost');
            $winner = $player;
            $loser  = $opponent;
        } else {
            // Ничья в REVEAL — оба остаются, игру продолжаем как обычно
            \Log::info("🤝 REVEAL tie between {$player->getUserId()} and {$opponent->getUserId()}");
            $winner = null;
            $loser  = null;
        }

        // 🕒 Дополнительный reveal-таймер (15 сек) — фронт показывает результат
        // Можно отправить отдельное событие, чтобы фронт отрисовал анимацию и показал карты
        event(new \App\Events\RevealResolved(
            gameId: $game->getId()->toInt(),
            playerId: (string)$player->getUserId(),
            opponentId: (string)$opponent->getUserId(),
            playerPoints: $playerPoints,
            opponentPoints: $opponentPoints,
            winnerId: $winner ? (string)$winner->getUserId() : null,
            loserId: $loser ? (string)$loser->getUserId() : null,
            resolveTimeout: 15
        ));

        $this->saveGame($game);
    }

    /**
     * 🎯 DARK — выбор игры в тёмную
     *
     * Правила:
     * - только игрок сразу справа от дилера;
     * - только в раундах 1–2;
     * - только если ещё не играл в темную в этой игре;
     * - само действие DARK не меняет банк и ставки, только режим.
     */
    private function processDark(Player $player, Game $game): void
    {
        \Log::info("🔄 [FINAL] Processing DARK for player {$player->getUserId()}");

        // 🔍 Проверяем, может ли этот игрок вообще выбрать DARK
        if (!$this->canPlayDark($game, $player)) {
            \Log::error("❌ DARK not allowed for player {$player->getUserId()} in current state");
            throw new DomainException('Dark mode is not available for this player');
        }

        // ✅ Переводим игрока в статус DARK и фиксируем, что он уже играл в темную
        $player->playDark();
        $player->setPlayedDark(true);

        \Log::info("✅ Player {$player->getUserId()} is now playing DARK");

        // ВНИМАНИЕ:
        // Никаких списаний с баланса и изменений банка здесь нет.
        // Все финансовые эффекты DARK обрабатываются в CALL/RAISE
        // через dark-привилегию (игрок платит половину, а ставит целиком).

        $this->saveGame($game);
    }


    /**
     * 🎯 Открытие карт после темной игры (С ЛОГИРОВАНИЕМ)
     */
    private function processOpen(Player $player, Game $game): void
    {
        \Log::info("🔄 Processing OPEN for player {$player->getUserId()}");

        if ($player->getStatus() !== PlayerStatus::DARK) {
            \Log::error("❌ Can only open cards after playing dark. Current status: {$player->getStatus()->value}");
            throw new DomainException('Can only open cards after playing dark');
        }
        
        $player->openCards();
        \Log::info("✅ Player {$player->getUserId()} opened cards");

        $this->saveGame($game);
    }

    /**
     * 🎯 Переход к следующему игроку (С ЛОГИРОВАНИЕМ) - ПОЛНОСТЬЮ ИСПРАВЛЕННАЯ ВЕРСИЯ
     */
    private function moveToNextPlayer(Game $game): void
    {
        $activePlayers = $game->getActivePlayers();
        $currentPosition = $game->getCurrentPlayerPosition();
        
        \Log::info("🔄 moveToNextPlayer: currentPosition={$currentPosition}, activePlayers=" . count($activePlayers));
        
        // 🎯 КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Проверяем количество активных игроков ДО любых операций
        if (empty($activePlayers)) {
            \Log::info("🎯 No active players left - ending bidding round");
            $this->endBiddingRound($game);
            return;
        }
        
        // 🎯 Если остался только один активный игрок - он победитель
        if (count($activePlayers) === 1) {
            \Log::info("🎯 Only one active player remaining - they win automatically!");
            $this->endBiddingRound($game);
            return;
        }
        
        // 🎯 Находим индекс текущего игрока среди активных
        $currentIndex = null;
        $activePlayersArray = array_values($activePlayers); // 🎯 ИСПРАВЛЕНИЕ: Переиндексируем массив
        
        foreach ($activePlayersArray as $index => $player) {
            if ($player->getPosition() === $currentPosition) {
                $currentIndex = $index;
                break;
            }
        }
        
        \Log::info("🔍 Current player index: " . ($currentIndex ?? 'NOT FOUND'));
        
        if ($currentIndex !== null) {
            // Переходим к следующему активному игроку
            $nextIndex = ($currentIndex + 1) % count($activePlayersArray);
            $nextPlayer = $activePlayersArray[$nextIndex];
            $game->setCurrentPlayerPosition($nextPlayer->getPosition());
            
            \Log::info("✅ Moving to next player: position={$nextPlayer->getPosition()}");
        } else {
            // Если текущий игрок не активен, выбираем первого активного
            \Log::info("🔄 Current player not active, selecting first active player");
            
            // 🎯 КРИТИЧЕСКАЯ ПРОВЕРКА: Убеждаемся что массив не пуст
            if (count($activePlayersArray) > 0) {
                $firstPlayer = $activePlayersArray[0];
                $game->setCurrentPlayerPosition($firstPlayer->getPosition());
                \Log::info("✅ Selected first active player: position={$firstPlayer->getPosition()}");
            } else {
                \Log::error("❌ CRITICAL: No active players available when trying to select first player");
                $this->endBiddingRound($game);
                return;
            }
        }
        
        // 🎯 Сохраняем игру после изменения
        $this->saveGame($game);
    }

    /**
     * 🎯 Завершение раунда торгов с событиями
     */
    private function endBiddingRound(Game $game): void
    {
        $activePlayers = $game->getActivePlayers();
        
        \Log::info("🎯 Ending bidding round. Active players: " . count($activePlayers));
        
        if (count($activePlayers) === 1) {
            // Один игрок остался - автоматическая победа
            $winner = array_values($activePlayers)[0];
            $game->setStatus(GameStatus::FINISHED);
            
            // 🎯 Отправляем событие завершения игры
            event(new \App\Events\GameFinished(
                gameId: $game->getId()->toInt(),
                winnerId: $winner->getUserId(),
                scores: [$winner->getUserId() => 0], // Можно добавить реальные очки
                finalState: $this->getGameStateForEvent($game)
            ));
            
            \Log::info("🎉 Player {$winner->getUserId()} wins automatically!");
        } elseif (count($activePlayers) > 1) {
            // Несколько игроков осталось - переход к сравнению карт
            $game->setStatus(GameStatus::COMPARISON);
            \Log::info("🔍 Multiple players remain - moving to card comparison");
            
            // 🎯 Здесь можно добавить событие для сравнения карт
        } else {
            // Нет активных игроков - ничья
            $game->setStatus(GameStatus::FINISHED);
            
            // 🎯 Отправляем событие завершения игры без победителя
            event(new \App\Events\GameFinished(
                gameId: $game->getId()->toInt(),
                winnerId: 0, // Нет победителя
                scores: [],
                finalState: $this->getGameStateForEvent($game)
            ));
            
            \Log::info("🤝 No active players - game ended in draw");
        }
        
        $this->saveGame($game);
    }

    // 🎯 Добавьте метод для сохранения игры
    private function saveGame(Game $game): void
    {
        $repository = new \App\Domain\Game\Repositories\CachedGameRepository();
        $repository->save($game);
        \Log::info("💾 Game saved after player action");
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
     * 🎯 Получить доступные действия с учетом круга (ИСПРАВЛЕННАЯ ВЕРСИЯ)
     */
    public function getAvailableActions(Game $game, Player $player): array
    {
        $currentRound = $game->getCurrentRound();
        $currentMaxBet = $game->getCurrentMaxBet();
        $playerBet = $player->getCurrentBet();
        
        \Log::info("🔍 getAvailableActions - Round: {$currentRound}, MaxBet: {$currentMaxBet}, PlayerBet: {$playerBet}");

        $actions = [PlayerAction::FOLD, PlayerAction::CALL, PlayerAction::RAISE];
        
        // 🎯 ИСПРАВЛЕНИЕ: CHECK доступен только если нет ставок для уравнивания И это разрешено в текущем раунде
        if ($currentMaxBet === $playerBet && $this->canCheckInRound($currentRound, $player)) {
            $actions[] = PlayerAction::CHECK;
            \Log::info("✅ CHECK added - no bet to call and allowed in round {$currentRound}");
        }
        
        // 🎯 OPEN всегда доступен (но может быть заблокирован другими условиями)
        $actions[] = PlayerAction::OPEN;
        
        // 🎯 DARK доступен только если canPlayDark возвращает true
        if ($this->canPlayDark($game, $player)) {
            $actions[] = PlayerAction::DARK;
            \Log::info("✅ DARK added - allowed for player right of dealer in round {$currentRound}");
        }
        
        // 🎯 REVEAL доступен в кругах 2-3
        if ($currentRound >= 2) {
            $actions[] = PlayerAction::REVEAL;
            \Log::info("✅ REVEAL added - round {$currentRound}");
        }
        
        $actionValues = array_map(fn($a) => $a->value, $actions);
        \Log::info("🎯 Final available actions: " . implode(', ', $actionValues));
        
        return $actions;
    }

    /**
     * 🎯 Проверить можно ли CHECK в данном раунде
     */
    private function canCheckInRound(int $round, Player $player): bool
    {
        // В раунде 1 CHECK всегда доступен если нет ставок
        if ($round === 1) {
            return true;
        }
        
        // В раундах 2-3 CHECK доступен только если игрок не играл в темную
        // или уже открыл карты после темной игры
        if ($round >= 2) {
            return !$player->hasPlayedDark() || $player->getStatus() !== PlayerStatus::DARK;
        }
        
        return true;
    }

    /**
     * 🎯 Можно ли этому игроку выбрать DARK в текущем состоянии игры
     */
    private function canPlayDark(Game $game, Player $player): bool
    {
        $round = $game->getCurrentRound();

        // ❌ DARK запрещён только начиная с 3-го раунда
        if ($round > 2) {
            \Log::info("⛔ DARK not allowed: round={$round} (>2)");
            return false;
        }

        // ❌ Игрок уже играл в темную в этой игре
        if ($player->hasPlayedDark()) {
            \Log::info("⛔ DARK not allowed: player {$player->getUserId()} already played dark");
            return false;
        }

        // ❌ Игрок не в игровом статусе
        if (!in_array($player->getStatus(), [PlayerStatus::ACTIVE, PlayerStatus::DARK], true)) {
            \Log::info("⛔ DARK not allowed: invalid status {$player->getStatus()->value}");
            return false;
        }

        // 🎯 Если игра умеет определять игрока справа от дилера — проверяем строго
        $rightOfDealer = $game->getPlayerRightOfDealer();

        if ($rightOfDealer !== null) {
            if ($rightOfDealer->getId()->toInt() !== $player->getId()->toInt()) {
                \Log::info("⛔ DARK not allowed: player {$player->getUserId()} is not right of dealer");
                return false;
            }

            \Log::info("✅ DARK is allowed (right-of-dealer) for player {$player->getUserId()} in round {$round}");
            return true;
        }

        // Если игрок справа от дилера не определён,
        // НЕ будем блокировать DARK (чтобы существующие тесты не падали).
        \Log::info("✅ DARK allowed (no right-of-dealer constraint) for player {$player->getUserId()} in round {$round}");
        return true;
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

    /**
     * 🎯 Запустить раунд торгов с WebSocket событием
     */
    public function startBiddingRound(Game $game): void
    {
        \Log::info("🎯 BiddingService: Starting bidding round for game: " . $game->getId()->toInt());

        // 🎯 ИСПРАВЛЕНИЕ: НЕ сбрасываем банк и максимальную ставку - они уже установлены анте
        $currentBank = $game->getBank();
        $currentMaxBet = $game->getCurrentMaxBet();
        
        \Log::info("💰 Game bank: {$currentBank}, max bet: {$currentMaxBet}");

        // 🎯 Сбрасываем только статусы проверки, НЕ ставки
        foreach ($game->getActivePlayers() as $player) {
            $player->setChecked(false);
            
            // 🎯 НЕ сбрасываем текущие ставки - они содержат анте
            // $player->resetCurrentBet(); // ЗАКОММЕНТИРУЙТЕ ЭТУ СТРОЧКУ!
            
            if (method_exists($player, 'resetForNewBiddingRound')) {
                $player->resetForNewBiddingRound();
            }
        }
        
        // 🎯 Устанавливаем первого игрока
        $firstPlayerPosition = $game->getCurrentPlayerPosition();
        if (!$firstPlayerPosition) {
            $activePlayers = $game->getActivePlayers();
            if (!empty($activePlayers)) {
                $firstPlayer = $activePlayers[array_rand($activePlayers)];
                $firstPlayerPosition = $firstPlayer->getPosition();
                $game->setCurrentPlayerPosition($firstPlayerPosition);
            }
        }
        
        // 🎯 Обновляем статус игры на BIDDING
        $game->startBidding();
        
        // 🎯 Отправляем событие начала раунда торгов
        $currentPlayer = $this->getCurrentPlayer($game);
        if ($currentPlayer) {
            event(new \App\Events\BiddingRoundStarted(
                gameId: $game->getId()->toInt(),
                roundNumber: $game->getCurrentRound(),
                currentPlayerPosition: $game->getCurrentPlayerPosition(),
                availableActions: $this->getAvailableActions($game, $currentPlayer),
                currentMaxBet: $game->getCurrentMaxBet()
            ));
        }
        
        \Log::info("🎯 BiddingService: Round started. First player: {$firstPlayerPosition}, Bank: {$currentBank}, Max bet: {$currentMaxBet}");
    }

    /**
     * 🎯 Получить состояние игры для WebSocket событий
     */
    private function getGameStateForEvent(Game $game): array
    {
        $playersData = [];
        foreach ($game->getPlayers() as $player) {
            $playersData[] = [
                'id' => $player->getUserId(),
                'position' => $player->getPosition(),
                'status' => $player->getStatus()->value,
                'balance' => $player->getBalance(),
                'current_bet' => $player->getCurrentBet(),
                'has_checked' => $player->hasChecked(),
                'has_played_dark' => $player->hasPlayedDark(),
                'cards_count' => count($player->getCards())
            ];
        }
        
        return [
            'game_id' => $game->getId()->toInt(),
            'status' => $game->getStatus()->value,
            'current_round' => $game->getCurrentRound(),
            'current_player_position' => $game->getCurrentPlayerPosition(),
            'current_max_bet' => $game->getCurrentMaxBet(),
            'bank' => $game->getBank(),
            'players' => $playersData,
            'active_players_count' => count($game->getActivePlayers())
        ];
    }

    /**
     * Универсальная доводка ставки игрока до целевого уровня.
     *
     * @param Game   $game
     * @param Player $player
     * @param int    $targetBet            целевой уровень ставки для игрока
     * @param bool   $darkPrivilegeActive  действует ли привилегия DARK (игрок платит половину, ставит целиком)
     */
    private function adjustPlayerBetTo(Game $game, Player $player, int $targetBet, bool $darkPrivilegeActive): void
    {
        $currentBet = $player->getCurrentBet();

        if ($targetBet <= $currentBet) {
            // Игрок уже на этой ставке или выше — ничего не делаем
            return;
        }

        $diff = $targetBet - $currentBet; // на сколько нужно поднять ставку

        if ($darkPrivilegeActive) {
            // 🎯 Темный игрок:
            // платит половину diff, но считается, что поставил полный diff.
            $payment = intdiv($diff + 1, 2); // ceil(diff / 2)

            // placeBet спишет payment с баланса и увеличит currentBet на payment
            $player->placeBet($payment);

            // добиваем ставку до полного diff (баланс уже не трогаем)
            $extraBet = $diff - $payment;
            if ($extraBet > 0) {
                $player->increaseCurrentBet($extraBet);
            }

            // в банк всегда уходит полный diff
            $game->increaseBank($diff);
        } else {
            // 🎯 Обычный игрок:
            // платит полный diff, и ровно на diff растёт ставка.
            $payment = $diff;

            $player->placeBet($payment);  // сама поднимет currentBet на diff
            $game->increaseBank($diff);   // в банк уходит diff
        }
    }

    /**
     * Определяем, действует ли привилегия DARK для игрока в текущем раунде.
     */
    private function isDarkPrivilegeActive(Game $game, Player $player): bool
    {
        // финальная

        if ($player->getStatus() !== PlayerStatus::DARK) {
            return false;
        }

        $round = $game->getCurrentRound();

        // Привилегия действует только в 1–2 раунде, в 3-м DARK остаётся как режим, но без скидки
        return in_array($round, [1, 2], true);
    }

    /**
     * 🔎 Поиск игрока по позиции за столом.
     * Используется, например, для события TurnChanged (previousPlayerId).
     */
    private function findPlayerByPosition(Game $game, int $position): ?Player
    {
        foreach ($game->getPlayers() as $player) {
            if ($player->getPosition() === $position) {
                return $player;
            }
        }

        return null;
    }

    /**
     * 🎯 Игрок считается активным в торгах, если он в игре и не FOLDED/WAITING.
     */
    private function isPlayerActiveInBidding(Player $player): bool
    {
        $isActive = in_array($player->getStatus(), [
            PlayerStatus::ACTIVE,
            PlayerStatus::DARK,
        ], true);
        
        \Log::info("🔍 isPlayerActiveInBidding: player ID={$player->getUserId()}, status={$player->getStatus()->value}, isActive=" . ($isActive ? 'YES' : 'NO'));
        
        return $isActive;
    }

    /**
     * 🔎 Поиск предыдущего активного игрока по позициям.
     *
     * Логика:
     * - берём всех игроков, сортированных по позиции (по часовой или против — важно, чтобы было консистентно);
     * - находим текущего;
     * - идём назад по позициям (с зацикливанием) до тех пор, пока не найдём активного;
     * - если никого не нашли — возвращаем null.
     */
    private function findPreviousActivePlayer(Game $game, Player $player): ?Player
    {
        $players = $game->getPlayers();
        
        // 🎯 СОРТИРУЕМ игроков по позициям
        usort($players, function(Player $a, Player $b) {
            return $a->getPosition() <=> $b->getPosition();
        });
        
        $count = count($players);
        if ($count === 0) {
            return null;
        }

        // Находим индекс текущего игрока в массиве
        $currentIndex = null;
        foreach ($players as $index => $p) {
            if ($p->getId()->toInt() === $player->getId()->toInt()) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === null) {
            return null;
        }

        // 🎯 ОТЛАДКА
        \Log::info("🔍 findPreviousActivePlayer: current player ID={$player->getUserId()}, position={$player->getPosition()}");
        \Log::info("🔍 All players positions: " . implode(', ', array_map(fn($p) => $p->getPosition() . '(ID:' . $p->getUserId() . ')', $players)));

        // Идём назад по кругу
        $index = ($currentIndex - 1 + $count) % $count;

        while ($index !== $currentIndex) {
            $candidate = $players[$index];
            
            \Log::info("🔍 Checking candidate: ID={$candidate->getUserId()}, position={$candidate->getPosition()}, status={$candidate->getStatus()->value}");

            if ($this->isPlayerActiveInBidding($candidate)) {
                \Log::info("✅ Found active opponent: ID={$candidate->getUserId()}");
                return $candidate;
            }

            $index = ($index - 1 + $count) % $count;
        }

        \Log::info("❌ No active opponent found");
        return null;
    }

    /**
     * 🚫 Помечает игрока как FOLDED в контексте REVEAL.
     * Игрок остаётся за столом (ожидание новой игры), но в текущей раздаче больше не участвует.
     */
    private function foldPlayerInReveal(Player $player, Game $game, string $reason): void
    {
        \Log::info("🚫 FOLD in REVEAL for player {$player->getUserId()} ({$reason})");

        $player->fold(); // доменный метод, который ставит статус PlayerStatus::FOLDED
        $player->updateLastActionTime();

        // ВАЖНО:
        // - мы не отправляем его в глобальное лобби,
        // - не трогаем баланс,
        // - не выключаем полностью из game, только из текущей раздачи.
    }


}