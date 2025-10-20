<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->string('user_id'); // или relation к User модели
            $table->string('username');
            $table->integer('chips')->default(1000);
            $table->integer('current_bet')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_turn')->default(false);
            $table->boolean('is_dealer')->default(false);
            $table->boolean('is_small_blind')->default(false);
            $table->boolean('is_big_blind')->default(false);
            $table->json('hand')->nullable(); // карты игрока
            $table->string('last_action')->nullable(); // fold, check, call, raise
            $table->integer('position')->default(0); // позиция за столом
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};