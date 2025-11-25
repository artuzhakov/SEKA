<!-- resources/js/Pages/SekaLobby.vue -->
<template>
  <div class="lobby-container">
    <!-- Header (без изменений) -->
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
            <span class="user-balance">{{ user.balance }}🪙</span>
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
      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <div class="loading-spinner">🎴</div>
        <p>Загрузка столов...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <div class="error-message">
          <h3>❌ Ошибка загрузки</h3>
          <p>{{ error }}</p>
          <button @click="loadTables" class="retry-btn">
            🔄 Попробовать снова
          </button>
        </div>
      </div>

      <!-- Tables by Type -->
      <div v-else class="table-type-section" v-for="tableType in tableTypes" :key="tableType.id">
        <div class="section-header">
          <div class="type-info">
            <span class="type-icon">{{ tableType.icon }}</span>
            <h2 class="type-title">{{ tableType.name }}</h2>
            <div class="type-details">
              <span class="bet-info">ставка {{ tableType.bet }}🪙</span>
              <span class="balance-info">мин. {{ tableType.minBalance }}🪙</span>
            </div>
          </div>
          <div class="section-stats">
            {{ getTablesByType(tableType.id).length }} столов
          </div>
        </div>

        <!-- Horizontal Scroll Container -->
        <div class="tables-scroll-container">
          <div class="tables-scroll">
            <div 
              class="table-card" 
              v-for="table in getTablesByType(tableType.id)" 
              :key="table.id"
            >
              <div class="table-header">
                <h3 class="table-name">{{ table.name || `Стол #${table.id}` }}</h3>
                <div class="players-count">{{ table.players_count }}/6</div>
              </div>
              
              <!-- Players Indicators -->
              <div class="players-indicators">
                <span 
                  v-for="n in 6" 
                  :key="n"
                  class="player-indicator"
                  :class="{ 
                    active: n <= table.players_count,
                    'current-user': isUserAtTable(table)
                  }"
                >
                  ●
                </span>
              </div>
              
              <!-- Join Button -->
              <button
                @click="handleJoinTable(table)"
                class="join-btn"
                :class="{ 
                  'almost-full': table.players_count >= 5,
                  'disabled': table.players_count >= 6,
                  'joined': isUserAtTable(table)
                }"
                :disabled="table.players_count >= 6"
              >
                {{ getJoinButtonText(table) }}
              </button>
            </div>

            <!-- Empty State for Table Type -->
            <div v-if="getTablesByType(tableType.id).length === 0" class="empty-table-card">
              <div class="empty-table-content">
                <span class="empty-icon">🎴</span>
                <p class="empty-text">Нет столов</p>
                <button 
                  v-if="user.isAdmin" 
                  @click="createTableOfType(tableType.id)"
                  class="create-empty-btn"
                >
                  Создать стол
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Create Section (Admin Only) -->
      <div class="create-section" v-if="user.isAdmin">
        <div class="create-card">
          <h3 class="create-title">⚙️ Админ: Создать стол</h3>
          <div class="create-controls">
            <div class="control-group">
              <label class="control-label">Уровень ставок:</label>
              <select
                v-model="newTableType"
                class="control-select"
              >
                <option value="novice">🥉 Новички (5🪙)</option>
                <option value="amateur">🥈 Любители (10🪙)</option>
                <option value="pro">🥇 Профи (25🪙)</option>
                <option value="master">🏆 Мастера (50🪙)</option>
              </select>
            </div>
            
            <button
              @click="createNewTable"
              class="create-btn"
              :disabled="isLoading"
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
import { useLobby } from '../components/seka/composables/useLobby' // 🎯 ИМПОРТИРУЕМ КОМПОЗЕЙБЛ

const props = defineProps({
  user: Object,
  auth: Object,
  errors: Object
})

// 🎯 ИСПОЛЬЗУЕМ КОМПОЗЕЙБЛ
const { 
  tables: gameTables, 
  isLoading, 
  error, 
  loadTables 
} = useLobby()

// Table types configuration
const tableTypes = ref([
  { id: 'novice', name: 'НОВИЧКИ', icon: '🥉', bet: 5, minBalance: 50 },
  { id: 'amateur', name: 'ЛЮБИТЕЛИ', icon: '🥈', bet: 10, minBalance: 100 },
  { id: 'pro', name: 'ПРОФИ', icon: '🥇', bet: 25, minBalance: 250 },
  { id: 'master', name: 'МАСТЕРА', icon: '🏆', bet: 50, minBalance: 500 }
])

