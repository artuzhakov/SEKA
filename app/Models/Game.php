<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'max_players',
        'small_blind',
        'big_blind',
        'current_pot',
        'current_round',
        'current_player_id',
        'current_bet',
        'community_cards',
        'winner_id',
        'game_state',
        'is_active'
    ];

    protected $casts = [
        'community_cards' => 'array',
        'game_state' => 'array',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function activePlayers()
    {
        return $this->players()->where('is_active', true);
    }

    public function getPlayerById($playerId)
    {
        return $this->players()->where('user_id', $playerId)->first();
    }

    public function startGame()
    {
        $this->update([
            'current_round' => 'preflop',
            'is_active' => true
        ]);

        // Триггерим событие
        broadcast(new \App\Events\GameStarted(
            gameId: $this->id,
            players: $this->players->toArray(),
            firstPlayerId: $this->getFirstPlayer()->user_id,
            initialState: $this->getGameState()
        ));
    }

    public function getGameState()
    {
        return [
            'pot' => $this->current_pot,
            'current_bet' => $this->current_bet,
            'community_cards' => $this->community_cards ?? [],
            'round' => $this->current_round,
            'active_players_count' => $this->activePlayers()->count()
        ];
    }

    public function getFirstPlayer()
    {
        return $this->players()->orderBy('position')->first();
    }
}