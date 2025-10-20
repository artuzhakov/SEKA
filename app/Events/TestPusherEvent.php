<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestPusherEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $connection = 'sync';
    public $queue = 'sync';

    public $gameId;
    public $message;

    // Делаем синхронным (без очереди)
    public $connection = 'sync';
    public $queue = 'sync';

    public function __construct(int $gameId, string $message)
    {
        $this->gameId = $gameId;
        $this->message = $message;
        
        \Log::info("TestPusherEvent created", [
            'gameId' => $gameId,
            'message' => $message
        ]);
    }

    public function broadcastOn()
    {
        \Log::info("Broadcasting to channel: game.{$this->gameId}");
        return new Channel("game.{$this->gameId}");
    }

    // public function broadcastAs()
    // {
    //     return 'test.event';
    // }

    public function broadcastWith()
    {
        return [
            'game_id' => $this->gameId,
            'message' => $this->message,
            'timestamp' => now()->toISOString(),
            'server_time' => time()
        ];
    }
}