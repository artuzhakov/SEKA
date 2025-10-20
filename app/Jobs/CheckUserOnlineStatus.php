<?php

namespace App\Jobs;

use App\Services\GameService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckUserOnlineStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $gameId;

    public function __construct($userId, $gameId)
    {
        $this->userId = $userId;
        $this->gameId = $gameId;

        $this->onQueue('delayed_logout');
    }

    public function handle()
    {
        $gameService = new GameService();
        $gameService->checkAndLogoutIfOnline($this->userId, $this->gameId);
    }
}
