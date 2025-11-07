<?php
// app/Events/BiddingRoundStarted.php
declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BiddingRoundStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $connection = 'sync';
    public $queue = 'sync';

    public function __construct(
        public int $gameId,
        public int $roundNumber, // 1, 2, 3
        public int $currentPlayerPosition,
        public array $availableActions,
        public int $currentMaxBet
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("game.{$this->gameId}")];
    }

    public function broadcastAs(): string
    {
        return 'bidding.round.started';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
            'round_number' => $this->roundNumber,
            'current_player_position' => $this->currentPlayerPosition,
            'available_actions' => $this->availableActions,
            'current_max_bet' => $this->currentMaxBet,
            'timestamp' => now()->toISOString()
        ];
    }
}