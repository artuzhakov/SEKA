<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $connection = 'sync';
    public $queue = 'sync';

    public function __construct(
        public int $gameId,
        public array $player,
        public array $playersList,
        public int $currentPlayersCount
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("game.{$this->gameId}")];
    }

    public function broadcastAs(): string
    {
        return 'PlayerJoined';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
            'player' => $this->player,
            'players_list' => $this->playersList,
            'current_players_count' => $this->currentPlayersCount,
            'timestamp' => now()->toISOString()
        ];
    }
}