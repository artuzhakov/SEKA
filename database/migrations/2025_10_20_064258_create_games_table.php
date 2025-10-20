<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('max_players')->default(6);
            $table->integer('small_blind')->default(5);
            $table->integer('big_blind')->default(10);
            $table->integer('current_pot')->default(0);
            $table->string('current_round')->default('waiting'); // waiting, preflop, flop, turn, river, finished
            $table->string('current_player_id')->nullable();
            $table->integer('current_bet')->default(0);
            $table->json('community_cards')->nullable();
            $table->string('winner_id')->nullable();
            $table->json('game_state')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};