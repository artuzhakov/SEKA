<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'user_id',
        'username',
        'chips',
        'current_bet',
        'is_active',
        'is_turn',
        'is_dealer',
        'is_small_blind',
        'is_big_blind',
        'hand',
        'last_action',
        'position'
    ];

    protected $casts = [
        'hand' => 'array',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function playCard($card, $action)
    {
        // Логика игры карты
        $this->update(['last_action' => $action]);

        // Триггерим событие
        broadcast(new \App\Events\CardPlayed(
            gameId: $this->game_id,
            playerId: $this->user_id,
            card: $card,
            newGameState: $this->game->getGameState(),
            nextPlayerId: $this->game->getNextPlayer()->user_id
        ));
    }

    public function fold()
    {
        $this->update([
            'is_active' => false,
            'last_action' => 'fold'
        ]);
    }
}