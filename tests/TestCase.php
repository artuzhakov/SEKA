<?php
// tests/TestCase.php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    protected function createCard(string $suit, string $rank): \App\Domain\Game\Entities\Card
    {
        return new \App\Domain\Game\Entities\Card(
            \App\Domain\Game\Enums\CardSuit::from($suit),
            \App\Domain\Game\Enums\CardRank::from($rank)
        );
    }
    
    protected function createPlayer(int $id, int $position = 1, int $balance = 1000): \App\Domain\Game\Entities\Player
    {
        return new \App\Domain\Game\Entities\Player(
            \App\Domain\Game\ValueObjects\PlayerId::fromInt($id),
            $id,
            $position,
            \App\Domain\Game\Enums\PlayerStatus::WAITING,
            $balance
        );
    }
    
    protected function createGame(int $id = 1, string $status = 'waiting'): \App\Domain\Game\Entities\Game
    {
        return new \App\Domain\Game\Entities\Game(
            \App\Domain\Game\ValueObjects\GameId::fromInt($id),
            \App\Domain\Game\Enums\GameStatus::from($status),
            1,
            \App\Domain\Game\Enums\GameMode::OPEN
        );
    }
}