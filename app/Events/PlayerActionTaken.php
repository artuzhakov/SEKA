<?php
// app/Events/PlayerActionTaken.php - ОБНОВЛЕННАЯ ВЕРСИЯ

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerActionTaken implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $connection = 'sync';
    public $queue = 'sync';

    public function __construct(
        public int $gameId,
        public int $playerId,
        public string $action, // check, raise, dark, open, fold, call, reveal
        public ?int $betAmount = null,
        public ?int $newPlayerPosition = null,
        public ?int $bank = null,
        public ?array $gameState = null, // 🆕 Полное состояние игры
        public ?array $availableActions = null // 🆕 Доступные действия для текущего игрока
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("game.{$this->gameId}")];
    }

    public function broadcastAs(): string
    {
        return 'player.action.taken';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
            'player_id' => $this->playerId,
            'action' => $this->action,
            'bet_amount' => $this->betAmount,
            'new_player_position' => $this->newPlayerPosition,
            'bank' => $this->bank,
            'game_state' => $this->gameState, // 🆕
            'available_actions' => $this->availableActions, // 🆕
            'timestamp' => now()->toISOString()
        ];
    }
}