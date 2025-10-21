<?php
// tests/Unit/Domain/Game/Entities/PlayerTest.php

namespace Tests\Unit\Domain\Game\Entities;

use Tests\TestCase;
use App\Domain\Game\Entities\Player;
use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\Enums\PlayerStatus;

class PlayerTest extends TestCase
{
    public function test_player_round_state()
    {
        $player = new Player(PlayerId::fromInt(1), 1, 1, PlayerStatus::ACTIVE, 1000);

        // Проверяем начальное состояние
        $this->assertFalse($player->hasChecked());
        $this->assertFalse($player->hasPlayedDark());

        // Устанавливаем состояния
        $player->setChecked(true);
        $player->setPlayedDark(true);

        $this->assertTrue($player->hasChecked());
        $this->assertTrue($player->hasPlayedDark());

        // Сброс для нового круга
        $player->resetForNewBiddingRound();
        $this->assertFalse($player->hasChecked());
        // playedDark НЕ сбрасывается - это на всю игру
        $this->assertTrue($player->hasPlayedDark());
    }
}