<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-900 to-blue-900 text-white">
    <!-- Navigation -->
    <nav class="bg-gray-800 border-b border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <span class="text-2xl font-bold text-purple-400">🎴 SEKA</span>
          </div>
          <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
              <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                <span class="text-sm font-bold">{{ user.name.charAt(0) }}</span>
              </div>
              <span class="text-gray-300">{{ user.name }}</span>
            </div>
            <Link href="/dashboard" class="text-gray-300 hover:text-white px-3 py-2 rounded-lg bg-gray-700">
              Профиль
            </Link>
            <button @click="logout" class="text-gray-300 hover:text-white px-3 py-2 rounded-lg bg-red-600 hover:bg-red-700">
              Выйти
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Lobby Content -->
    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-12">
          <h1 class="text-4xl md:text-5xl font-bold mb-4 bg-gradient-to-r from-purple-400 to-blue-400 bg-clip-text text-transparent">
            Игровое Лобби
          </h1>
          <p class="text-xl text-gray-300 max-w-3xl mx-auto">
            Выберите игру или создайте новую. Присоединяйтесь к сообществу игроков SEKA!
          </p>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
          <!-- Create Game -->
          <div class="bg-gray-800 rounded-2xl border border-gray-700 p-8 hover:border-purple-500 transition-all duration-300 hover:transform hover:scale-105">
            <div class="text-center mb-6">
              <div class="text-5xl mb-4">🎮</div>
              <h2 class="text-2xl font-bold mb-3">Создать игру</h2>
              <p class="text-gray-400">Создайте приватную игру</p>
            </div>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">
                  Название комнаты
                </label>
                <input
                  v-model="newGame.name"
                  type="text"
                  class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-white"
                  placeholder="Введите название..."
                >
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-2">
                    Игроков
                  </label>
                  <select
                    v-model="newGame.max_players"
                    class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 text-white"
                  >
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-2">
                    Ставка
                  </label>
                  <select
                    v-model="newGame.starting_balance"
                    class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 text-white"
                  >
                    <option value="500">500 ₽</option>
                    <option value="1000">1000 ₽</option>
                    <option value="2000">2000 ₽</option>
                    <option value="5000">5000 ₽</option>
                  </select>
                </div>
              </div>

              <button
                @click="createGame"
                :disabled="isCreatingGame"
                class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-4 px-6 rounded-xl hover:from-purple-700 hover:to-blue-700 font-bold text-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="isCreatingGame">
                  <span class="animate-spin inline-block mr-2">⟳</span>
                  Создание...
                </span>
                <span v-else>🎮 СОЗДАТЬ ИГРУ</span>
              </button>
            </div>
          </div>

          <!-- Quick Join -->
          <div class="bg-gray-800 rounded-2xl border border-gray-700 p-8 hover:border-green-500 transition-all duration-300">
            <div class="text-center mb-6">
              <div class="text-5xl mb-4">⚡</div>
              <h2 class="text-2xl font-bold mb-3">Быстрый старт</h2>
              <p class="text-gray-400">Присоединитесь к случайной игре</p>
            </div>

            <div class="space-y-6">
              <button
                @click="quickJoin"
                :disabled="availableGames.length === 0 || isQuickJoining"
                class="w-full bg-gradient-to-r from-green-600 to-teal-600 text-white py-4 px-6 rounded-xl hover:from-green-700 hover:to-teal-700 font-bold text-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="isQuickJoining">
                  <span class="animate-spin inline-block mr-2">⟳</span>
                  Поиск...
                </span>
                <span v-else>🎯 БЫСТРОЕ ПРИСОЕДИНЕНИЕ</span>
              </button>

              <div class="text-center p-4 bg-gray-700 rounded-xl">
                <div class="text-3xl font-bold text-green-400 mb-2">{{ availableGames.length }}</div>
                <div class="text-gray-300">доступных игр</div>
              </div>
            </div>
          </div>

          <!-- User Stats -->
          <div class="bg-gray-800 rounded-2xl border border-gray-700 p-8">
            <div class="text-center mb-6">
              <div class="text-5xl mb-4">🏆</div>
              <h2 class="text-2xl font-bold mb-3">Ваша статистика</h2>
              <p class="text-gray-400">Обзор ваших достижений</p>
            </div>

            <div class="space-y-4">
              <div class="flex justify-between items-center p-3 bg-gray-700 rounded-lg">
                <span class="text-gray-300">Игр сыграно:</span>
                <span class="font-bold text-white">{{ userStats.totalGames }}</span>
              </div>
              <div class="flex justify-between items-center p-3 bg-gray-700 rounded-lg">
                <span class="text-gray-300">Побед:</span>
                <span class="font-bold text-green-400">{{ userStats.wins }}</span>
              </div>
              <div class="flex justify-between items-center p-3 bg-gray-700 rounded-lg">
                <span class="text-gray-300">Процент побед:</span>
                <span class="font-bold text-yellow-400">{{ userStats.winRate }}%</span>
              </div>
              <div class="flex justify-between items-center p-3 bg-gray-700 rounded-lg">
                <span class="text-gray-300">Баланс:</span>
                <span class="font-bold text-blue-400">{{ userStats.balance }} ₽</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Available Games -->
        <div class="bg-gray-800 rounded-2xl border border-gray-700 p-8">
          <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-bold">🎴 Доступные игры</h2>
            <div class="flex items-center space-x-4">
              <div class="flex items-center space-x-2 text-gray-300">
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                <span>Ожидание</span>
              </div>
              <div class="flex items-center space-x-2 text-gray-300">
                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                <span>В процессе</span>
              </div>
              <button
                @click="refreshGames"
                :disabled="isLoading"
                class="bg-gray-700 text-gray-300 px-4 py-2 rounded-xl hover:bg-gray-600 font-medium disabled:opacity-50 transition-colors"
              >
                🔄 Обновить
              </button>
            </div>
          </div>

          <!-- Games List -->
          <div v-if="isLoading" class="text-center py-12">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-purple-500 mx-auto mb-4"></div>
            <p class="text-gray-400">Загрузка списка игр...</p>
          </div>

          <div v-else-if="availableGames.length === 0" class="text-center py-12">
            <div class="text-6xl mb-4">😴</div>
            <p class="text-gray-400 text-xl mb-2">Игровые комнаты пусты</p>
            <p class="text-gray-500">Создайте первую игру и станьте её хостом!</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div
              v-for="game in availableGames"
              :key="game.id"
              class="bg-gray-700 rounded-xl border-2 transition-all duration-300 hover:transform hover:scale-105 cursor-pointer group"
              :class="{
                'border-green-500 hover:border-green-400': game.status === 'waiting',
                'border-yellow-500 hover:border-yellow-400': game.status === 'active'
              }"
              @click="joinGame(game.id)"
            >
              <div class="p-6">
                <!-- Game Header -->
                <div class="flex justify-between items-start mb-4">
                  <h3 class="text-lg font-bold text-white group-hover:text-purple-300 transition-colors">
                    {{ game.name || `Комната #${game.id}` }}
                  </h3>
                  <span
                    class="px-3 py-1 rounded-full text-xs font-bold"
                    :class="{
                      'bg-green-500 text-white': game.status === 'waiting',
                      'bg-yellow-500 text-white': game.status === 'active'
                    }"
                  >
                    {{ game.status === 'waiting' ? 'ОЖИДАНИЕ' : 'В ИГРЕ' }}
                  </span>
                </div>

                <!-- Game Info -->
                <div class="space-y-3 mb-4">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Игроки:</span>
                    <span class="text-white font-medium">
                      {{ game.players_count }}/{{ game.max_players }}
                    </span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Ставка:</span>
                    <span class="text-blue-400 font-medium">{{ game.starting_balance }} ₽</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Создана:</span>
                    <span class="text-gray-300">{{ formatTime(game.created_at) }}</span>
                  </div>
                </div>

                <!-- Players Avatars -->
                <div class="flex items-center justify-between">
                  <div class="flex -space-x-2">
                    <div
                      v-for="player in game.players"
                      :key="player.id"
                      class="w-8 h-8 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full border-2 border-gray-700 flex items-center justify-center text-xs font-bold text-white"
                      :title="player.name"
                    >
                      {{ player.name.charAt(0) }}
                    </div>
                    <div
                      v-for="n in (game.max_players - game.players_count)"
                      :key="'empty-' + n"
                      class="w-8 h-8 bg-gray-600 rounded-full border-2 border-gray-700 flex items-center justify-center text-xs text-gray-400"
                    >
                      +
                    </div>
                  </div>
                  
                  <div class="text-right">
                    <div class="text-green-400 text-sm font-bold group-hover:text-green-300 transition-colors">
                      ПРИСОЕДИНИТЬСЯ
                    </div>
                    <div class="text-gray-400 text-xs">Нажмите для входа</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Test Section -->
        <div class="mt-8 bg-gradient-to-r from-purple-900 to-blue-900 rounded-2xl border border-purple-700 p-8">
          <h3 class="text-xl font-bold mb-4 text-center">🎯 Тестовые игры (для разработки)</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button
              v-for="testGame in testGames"
              :key="testGame.id"
              @click="joinGame(testGame.id)"
              class="bg-gray-700 hover:bg-gray-600 text-white py-3 px-4 rounded-xl border border-gray-600 hover:border-purple-500 transition-all duration-300 text-center"
            >
              <div class="font-bold mb-1">Тест #{{ testGame.id }}</div>
              <div class="text-sm text-gray-300">{{ testGame.players_count }}/{{ testGame.max_players }} игроков</div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  user: Object,
  auth: Object,
  errors: Object
})

