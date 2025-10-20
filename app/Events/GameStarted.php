<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $connection = 'sync';
    public $queue = 'sync';

    public function __construct(
        public int $gameId,
        public array $players,
        public string $firstPlayerId, // 🎯 УБИРАЕМ status, ДОБАВЛЯЕМ firstPlayerId
        public array $initialState
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("game.{$this->gameId}")];
    }

    public function broadcastAs(): string
    {
        return 'GameStarted';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
            'players' => $this->players,
            'first_player_id' => $this->firstPlayerId,
            'state' => $this->initialState,
            'timestamp' => now()->toISOString()
        ];
    }
}