<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $connection = 'sync';
    public $queue = 'sync';

    public function __construct(
        public int $gameId,
        public int $playerId,
        public string $playerStatus,
        public int $readyPlayersCount,
        public int $timeUntilStart
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("game.{$this->gameId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'player.ready';
    }
}