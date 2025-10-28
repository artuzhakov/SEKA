<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_game_players_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('position'); // Позиция за столом
            $table->json('cards')->nullable(); // Карты игрока
            $table->boolean('is_dark')->default(false); // Играет в темную
            $table->boolean('has_opened')->default(false); // Открыл карты после темной
            $table->boolean('folded')->default(false); // Сбросил карты
            $table->integer('balance'); // Текущий баланс в игре
            $table->integer('current_bet')->default(0);
            $table->boolean('is_ready')->default(false);
            $table->integer('total_bet')->default(0);
            $table->timestamps();

            $table->unique(['game_id', 'user_id']);
            $table->unique(['game_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_players');
    }
};