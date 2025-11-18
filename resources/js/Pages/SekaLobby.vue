<!-- resources/js/Pages/SekaLobby.vue -->
<template>
  <div class="lobby-container">
    <!-- Header -->
    <div class="lobby-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="logo">🎴 SEKA</h1>
          <div class="stats">
            <span class="stat-item">
              <span class="stat-value">{{ totalPlayers }}</span>
              <span class="stat-label">игроков онлайн</span>
            </span>
            <span class="stat-divider">•</span>
            <span class="stat-item">
              <span class="stat-value">{{ availableTablesCount }}</span>
              <span class="stat-label">столов доступно</span>
            </span>
          </div>
        </div>
        
        <div class="header-right">
          <div class="user-info">
            <div class="user-avatar">
              {{ user.name.charAt(0) }}
            </div>
            <span class="user-name">{{ user.name }}</span>
          </div>
          <Link href="/dashboard" class="profile-btn">
            Профиль
          </Link>
          <button @click="logout" class="logout-btn">
            Выйти
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="lobby-content">
      <!-- Tables Grid -->
      <div class="tables-section">
        <h2 class="section-title">Игровые столы</h2>
        <div class="tables-grid">
          <GameRoomCard
            v-for="table in gameTables"
            :key="table.id"
            :table="table"
            @join="handleJoinTable"
          />
        </div>
      </div>

      <!-- Quick Create Section -->
      <div class="create-section">
        <div class="create-card">
          <h3 class="create-title">Создать новый стол</h3>
          <div class="create-controls">
            <div class="control-group">
              <label class="control-label">Уровень ставок:</label>
              <select
                v-model="newTableType"
                class="control-select"
              >
                <option value="novice">🥉 Новички (5-25🪙)</option>
                <option value="amateur">🥈 Любители (10-100🪙)</option>
                <option value="pro">🥇 Профи (25-250🪙)</option>
                <option value="master">🏆 Мастера (50-500🪙)</option>
              </select>
            </div>
            
            <div class="control-group">
              <label class="control-label">Количество игроков:</label>
              <select
                v-model="newTablePlayers"
                class="control-select"
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
              class="create-btn"
            >
              🎯 Создать стол
            </button>
          </div>
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
  gameTables.value = [
    createTable(TABLE_TYPES.novice, 2),
    createTable(TABLE_TYPES.amateur, 1),
    createTable(TABLE_TYPES.pro, 3),
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

const handleJoinTable = async (tableId) => {
  const table = gameTables.value.find(t => t.id === tableId)
  if (!table || table.status === 'full') return

  try {
    console.log('🎯 Joining table:', tableId)
    
    // 🎯 ИСПРАВЛЕНИЕ: Используем публичные маршруты БЕЗ CSRF
    const response = await fetch(`/api/public/seka/games/${tableId}/join`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
        // 🎯 УБИРАЕМ X-CSRF-TOKEN - он не нужен для публичных маршрутов
      },
      body: JSON.stringify({
        user_id: props.user?.id || 1,
        player_name: props.user?.name || 'Player'
      })
    })

    console.log('🎯 Response status:', response.status)
    
    if (response.ok) {
      const data = await response.json()
      console.log('✅ Join successful:', data)
      
      // 🎯 ОБНОВЛЯЕМ список игр после присоединения
      await loadRealGames()
      
      // ✅ Успешно присоединились - переходим в игру
      window.location.href = `/game/${tableId}`
    } else {
      const errorText = await response.text()
      console.error('❌ Join failed:', response.status, errorText)
      
      try {
        const errorData = JSON.parse(errorText)
        
        // 🎯 ВАЖНОЕ ИСПРАВЛЕНИЕ: Обработка "игрок уже в игре"
        if (errorData.message?.includes('already joined') || 
            errorData.message?.includes('уже присоединился')) {
          console.log('ℹ️ Player already in game, redirecting...')
          window.location.href = `/game/${tableId}`
          return // 🎯 Выходим из функции
        }
        
        alert(`Ошибка: ${errorData.message || 'Не удалось присоединиться'}`)
      } catch {
        alert(`Ошибка сервера: ${response.status}. Проверьте консоль для деталей.`)
      }
    }
  } catch (error) {
    console.error('❌ Join game error:', error)
    alert('Ошибка присоединения к игре: ' + error.message)
  }
}

const createNewTable = async () => {
  try {
    console.log('🎯 Creating new table...')
    
    // 🎯 ИСПРАВЛЕНИЕ: Используем публичные маршруты БЕЗ CSRF
    const response = await fetch('/api/public/seka/games', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
        // 🎯 УБИРАЕМ X-CSRF-TOKEN
      },
      body: JSON.stringify({
        user_id: props.user?.id || 1,
        table_type: newTableType.value,
        player_name: props.user?.name || 'Player'
      })
    })

    console.log('🎯 Create response status:', response.status)
    
    if (response.ok) {
      const gameData = await response.json()
      console.log('✅ Create successful:', gameData)
      
      // 🎯 ОБНОВЛЯЕМ список игр после создания
      await loadRealGames()
      
      // ✅ Успешно создали - переходим в игру
      const gameId = gameData.game?.id || gameData.id
      if (gameId) {
        window.location.href = `/game/${gameId}`
      } else {
        alert('Ошибка: не получен ID игры')
      }
    } else {
      const errorText = await response.text()
      console.error('❌ Create failed:', response.status, errorText)
      
      try {
        const errorData = JSON.parse(errorText)
        alert(`Ошибка: ${errorData.message || 'Не удалось создать стол'}`)
      } catch {
        alert(`Ошибка сервера: ${response.status}`)
      }
    }
  } catch (error) {
    console.error('❌ Create table error:', error)
    alert('Ошибка создания стола: ' + error.message)
  }
}