// State
const isLoading = ref(false)
const isCreatingGame = ref(false)
const isQuickJoining = ref(false)

const newGame = ref({
  name: '',
  max_players: 6,
  starting_balance: 1000
})

const availableGames = ref([])
const testGames = ref([
  { id: 1, name: 'Тестовая игра 1', status: 'waiting', players_count: 1, max_players: 6 },
  { id: 2, name: 'Тестовая игра 2', status: 'waiting', players_count: 2, max_players: 6 },
  { id: 3, name: 'Тестовая игра 3', status: 'active', players_count: 4, max_players: 6 }
])

const userStats = ref({
  totalGames: 15,
  wins: 8,
  winRate: 53,
  balance: 2450
})

// Methods
const loadGames = async () => {
  isLoading.value = true
  try {
    // Моковые данные в стиле SEKA
    availableGames.value = [
      {
        id: 101,
        name: 'Вечерний турнир',
        status: 'waiting',
        players_count: 3,
        max_players: 6,
        starting_balance: 1000,
        created_at: new Date(Date.now() - 15 * 60 * 1000).toISOString(),
        players: [
          { id: 1, name: 'Алексей' },
          { id: 2, name: 'Мария' },
          { id: 3, name: 'Дмитрий' }
        ]
      },
      {
        id: 102,
        name: 'Быстрая партия',
        status: 'waiting', 
        players_count: 1,
        max_players: 4,
        starting_balance: 500,
        created_at: new Date(Date.now() - 8 * 60 * 1000).toISOString(),
        players: [
          { id: 4, name: 'Сергей' }
        ]
      },
      {
        id: 103,
        name: 'Профессиональная игра',
        status: 'active',
        players_count: 5,
        max_players: 6,
        starting_balance: 2000,
        created_at: new Date(Date.now() - 25 * 60 * 1000).toISOString(),
        players: [
          { id: 5, name: 'Анна' },
          { id: 6, name: 'Иван' },
          { id: 7, name: 'Ольга' },
          { id: 8, name: 'Павел' },
          { id: 9, name: 'Елена' }
        ]
      }
    ]
  } catch (error) {
    console.error('Ошибка загрузки игр:', error)
  } finally {
    isLoading.value = false
  }
}