// State
const newTableType = ref('novice')

// Computed
const totalPlayers = computed(() => {
  return gameTables.value.reduce((sum, table) => sum + (table.players_count || 0), 0)
})

const availableTablesCount = computed(() => {
  return gameTables.value.filter(table => (table.players_count || 0) < 6).length
})

// Methods
const getTablesByType = (type) => {
  return gameTables.value.filter(table => table.table_type === type)
}

const isUserAtTable = (table) => {
  return false // 🎯 МОЖНО ДОБАВИТЬ ЛОГИКУ ПРОВЕРКИ ПОЛЬЗОВАТЕЛЯ
}

const getJoinButtonText = (table) => {
  const playersCount = table.players_count || 0
  if (isUserAtTable(table)) return 'ВОЙТИ'
  if (playersCount >= 6) return 'ПОЛНЫЙ'
  if (playersCount >= 5) return 'ПОЧТИ ПОЛНЫЙ'
  return 'ПРИСОЕДИНИТЬСЯ'
}

const getCsrfToken = () => {
  return document.querySelector('meta[name="csrf-token"]')?.content
}

const handleJoinTable = async (table) => {
  const playersCount = table.players_count || 0
  if (playersCount >= 6) return

  try {
    console.log('🎯 Joining table:', table.id)
    
    const csrfToken = getCsrfToken()
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
    
    if (csrfToken) {
      headers['X-CSRF-TOKEN'] = csrfToken
    }
    
    const response = await fetch(`/api/seka/games/${table.id}/join`, {
      method: 'POST',
      headers: headers,
      body: JSON.stringify({
        user_id: props.user?.id || 1,
        player_name: props.user?.name || 'Player'
      })
    })

    if (response.ok) {
      const data = await response.json()
      console.log('✅ Join successful - FULL RESPONSE:', data)
      
      // 🎯 ПРОВЕРЯЕМ СТРУКТУРУ ОТВЕТА
      const gameId = data.game_id || data.id || data.game?.id
      console.log('🎯 Extracted Game ID:', gameId)
      
      if (gameId) {
        window.location.href = `/game/${gameId}`
      } else {
        console.error('❌ No game ID in response:', data)
        alert('Ошибка: не получен ID игры')
      }
    } else {
      const errorText = await response.text()
      console.error('❌ Join failed:', response.status, errorText)
      
      try {
        const errorData = JSON.parse(errorText)
        if (errorData.message?.includes('already joined')) {
          window.location.href = `/game/${table.id}`
          return
        }
        alert(`Ошибка: ${errorData.message || 'Не удалось присоединиться'}`)
      } catch {
        alert(`Ошибка сервера: ${response.status}`)
      }
    }
  } catch (error) {
    console.error('❌ Join game error:', error)
    alert('Ошибка присоединения к игре: ' + error.message)
  }
}

const createNewTable = async () => {
  try {
    const csrfToken = getCsrfToken()
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json', 
      'X-Requested-With': 'XMLHttpRequest'
    }
    
    if (csrfToken) {
      headers['X-CSRF-TOKEN'] = csrfToken
    }
    
    const response = await fetch('/api/seka/games', {
      method: 'POST',
      headers: headers,
      body: JSON.stringify({
        user_id: props.user?.id || 1,
        table_type: newTableType.value,
        player_name: props.user?.name || 'Player'
      })
    })

    if (response.ok) {
      const gameData = await response.json()
      const gameId = gameData.game?.id || gameData.id
      if (gameId) {
        window.location.href = `/game/${gameId}`
      } else {
        alert('Ошибка: не получен ID игры')
      }
    } else {
      const errorText = await response.text()
      console.error('❌ Create failed:', response.status, errorText)
      alert(`Ошибка создания стола: ${response.status}`)
    }
  } catch (error) {
    console.error('❌ Create table error:', error)
    alert('Ошибка создания стола: ' + error.message)
  }
}

const createTableOfType = (type) => {
  newTableType.value = type
  createNewTable()
}

const logout = () => {
  router.post('/logout')
}

// 🎯 onMounted УЖЕ В КОМПОЗЕЙБЛЕ - НИЧЕГО НЕ ДЕЛАЕМ
</script>

<style scoped>
/* Стили остаются без изменений */
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
  max-width: 1400px;
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
  gap: 12px;
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

