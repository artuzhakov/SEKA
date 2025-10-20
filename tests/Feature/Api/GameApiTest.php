<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GameApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем и аутентифицируем пользователя
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_can_start_new_game_via_api()
    {
        $response = $this->postJson('/api/games/start', [
            'room_id' => 1, // Просто число, без проверки exists
            'players' => [1, 2, 3] // Просто числа, без проверки exists
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'status' => 'waiting'
                ]);
    }

    /** @test */
    public function it_validates_start_game_request()
    {
        $response = $this->postJson('/api/games/start', [
            'room_id' => 'invalid',
            'players' => [1] // недостаточно игроков
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['room_id', 'players']);
    }
}