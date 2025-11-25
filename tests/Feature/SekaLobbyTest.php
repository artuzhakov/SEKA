<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Game\Repositories\CachedGameRepository;
use App\Domain\Game\Entities\Game;
use App\Domain\Game\ValueObjects\GameId;
use App\Domain\Game\ValueObjects\PlayerId;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\PlayerStatus;
use App\Domain\Game\Enums\GameMode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class SekaLobbyTest extends TestCase
{
    use RefreshDatabase;

    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CachedGameRepository();
        $this->setupLobbyEnvironment();
    }

    /**
     * 🎯 ГРУППА 1: ТЕСТЫ ЛОББИ
     */

    /** @test */
    public function test_initial_lobby_creation_creates_16_tables()
    {
        // 🎯 Первый запрос к лобби
        $response = $this->getJson('/api/seka/lobby');
        
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        
        $games = $response->json('games');
        
        // 🎯 Проверяем что создано 16 столов
        $this->assertCount(16, $games, 'Должно быть создано 16 столов');
        
        // 🎯 Проверяем распределение по типам
        $tableTypes = array_count_values(array_column($games, 'table_type'));
        $this->assertEquals(4, $tableTypes['novice'] ?? 0, 'Должно быть 4 стола novice');
        $this->assertEquals(4, $tableTypes['amateur'] ?? 0, 'Должно быть 4 стола amateur');
        $this->assertEquals(4, $tableTypes['pro'] ?? 0, 'Должно быть 4 стола pro');
        $this->assertEquals(4, $tableTypes['master'] ?? 0, 'Должно быть 4 стола master');
        
        // 🎯 Проверяем базовые ставки
        foreach ($games as $game) {
            $expectedBet = match($game['table_type']) {
                'novice' => 5,
                'amateur' => 10,
                'pro' => 25,
                'master' => 50,
                default => 5
            };
            $this->assertEquals($expectedBet, $game['base_bet'], "Стол {$game['id']} имеет неправильную ставку");
            $this->assertEquals('waiting', $game['status'], "Стол {$game['id']} должен быть в статусе waiting");
            $this->assertEquals(0, $game['players_count'], "Стол {$game['id']} должен быть пустым");
        }
    }

    /** @test */
    public function test_lobby_idempotency_does_not_create_new_tables()
    {
        // 🎯 Первый запрос
        $response1 = $this->getJson('/api/seka/lobby');
        $games1 = $response1->json('games');
        $gameIds1 = array_column($games1, 'id');
        
        // 🎯 Второй запрос
        $response2 = $this->getJson('/api/seka/lobby');
        $games2 = $response2->json('games');
        $gameIds2 = array_column($games2, 'id');
        
        // 🎯 Проверяем что те же самые ID
        $this->assertEquals($gameIds1, $gameIds2, 'ID столов должны совпадать между запросами');
        $this->assertCount(16, $games2, 'Количество столов должно остаться 16');
        
        // 🎯 Проверяем что players_count сохраняется
        foreach ($games2 as $game) {
            $this->assertEquals(0, $game['players_count'], "Players count должен сохраняться");
        }
    }

    /** @test */
    public function test_table_type_determination_by_base_bet()
    {
        $testCases = [
            [5, 'novice'],
            [10, 'amateur'],
            [25, 'pro'],
            [50, 'master'],
            [100, 'novice'],
        ];

        $controller = $this->createGameController();

        foreach ($testCases as [$baseBet, $expectedType]) {
            \Log::info("🔍 TEST CASE START", [
                'baseBet' => $baseBet,
                'expectedType' => $expectedType
            ]);

            $game = new Game(
                GameId::fromInt(999),
                GameStatus::WAITING,
                999,
                GameMode::OPEN,
                $baseBet
            );

            // 🎯 ДИАГНОСТИКА: какая базовая ставка у созданной игры?
            \Log::info("🔍 GAME CREATION DEBUG", [
                'input_base_bet' => $baseBet,
                'game_base_bet' => $game->getBaseBet(),
                'game_base_bet_type' => gettype($game->getBaseBet()),
                'game_base_bet_equals_input' => $game->getBaseBet() == $baseBet ? 'YES' : 'NO'
            ]);

            $actualType = $this->invokePrivateMethod($controller, 'determineTableTypeByGame', [$game]);
            
            \Log::info("🔍 TEST CASE RESULT", [
                'baseBet' => $baseBet,
                'expected' => $expectedType,
                'actual' => $actualType,
                'match' => $expectedType === $actualType ? 'YES' : 'NO'
            ]);
            
            $this->assertEquals($expectedType, $actualType, 
                "Failed for baseBet: {$baseBet}. Expected: {$expectedType}, Got: {$actualType}");
        }
    }

    /** @test */
    public function test_lobby_cleanup_removes_excess_tables()
    {
        // 🎯 Создаем лишние столы
        $excessGameIds = [];
        for ($i = 1; $i <= 10; $i++) {
            $game = new Game(
                GameId::fromInt($i),
                GameStatus::WAITING,
                $i,
                GameMode::OPEN,
                5 // Все столы novice
            );
            $this->repository->save($game);
            $excessGameIds[] = $i;
        }

        $this->repository->saveLobbyGameIds($excessGameIds);

        // 🎯 Запускаем очистку
        $response = $this->postJson('/api/seka/lobby/cleanup');
        
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // 🎯 Проверяем что осталось только 4 стола novice
        $lobbyResponse = $this->getJson('/api/seka/lobby');
        $games = $lobbyResponse->json('games');
        
        $noviceTables = array_filter($games, fn($game) => $game['table_type'] === 'novice');
        $this->assertCount(4, $noviceTables, 'Должно остаться 4 стола типа novice после очистки');
    }

    /**
     * 🎯 ГРУППА 2: ТЕСТЫ ПРИСОЕДИНЕНИЯ ИГРОКОВ
     */

    /** @test */
    public function test_player_join_with_real_name_not_player_id()
    {
        // 🎯 Создаем тестового пользователя
        $user = User::factory()->create([
            'name' => 'Тестовый Игрок'
        ]);

        $this->actingAs($user);

        // 🎯 Создаем тестовую игру
        $gameId = 888888;
        $game = new Game(
            GameId::fromInt($gameId),
            GameStatus::WAITING,
            $gameId,
            GameMode::OPEN,
            5
        );
        $this->repository->save($game);
        $this->repository->saveLobbyGameIds([$gameId]);

        // 🎯 Присоединяемся к игре
        $response = $this->postJson("/api/seka/games/{$gameId}/join", [
            'user_id' => $user->id
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $playerData = $response->json('player');
        $this->assertEquals('Тестовый Игрок', $playerData['name'], 'Имя игрока должно быть реальным, не Player_23');
        $this->assertEquals($user->id, $playerData['id'], 'ID игрока должен совпадать');
        
        // 🎯 Проверяем что players_count обновился
        $gameData = $response->json('game');
        $this->assertEquals(1, $gameData['players_count'], 'Players count должен увеличиться до 1');
    }

    /** @test */
    public function test_join_full_table_returns_error()
    {
        // 🎯 Создаем заполненный стол (6 игроков)
        $game = $this->createGameWithPlayers(6);
        
        $user = User::factory()->create();
        $this->actingAs($user);

        // 🎯 Пытаемся присоединиться к заполненному столу
        $response = $this->postJson("/api/seka/games/{$game->getId()->toInt()}/join", [
            'user_id' => $user->id
        ]);

        $response->assertStatus(400)
                 ->assertJson(['success' => false]);
        
        // 🎯 Проверяем что игрок не добавился
        $this->assertCount(6, $game->getPlayers(), 'Количество игроков не должно измениться');
    }

    /** @test */
    public function test_duplicate_player_join_redirects_to_existing_game()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $game = $this->createGameWithPlayers(1);
        $existingPlayer = $game->getPlayers()[0];
        
        // 🎯 Мокаем существующего игрока с тем же user_id
        $existingPlayer = $this->mockPlayerWithUserId($existingPlayer, $user->id);

        // 🎯 Пытаемся присоединиться повторно
        $response = $this->postJson("/api/seka/games/{$game->getId()->toInt()}/join", [
            'user_id' => $user->id
        ]);

        // 🎯 Должен произойти редирект в существующую игру
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    /**
     * 🎯 ГРУППА 3: ТЕСТЫ ВЫХОДА ИГРОКОВ
     */

    /** @test */
    public function test_player_can_leave_table_successfully()
    {
        // 🎯 Создаем стол с игроком
        $game = $this->createGameWithPlayers(1);
        $player = $game->getPlayers()[0];
        $gameId = $game->getId()->toInt();
        
        $initialPlayersCount = count($game->getPlayers());

        // 🎯 Игрок выходит
        $response = $this->postJson("/api/seka/games/{$gameId}/leave-to-lobby", [
            'user_id' => $player->getUserId()
        ]);
        
        // 🎯 Проверяем результат
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Successfully left the game'
                 ]);
        
        // 🎯 Проверяем что игрок удален
        $updatedGame = $this->repository->find(GameId::fromInt($gameId));
        $this->assertCount($initialPlayersCount - 1, $updatedGame->getPlayers(), 'Количество игроков должно уменьшиться на 1');
        $this->assertEquals('waiting', $updatedGame->getStatus()->value, 'Статус игры должен остаться waiting');
    }

    /** @test */
    public function test_last_player_leave_keeps_table_in_lobby()
    {
        // 🎯 Создаем стол с 1 игроком
        $game = $this->createGameWithPlayers(1);
        $player = $game->getPlayers()[0];
        $gameId = $game->getId()->toInt();
        
        $this->repository->saveLobbyGameIds([$gameId]);

        // 🎯 Игрок выходит
        $this->postJson("/api/seka/games/{$gameId}/leave-to-lobby", [
            'user_id' => $player->getUserId()
        ]);
        
        // 🎯 Проверяем что стол остался в лобби
        $lobbyResponse = $this->getJson('/api/seka/lobby');
        $games = $lobbyResponse->json('games');
        
        $gameIds = array_column($games, 'id');
        $this->assertContains($gameId, $gameIds, 'Стол должен остаться в лобби после выхода последнего игрока');
        
        // 🎯 Находим наш стол и проверяем players_count
        $updatedGame = collect($games)->firstWhere('id', $gameId);
        $this->assertEquals(0, $updatedGame['players_count'], 'Players count должен быть 0');
        $this->assertEquals('waiting', $updatedGame['status'], 'Статус должен остаться waiting');
    }

    /** @test */
    public function test_leave_nonexistent_player_returns_error()
    {
        $game = $this->createGameWithPlayers(1);
        $gameId = $game->getId()->toInt();
        
        // 🎯 Пытаемся выйти несуществующим игроком
        $response = $this->postJson("/api/seka/games/{$gameId}/leave-to-lobby", [
            'user_id' => 999999 // Несуществующий ID
        ]);

        $response->assertStatus(400)
                 ->assertJson(['success' => false]);
        
        // 🎯 Проверяем что состояние игры не изменилось
        $updatedGame = $this->repository->find(GameId::fromInt($gameId));
        $this->assertCount(1, $updatedGame->getPlayers(), 'Количество игроков не должно измениться');
    }

    /**
     * 🎯 ГРУППА 4: ТЕСТЫ ИГРОВОГО ПРОЦЕССА
     */

    /** @test */
    public function test_readiness_system_requires_minimum_2_players()
    {
        // 🎯 Создаем стол с 1 игроком
        $game = $this->createGameWithPlayers(1);
        $player = $game->getPlayers()[0];
        
        // 🎯 Игрок отмечает готовность
        $response = $this->postJson("/api/seka/games/{$game->getId()->toInt()}/ready", [
            'player_id' => $player->getUserId(),
            'game_id' => $game->getId()->toInt()
        ]);

        $response->assertStatus(200);
        
        $responseData = $response->json();
        $this->assertEquals('waiting', $responseData['game_status'] ?? '', 'Игра не должна начинаться с 1 игроком');
    }

    /** @test */
    public function test_custom_table_creation_with_correct_base_bet()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tableTypes = [
            'novice' => 5,
            'amateur' => 10,
            'pro' => 25,
            'master' => 50
        ];

        foreach ($tableTypes as $tableType => $expectedBaseBet) {
            $response = $this->postJson('/api/seka/games', [
                'user_id' => $user->id,
                'table_type' => $tableType,
                'player_name' => $user->name
            ]);

            $response->assertStatus(200)
                     ->assertJson(['success' => true]);

            $gameData = $response->json('game');
            $this->assertEquals($expectedBaseBet, $gameData['base_bet'], "Стол типа {$tableType} должен иметь ставку {$expectedBaseBet}");
            $this->assertEquals(1, $gameData['players_count'], 'Создатель должен быть добавлен как игрок');
        }
    }

    /**
     * 🎯 ГРУППА 5: ТЕХНИЧЕСКИЕ ТЕСТЫ
     */

    /** @test */
    public function test_game_id_generation_produces_unique_ids()
    {
        $controller = $this->createGameController();
        
        $generatedIds = [];
        for ($i = 0; $i < 100; $i++) {
            $gameId = $this->invokePrivateMethod($controller, 'generateGameId', []);
            
            // 🎯 Проверяем что ID уникальный
            $this->assertNotContains($gameId, $generatedIds, "ID {$gameId} должен быть уникальным");
            $generatedIds[] = $gameId;
            
            // 🎯 Проверяем что ID в правильном диапазоне
            $this->assertGreaterThanOrEqual(100000, $gameId, "ID {$gameId} должен быть ≥ 100000");
            $this->assertLessThanOrEqual(999999, $gameId, "ID {$gameId} должен быть ≤ 999999");
        }
    }

    /** @test */
    public function test_cache_persistence_works_correctly()
    {
        $gameId = 777777;
        $game = new Game(
            GameId::fromInt($gameId),
            GameStatus::WAITING,
            $gameId,
            GameMode::OPEN,
            5
        );

        // 🎯 Сохраняем игру в кэш
        $this->repository->save($game);
        
        // 🎯 Загружаем игру из кэша
        $loadedGame = $this->repository->find(GameId::fromInt($gameId));
        
        $this->assertNotNull($loadedGame, 'Игра должна быть загружена из кэша');
        $this->assertEquals($gameId, $loadedGame->getId()->toInt(), 'ID загруженной игры должен совпадать');
        $this->assertEquals(5, $loadedGame->getBaseBet(), 'Базовая ставка должна сохраниться');
    }

    /**
     * 🛠️ ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
     */

    protected function setupLobbyEnvironment()
    {
        $this->repository->saveLobbyGameIds([]);
        
        // Очищаем кэш игр
        for ($i = 1; $i <= 1000; $i++) {
            $this->repository->clear($i);
        }
        
        Cache::flush();
    }

    // В методе createGameWithPlayers ИСПРАВИТЬ:
    protected function createGameWithPlayers(int $playerCount): Game
    {
        $gameId = random_int(100000, 999999);
        $game = new Game(
            GameId::fromInt($gameId),
            GameStatus::WAITING,
            $gameId,
            GameMode::OPEN,
            5
        );

        for ($i = 1; $i <= $playerCount; $i++) {
            $player = new \App\Domain\Game\Entities\Player(
                PlayerId::fromInt($i),
                $i,           // userId
                $i,           // position 
                PlayerStatus::WAITING, // 🎯 status - 4-й параметр
                1000          // balance - 5-й параметр
            );
            $game->addPlayer($player);
        }

        $this->repository->save($game);
        return $game;
    }

    protected function createGameController()
    {
        return new \App\Http\Controllers\GameController(
            app(\App\Application\Services\GameService::class),
            app(\App\Application\Services\DistributionService::class),
            app(\App\Application\Services\BiddingService::class),
            app(\App\Application\Services\QuarrelService::class),
            app(\App\Application\Services\ReadinessService::class)
        );
    }

    protected function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }

    protected function mockPlayerWithUserId($player, $userId)
    {
        $reflection = new \ReflectionClass($player);
        $property = $reflection->getProperty('userId');
        $property->setAccessible(true);
        $property->setValue($player, $userId);
        
        return $player;
    }
}