.user-balance {
  font-size: 0.9rem;
  font-weight: bold;
  color: #fbbf24;
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
  max-width: 1400px;
  margin: 0 auto;
}

/* Table Type Sections */
.table-type-section {
  margin-bottom: 40px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding: 0 10px;
}

.type-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.type-icon {
  font-size: 2rem;
}

.type-title {
  font-size: 1.5rem;
  font-weight: bold;
  color: #e5e7eb;
  margin: 0;
}

.type-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.bet-info {
  font-size: 0.9rem;
  color: #fbbf24;
  font-weight: 500;
}

.balance-info {
  font-size: 0.8rem;
  color: #9ca3af;
}

.section-stats {
  font-size: 0.9rem;
  color: #9ca3af;
}

/* Horizontal Scroll */
.tables-scroll-container {
  overflow-x: auto;
  padding: 10px 0;
  margin: 0 -10px;
}

.tables-scroll {
  display: flex;
  gap: 15px;
  padding: 0 10px;
  min-width: min-content;
}

/* Table Cards */
.table-card {
  background: rgba(0, 0, 0, 0.4);
  border: 2px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 20px;
  min-width: 200px;
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
  flex-shrink: 0;
}

.table-card:hover {
  border-color: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.table-name {
  font-size: 1.1rem;
  font-weight: bold;
  color: #e5e7eb;
  margin: 0;
}

.players-count {
  font-size: 0.9rem;
  color: #9ca3af;
}

/* Players Indicators */
.players-indicators {
  display: flex;
  justify-content: center;
  gap: 6px;
  margin-bottom: 20px;
}

.player-indicator {
  font-size: 1.2rem;
  color: #4b5563;
  transition: all 0.3s ease;
}

.player-indicator.active {
  color: #10b981;
}

.player-indicator.current-user {
  color: #fbbf24;
  transform: scale(1.2);
}

/* Join Buttons */
.join-btn {
  width: 100%;
  padding: 12px;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s ease;
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.join-btn:hover:not(.disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.join-btn.almost-full {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}

.join-btn.disabled {
  background: #6b7280;
  cursor: not-allowed;
  opacity: 0.6;
}

.join-btn.joined {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

/* Create Section */
.create-section {
  display: flex;
  justify-content: center;
  margin-top: 40px;
}

.create-card {
  background: rgba(0, 0, 0, 0.3);
  border-radius: 15px;
  padding: 25px;
  border: 2px solid #10b981;
  backdrop-filter: blur(10px);
  width: 100%;
  max-width: 400px;
}

.create-title {
  font-size: 1.2rem;
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

/* Scrollbar Styling */
.tables-scroll-container::-webkit-scrollbar {
  height: 8px;
}

.tables-scroll-container::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
}

.tables-scroll-container::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 4px;
}

.tables-scroll-container::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}

/* Добавляем стили для состояний загрузки и ошибок */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: white;
}

.loading-spinner {
  font-size: 3rem;
  animation: spin 2s linear infinite;
  margin-bottom: 20px;
}

.error-state {
  display: flex;
  justify-content: center;
  padding: 40px 20px;
}

.error-message {
  background: rgba(220, 38, 38, 0.9);
  color: white;
  padding: 2rem;
  border-radius: 10px;
  text-align: center;
  max-width: 400px;
}

.retry-btn {
  background: white;
  color: #dc2626;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: bold;
  margin-top: 15px;
  cursor: pointer;
  transition: all 0.2s;
}

.retry-btn:hover {
  background: #f3f4f6;
}

.empty-table-card {
  background: rgba(255, 255, 255, 0.05);
  border: 2px dashed rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  padding: 30px 20px;
  min-width: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.empty-table-content {
  text-align: center;
  color: rgba(255, 255, 255, 0.6);
}

.empty-icon {
  font-size: 2rem;
  display: block;
  margin-bottom: 10px;
}

.empty-text {
  margin: 0 0 15px 0;
  font-size: 0.9rem;
}

.create-empty-btn {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.2s;
}

.create-empty-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
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
  
  .header-right {
    flex-direction: column;
    gap: 10px;
  }
  
  .user-info {
    justify-content: center;
  }
  
  .section-header {
    flex-direction: column;
    gap: 10px;
    text-align: center;
  }
  
  .type-info {
    flex-direction: column;
    gap: 10px;
  }
  
  .table-card {
    min-width: 180px;
    padding: 15px;
  }
  
  .create-card {
    padding: 20px;
  }
}
</style>