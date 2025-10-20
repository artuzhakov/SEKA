<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardsDistributed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $connection = 'sync';
    public $queue = 'sync';

    public function __construct(
        public int $gameId,
        public array $playerCards, // [player_id => [cards]]
        public array $communityCards,
        public string $round
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("game.{$this->gameId}")];
    }

    public function broadcastAs(): string
    {
        return 'CardsDistributed';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
            'player_cards' => $this->playerCards,
            'community_cards' => $this->communityCards,
            'round' => $this->round,
            'timestamp' => now()->toISOString()
        ];
    }
}