const createGame = async () => {
  if (!newGame.value.name.trim()) {
    newGame.value.name = `Игра ${props.user.name}`
  }
  
  isCreatingGame.value = true
  try {
    const gameId = Math.floor(Math.random() * 1000) + 100
    
    const game = {
      id: gameId,
      name: newGame.value.name,
      status: 'waiting',
      players_count: 1,
      max_players: newGame.value.max_players,
      starting_balance: newGame.value.starting_balance,
      created_at: new Date().toISOString(),
      players: [
        { id: props.user.id, name: props.user.name }
      ]
    }
    
    availableGames.value.unshift(game)
    
    // Переходим в игру
    setTimeout(() => {
      router.visit(`/game/${gameId}`)
    }, 1000)
    
  } catch (error) {
    console.error('Ошибка создания игры:', error)
    alert('Не удалось создать игру')
  } finally {
    isCreatingGame.value = false
  }
}

const quickJoin = async () => {
  isQuickJoining.value = true
  try {
    if (availableGames.value.length > 0) {
      const game = availableGames.value.find(g => g.status === 'waiting' && g.players_count < g.max_players)
      if (game) {
        await joinGame(game.id)
      } else {
        alert('Нет подходящих игр для быстрого присоединения')
      }
    }
  } catch (error) {
    console.error('Ошибка быстрого присоединения:', error)
  } finally {
    isQuickJoining.value = false
  }
}

const joinGame = async (gameId) => {
  try {
    router.visit(`/game/${gameId}`)
  } catch (error) {
    console.error('Ошибка присоединения к игре:', error)
    alert('Не удалось присоединиться к игре')
  }
}

const refreshGames = () => {
  loadGames()
}

const logout = () => {
  router.post('/logout')
}

const formatTime = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMinutes = Math.floor((now - date) / (1000 * 60))
  
  if (diffMinutes < 1) return 'только что'
  if (diffMinutes < 60) return `${diffMinutes} мин назад`
  
  const diffHours = Math.floor(diffMinutes / 60)
  if (diffHours < 24) return `${diffHours} ч назад`
  
  return date.toLocaleDateString('ru-RU')
}

// Lifecycle
onMounted(() => {
  loadGames()
})
</script>