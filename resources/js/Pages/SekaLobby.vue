<!-- resources/js/Pages/SekaLobby.vue -->
<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Header -->
      <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">🎴 Лобби SEKA</h1>
            <p class="text-gray-600 mt-2">Выберите игру или создайте новую</p>
          </div>
          <div class="flex items-center space-x-4">
            <div class="text-right">
              <p class="text-sm text-gray-500">Игрок</p>
              <p class="font-semibold">{{ user.name }}</p>
            </div>
            <Link 
              :href="route('dashboard')" 
              class="bg-gray-100 px-4 py-2 rounded-lg hover:bg-gray-200"
            >
              Назад
            </Link>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Games List -->
        <div class="lg:col-span-3">
          <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-xl font-semibold">Доступные игры</h2>
              <div class="flex space-x-2">
                <button 
                  @click="loadGames"
                  class="bg-gray-100 px-3 py-2 rounded-lg hover:bg-gray-200"
                >
                  🔄
                </button>
              </div>
            </div>

            <!-- Games List -->
            <div v-if="isLoading" class="text-center py-8">
              <p class="text-gray-500">Загрузка игр...</p>
            </div>

            <div v-else-if="availableGames.length === 0" class="text-center py-8">
              <div class="text-6xl mb-4">🎴</div>
              <h3 class="text-lg font-semibold mb-2">Нет доступных игр</h3>
              <p class="text-gray-500 mb-4">Создайте первую игру и пригласите друзей!</p>
            </div>

            <div v-else class="space-y-4">
              <div 
                v-for="game in availableGames" 
                :key="game.id"
                class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition"
              >
                <div class="flex justify-between items-center">
                  <div>
                    <h3 class="font-semibold">Игра #{{ game.id }}</h3>
                    <div class="flex items-center space-x-4 text-sm text-gray-500 mt-1">
                      <span>👤 {{ game.players_count }}/6 игроков</span>
                      <span :class="getStatusClass(game.status)">{{ getGameStatusText(game.status) }}</span>
                      <span>💰 Ставка: {{ game.stake }} ₽</span>
                    </div>
                  </div>
                  <div class="flex space-x-2">
                    <button 
                      @click="joinGame(game.id)"
                      :disabled="game.status !== 'waiting'"
                      class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
                    >
                      Присоединиться
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Create Game -->
          <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Создать игру</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Ставка
                </label>
                <select v-model="newGameStake" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option value="10">10 ₽</option>
                  <option value="25">25 ₽</option>
                  <option value="50">50 ₽</option>
                  <option value="100">100 ₽</option>
                </select>
              </div>
              <button 
                @click="createGame"
                :disabled="isCreating"
                class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 disabled:bg-gray-300"
              >
                🎲 Создать игру
              </button>
            </div>
          </div>

          <!-- User Stats -->
          <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Ваша статистика</h3>
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-gray-600">Баланс:</span>
                <span class="font-semibold">{{ user.wallet?.balance || 0 }} ₽</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Сыграно игр:</span>
                <span class="font-semibold">0</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Побед:</span>
                <span class="font-semibold">0</span>
              </div>
            </div>
          </div>

          <!-- Quick Help -->
          <div class="bg-blue-50 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-2">📚 Правила SEKA</h3>
            <p class="text-sm text-blue-700 mb-3">
              Уникальная карточная игра с тремя кругами торгов и системой темной игры.
            </p>
            <button class="text-blue-600 text-sm font-semibold hover:text-blue-800">
              Изучить правила →
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  user: Object
})

const availableGames = ref([])
const isLoading = ref(false)
const isCreating = ref(false)
const newGameStake = ref('10')

const getGameStatusText = (status) => {
  const statusMap = {
    'waiting': 'Ожидание игроков',
    'active': 'В процессе',
    'finished': 'Завершена'
  }
  return statusMap[status] || status
}

const getStatusClass = (status) => {
  const classMap = {
    'waiting': 'text-green-600',
    'active': 'text-orange-600',
    'finished': 'text-gray-600'
  }
  return classMap[status] || 'text-gray-600'
}

const loadGames = async () => {
  try {
    isLoading.value = true
    // В реальности здесь будет запрос к API
    // const response = await axios.get('/api/seka/games')
    
    // Заглушка для демонстрации
    setTimeout(() => {
      availableGames.value = [
        { id: 1, players_count: 2, status: 'waiting', stake: 10 },
        { id: 2, players_count: 4, status: 'active', stake: 25 },
        { id: 3, players_count: 1, status: 'waiting', stake: 50 }
      ]
      isLoading.value = false
    }, 1000)
  } catch (error) {
    console.error('Failed to load games:', error)
    isLoading.value = false
  }
}

const createGame = async () => {
  try {
    isCreating.value = true
    // В реальности здесь будет запрос к API
    // const response = await axios.post('/api/seka/start', { stake: newGameStake.value })
    
    // Заглушка для демонстрации
    setTimeout(() => {
      const newGameId = Math.floor(Math.random() * 1000) + 100
      joinGame(newGameId)
      isCreating.value = false
    }, 1500)
  } catch (error) {
    console.error('Failed to create game:', error)
    isCreating.value = false
  }
}

const joinGame = (gameId) => {
  window.location.href = `/seka-game/${gameId}`
}

onMounted(() => {
  loadGames()
})
</script>