<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TurnChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $connection = 'sync';
    public $queue = 'sync';

    public function __construct(
        public int $gameId,
        public string $previousPlayerId,
        public string $currentPlayerId,
        public int $turnTimeLeft
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("game.{$this->gameId}")];
    }

    public function broadcastAs(): string
    {
        return 'TurnChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
            'previous_player_id' => $this->previousPlayerId,
            'current_player_id' => $this->currentPlayerId,
            'turn_time_left' => $this->turnTimeLeft,
            'timestamp' => now()->toISOString()
        ];
    }
}