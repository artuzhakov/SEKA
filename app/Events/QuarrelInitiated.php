<?php
// app/Events/QuarrelInitiated.php
declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuarrelInitiated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $connection = 'sync';
    public $queue = 'sync';

    public function __construct(
        public int $gameId,
        public array $winningPlayers, // Игроки с одинаковыми очками
        public int $requiredBet, // Ставка для участия в сваре
        public int $timeoutSeconds = 30 // Время на голосование
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("game.{$this->gameId}")];
    }

    public function broadcastAs(): string
    {
        return 'quarrel.initiated';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
            'winning_players' => $this->winningPlayers,
            'required_bet' => $this->requiredBet,
            'timeout_seconds' => $this->timeoutSeconds,
            'timestamp' => now()->toISOString()
        ];
    }
}