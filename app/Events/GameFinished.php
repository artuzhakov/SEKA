<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameFinished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $connection = 'sync';
    public $queue = 'sync';

    public function __construct(
        public int $gameId,
        public int $winnerId,
        public array $scores,
        public array $finalState
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("game.{$this->gameId}")];
    }

    public function broadcastAs(): string
    {
        return 'GameFinished';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
            'winner_id' => $this->winnerId,
            'scores' => $this->scores,
            'final_state' => $this->finalState,
            'timestamp' => now()->toISOString()
        ];
    }
}