// 🎯 ДОБАВЬТЕ fallback если API не работает
const loadRealGames = async () => {
  try {
    console.log('🎯 Loading real games from API...')
    const response = await fetch('/api/public/seka/lobby', {
      headers: {
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      console.log('✅ Real games loaded:', data)
      
      if (data.success && data.games) {
        gameTables.value = data.games.map(game => ({
          id: game.id,
          name: game.name || `Стол #${game.id}`,
          minBet: game.base_bet || 5,
          maxBet: (game.base_bet || 5) * 5,
          buyIn: game.base_bet || 5,
          players: game.players_count || 0,
          maxPlayers: game.max_players || 6,
          status: game.players_count >= (game.max_players || 6) ? 'full' : 'available',
          color: getColorByBet(game.base_bet || 5),
          type: getTypeByBet(game.base_bet || 5)
        }))
      }
    } else {
      console.warn('⚠️ Could not load real games, using mock data')
      initializeTables() // fallback на мок данные
    }
  } catch (error) {
    console.error('❌ Error loading real games:', error)
    initializeTables() // fallback на мок данные
  }
}

const createNewTableOfType = (type) => {
  const config = TABLE_TYPES[type]
  const newTable = createTable(config, 0)
  gameTables.value.push(newTable)
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

const getColorByBet = (bet) => {
  if (bet <= 5) return 'green'
  if (bet <= 10) return 'blue' 
  if (bet <= 25) return 'purple'
  return 'gold'
}

const getTypeByBet = (bet) => {
  if (bet <= 5) return 'novice'
  if (bet <= 10) return 'amateur'
  if (bet <= 25) return 'pro'
  return 'master'
}

onMounted(() => {
  loadRealGames()
})
</script>

<style scoped>
.lobby-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #0a2f0a 0%, #1a5a1a 100%);
  color: white;
  padding: 20px;
}

.lobby-header {
  background: rgba(0, 0, 0, 0.3);
  border-radius: 15px;
  padding: 20px;
  margin-bottom: 30px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 30px;
}

.logo {
  font-size: 2.5rem;
  font-weight: bold;
  color: #10b981;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
}

.stats {
  display: flex;
  align-items: center;
  gap: 15px;
  font-size: 0.9rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-value {
  font-size: 1.2rem;
  font-weight: bold;
  color: #fbbf24;
}

.stat-label {
  font-size: 0.8rem;
  color: #9ca3af;
  margin-top: 2px;
}

.stat-divider {
  color: #6b7280;
  font-weight: bold;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 15px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(255, 255, 255, 0.1);
  padding: 8px 15px;
  border-radius: 25px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.user-avatar {
  width: 32px;
  height: 32px;
  background: #10b981;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 0.9rem;
}

.user-name {
  font-size: 0.9rem;
  color: #e5e7eb;
}

.profile-btn, .logout-btn {
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.2s;
  border: none;
  cursor: pointer;
}

.profile-btn {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.profile-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.logout-btn {
  background: #dc2626;
  color: white;
}

.logout-btn:hover {
  background: #b91c1c;
}

.lobby-content {
  max-width: 1200px;
  margin: 0 auto;
}

.tables-section {
  margin-bottom: 30px;
}

.section-title {
  font-size: 1.8rem;
  font-weight: bold;
  color: #e5e7eb;
  margin-bottom: 20px;
  text-align: center;
}

.tables-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.create-section {
  display: flex;
  justify-content: center;
}

.create-card {
  background: rgba(0, 0, 0, 0.3);
  border-radius: 15px;
  padding: 25px;
  border: 2px solid #10b981;
  backdrop-filter: blur(10px);
  width: 100%;
  max-width: 500px;
}

.create-title {
  font-size: 1.3rem;
  font-weight: bold;
  color: #10b981;
  margin-bottom: 20px;
  text-align: center;
}

.create-controls {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.control-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.control-label {
  font-size: 0.9rem;
  color: #d1d5db;
  font-weight: 500;
}

.control-select {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid #4b5563;
  border-radius: 8px;
  padding: 10px 12px;
  color: white;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.control-select:focus {
  outline: none;
  border-color: #10b981;
  box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}

.create-btn {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  border-radius: 10px;
  padding: 12px 24px;
  font-size: 1rem;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 10px;
}

.create-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

/* Адаптивность */
@media (max-width: 768px) {
  .lobby-container {
    padding: 15px;
  }
  
  .header-content {
    flex-direction: column;
    gap: 15px;
    text-align: center;
  }
  
  .header-left {
    flex-direction: column;
    gap: 15px;
  }
  
  .tables-grid {
    grid-template-columns: 1fr;
  }
  
  .create-card {
    padding: 20px;
  }
}
</style>