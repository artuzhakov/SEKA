<!-- resources/js/Pages/SekaLobby.vue -->
<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-900 to-emerald-900 text-white p-4">
    <!-- Compact Header -->
    <div class="max-w-6xl mx-auto mb-6">
      <div class="flex justify-between items-center">
        <div class="flex items-center gap-4">
          <h1 class="text-2xl font-bold text-emerald-400">SEKA</h1>
          <div class="text-sm text-gray-300">
            <span class="font-bold">{{ totalPlayers }}</span> игроков онлайн • 
            <span class="font-bold">{{ availableTablesCount }}</span> столов доступно
          </div>
        </div>
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
            <div class="w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center text-xs">
              {{ user.name.charAt(0) }}
            </div>
            <span class="text-sm text-gray-300">{{ user.name }}</span>
          </div>
          <Link href="/dashboard" class="text-sm px-3 py-1 bg-gray-700 rounded hover:bg-gray-600">
            Профиль
          </Link>
          <button @click="logout" class="text-sm px-3 py-1 bg-red-600 rounded hover:bg-red-700">
            Выйти
          </button>
        </div>
      </div>
    </div>

    <!-- Compact Tables Grid -->
    <div class="max-w-6xl mx-auto">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <GameRoomCard
          v-for="table in gameTables"
          :key="table.id"
          :table="table"
          @join="handleJoinTable"
        />
      </div>

      <!-- Quick Create Section -->
      <div class="bg-gray-800/50 rounded-lg border border-emerald-500/30 p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <select
              v-model="newTableType"
              class="px-3 py-2 bg-gray-700 border border-gray-600 rounded text-sm focus:ring-2 focus:ring-emerald-500 text-white"
            >
              <option value="novice">Новички (5-25)</option>
              <option value="amateur">Любители (10-100)</option>
              <option value="pro">Профи (25-250)</option>
              <option value="master">Мастера (50-500)</option>
            </select>
            
            <select
              v-model="newTablePlayers"
              class="px-3 py-2 bg-gray-700 border border-gray-600 rounded text-sm focus:ring-2 focus:ring-emerald-500 text-white"
            >
              <option value="2">2 игрока</option>
              <option value="3">3 игрока</option>
              <option value="4">4 игрока</option>
              <option value="5">5 игроков</option>
              <option value="6">6 игроков</option>
            </select>
          </div>
          
          <button
            @click="createNewTable"
            class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded text-sm font-medium transition-colors"
          >
            Создать стол
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import GameRoomCard from '@/components/seka/components/GameRoomCard.vue'

const props = defineProps({
  user: Object,
  auth: Object,
  errors: Object
})

// Configuration for table types
const TABLE_TYPES = {
  novice: { minBet: 5, maxBet: 25, buyIn: 5, name: "Новички", color: "green" },
  amateur: { minBet: 10, maxBet: 100, buyIn: 10, name: "Любители", color: "blue" },
  pro: { minBet: 25, maxBet: 250, buyIn: 25, name: "Профи", color: "purple" },
  master: { minBet: 50, maxBet: 500, buyIn: 50, name: "Мастера", color: "gold" }
}

// State
const gameTables = ref([])
const newTableType = ref('novice')
const newTablePlayers = ref(6)
let tableIdCounter = 1

// Computed
const totalPlayers = computed(() => {
  return gameTables.value.reduce((sum, table) => sum + table.players, 0)
})

const availableTablesCount = computed(() => {
  return gameTables.value.filter(table => table.status === 'available').length
})

// Methods
const initializeTables = () => {
  // Создаем начальные столы с разным количеством игроков для реализма
  gameTables.value = [
    createTable(TABLE_TYPES.novice, 0),
    createTable(TABLE_TYPES.amateur, 1),
    createTable(TABLE_TYPES.pro, 2),
    createTable(TABLE_TYPES.master, 0)
  ]
}

const createTable = (config, initialPlayers = 0) => {
  const isFull = initialPlayers >= 6
  return {
    id: tableIdCounter++,
    name: config.name,
    minBet: config.minBet,
    maxBet: config.maxBet,
    buyIn: config.buyIn,
    players: initialPlayers,
    maxPlayers: 6,
    status: isFull ? 'full' : 'available',
    color: config.color,
    type: Object.keys(TABLE_TYPES).find(key => TABLE_TYPES[key].name === config.name)
  }
}

const handleJoinTable = (tableId) => {
  const table = gameTables.value.find(t => t.id === tableId)
  if (!table || table.status === 'full') return

  table.players++
  
  if (table.players >= table.maxPlayers) {
    table.status = 'full'
    createNewTableOfType(table.type)
  }
  
  setTimeout(() => {
    router.visit(`/game/${tableId}`)
  }, 500)
}

const createNewTableOfType = (type) => {
  const config = TABLE_TYPES[type]
  const newTable = createTable(config, 0)
  gameTables.value.push(newTable)
}

const createNewTable = () => {
  const config = TABLE_TYPES[newTableType.value]
  const newTable = createTable(config, 1)
  gameTables.value.push(newTable)
  
  setTimeout(() => {
    router.visit(`/game/${newTable.id}`)
  }, 500)
}

const logout = () => {
  router.post('/logout')
}

// Симуляция активности игроков
const simulatePlayerActivity = () => {
  setInterval(() => {
    if (Math.random() < 0.2) {
      const availableTables = gameTables.value.filter(t => t.status === 'available')
      if (availableTables.length > 0) {
        const randomTable = availableTables[Math.floor(Math.random() * availableTables.length)]
        randomTable.players++
        
        if (randomTable.players >= randomTable.maxPlayers) {
          randomTable.status = 'full'
          createNewTableOfType(randomTable.type)
        }
      }
    }
  }, 8000)
}

onMounted(() => {
  initializeTables()
  simulatePlayerActivity()
})
</script>