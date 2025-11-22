<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: результат действия REVEAL (вскрытие двух игроков).
 *
 * Отправляется сразу после сравнения комбинаций:
 * - игрок, который вызвал REVEAL;
 * - предыдущий активный игрок, которого вскрыли.
 *
 * Фронт должен:
 *  1) показать карты обоих игроков;
 *  2) показать очки и победителя;
 *  3) подождать resolveTimeout секунд;
 *  4) продолжить игровой процесс.
 */
class RevealResolved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $gameId;
    public string $playerId;
    public string $opponentId;

    public int $playerPoints;
    public int $opponentPoints;

    public ?string $winnerId;
    public ?string $loserId;

    /**
     * Таймаут в секундах, в течение которого фронт показывает
     * результат REVEAL, прежде чем игра продолжится.
     */
    public int $resolveTimeout;

    /**
     * По аналогии с TurnChanged — подключение и очередь "sync".
     */
    public string $connection = 'sync';
    public string $queue = 'sync';

    public function __construct(
        int $gameId,
        string $playerId,
        string $opponentId,
        int $playerPoints,
        int $opponentPoints,
        ?string $winnerId,
        ?string $loserId,
        int $resolveTimeout = 15
    ) {
        $this->gameId         = $gameId;
        $this->playerId       = $playerId;
        $this->opponentId     = $opponentId;

        $this->playerPoints   = $playerPoints;
        $this->opponentPoints = $opponentPoints;

        $this->winnerId       = $winnerId;
        $this->loserId        = $loserId;

        $this->resolveTimeout = $resolveTimeout;
    }

    /**
     * Куда отправлять событие.
     *
     * Здесь используем тот же канал, что и TurnChanged:
     *   Channel('game.{id}')
     *
     * Если TurnChanged использует другой тип канала (PrivateChannel / PresenceChannel),
     * поменяй здесь аналогично.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('game.' . $this->gameId);
    }

    /**
     * Имя события в WebSocket-потоке.
     */
    public function broadcastAs(): string
    {
        return 'reveal.resolved';
    }
}
