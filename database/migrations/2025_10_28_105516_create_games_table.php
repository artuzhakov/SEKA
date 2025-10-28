<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_games_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->enum('status', ['waiting', 'active', 'bidding', 'finished', 'cancelled'])->default('waiting');
            $table->integer('current_round')->default(1);
            $table->integer('max_players')->default(6);
            $table->integer('min_bet');
            $table->integer('max_bet');
            $table->integer('buy_in');
            $table->integer('pot')->default(0);
            $table->json('deck')->nullable(); // Колода карт
            $table->integer('current_player_position')->nullable();
            $table->timestamp('last_action_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};