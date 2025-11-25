<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $gameId,
        public int $playerId,
        public array $playersList,
        public int $currentPlayersCount,
        public string $gameStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("game.{$this->gameId}"),
            new Channel('lobby') // 🎯 Также обновляем лобби
        ];
    }

    public function broadcastAs(): string
    {
        return 'player.left';
    }
}