<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardPlayed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $connection = 'sync';
    public $queue = 'sync';

    public function __construct(
        public int $gameId,
        public string $playerId,
        public array $card,
        public array $newGameState,
        public string $nextPlayerId
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("game.{$this->gameId}")];
    }

    public function broadcastAs(): string
    {
        return 'CardPlayed';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
            'player_id' => $this->playerId,
            'card' => $this->card,
            'game_state' => $this->newGameState,
            'next_player_id' => $this->nextPlayerId,
            'timestamp' => now()->toISOString()
        ];
    }
}