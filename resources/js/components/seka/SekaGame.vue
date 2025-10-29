
<!-- resources/js/components/seka/SekaGame.vue -->
<template>
    <div class="seka-game">
        <div v-if="authError" class="auth-error">
          <div class="error-message">
              <h3>🔐 Требуется авторизация</h3>
              <p>{{ authError }}</p>
              <button @click="redirectToLogin" class="btn-login">
              Войти в систему
              </button>
          </div>
        </div>

        <div v-if="error" class="error-message">
            {{ error }}
        </div>

        <div v-if="isLoading" class="loading">
            Загрузка игры...
        </div>

        <template v-else>
        <GameHeader 
            :game-id="gameId"
            :game-status="gameStatus"
            :current-round="currentRound"
            :user="usePage().props.auth.user"
        />

        <GameTable 
            :players="players"
            :current-player-position="currentPlayerPosition"
            :bank="bank"
            :current-round="currentRound"
            :game-status="gameStatus"
        />
        
        <Notifications />
        
        <PlayerControlPanel 
        v-if="gameStatus === 'bidding'"
        :current-player-id="currentPlayerId"
        :players="players"
        :current-player-position="currentPlayerPosition"
        @switch-player="switchPlayer"
        />
        
        <ActionPanel 
          v-if="gameStatus === 'active' && isMyTurn"
          :available-actions="availableActions"
          :current-player-info="currentPlayerInfo"
          :current-max-bet="currentMaxBet"
          @take-action="takeAction"
          @show-raise-modal="showRaiseModal = true"
        />
        
        <RaiseModal 
          v-if="showRaiseModal"
          :min-raise="minRaise"
          :max-raise="maxRaise"
          :current-player-info="currentPlayerInfo"
          :current-max-bet="currentMaxBet"
          @execute-raise="executeRaise"
          @cancel="cancelRaise"
        />
        
        <GameTable 
        :players="players"
        :player-cards="playerCards"
        :current-player-position="currentPlayerPosition"
        :bank="bank"
        :current-round="currentRound"
        :game-status="gameStatus"
        />
        
        <MonitoringPanel />
        
        <TestPanel />
        
        <DebugPanel 
        v-if="showDebug"
        :game-status="gameStatus"
        :current-player-position="currentPlayerPosition"
        :current-player-id="currentPlayerId"
        :is-my-turn="isMyTurn"
        :active-players-count="activePlayersCount"
        :current-round="currentRound"
        :available-actions="availableActions"
        />

        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

// Components
import GameHeader from './components/GameHeader.vue'
import GameTable from './components/GameTable.vue'
import PlayerControlPanel from './components/PlayerControlPanel.vue'
import ActionPanel from './components/ActionPanel.vue'
import RaiseModal from './components/RaiseModal.vue'
import Notifications from './components/Notifications.vue'
import MonitoringPanel from './components/MonitoringPanel.vue'
import TestPanel from './components/TestPanel.vue'
import DebugPanel from './components/DebugPanel.vue'

const props = defineProps({
  gameId: Number
})

// 🔧 STATE ДЛЯ РЕАЛЬНОГО API
const gameState = ref(null)
const isLoading = ref(false)
const error = ref(null)
const authError = ref(null)
const showRaiseModal = ref(false)
const showDebug = ref(true)
const playerCards = ref({})

// 🔧 REAL-TIME ПОДПИСКА
const setupRealTimeUpdates = () => {
  console.log('🔔 Real-time updates setup for game:', props.gameId)
  // TODO: реализовать Pusher когда API заработает
}

// 🔧 CSRF TOKEN
const ensureCsrfToken = async () => {
  try {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
    console.log('✅ CSRF cookie set')
  } catch (err) {
    console.error('❌ Failed to get CSRF cookie:', err)
  }
}

// 🔧 ЗАГРУЗКА СОСТОЯНИЯ ИГРЫ
const loadGameState = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const user = usePage().props.auth.user
    console.log('🔄 Loading game state for user:', user)

    if (!user) {
      throw new Error('User not authenticated')
    }

    // 🔧 ПЕРВЫЙ ЗАПРОС - получаем CSRF
    await axios.get('/sanctum/csrf-cookie', {
      withCredentials: true
    })

    // 🔧 ВТОРОЙ ЗАПРОС - проверяем аутентификацию
    const authCheck = await axios.get('/api/user', {
      withCredentials: true,
      headers: {
        'Accept': 'application/json'
      }
    })
    console.log('✅ Auth check passed:', authCheck.data)

    // 🔧 ТРЕТИЙ ЗАПРОС - получаем игру
    const response = await axios.get(`/api/seka/${props.gameId}/full-state`, {
      withCredentials: true,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    console.log('✅ Game state loaded:', response.data)
    gameState.value = response.data.game
    
  } catch (err) {
    console.error('❌ API ERROR DETAILS:', {
      status: err.response?.status,
      url: err.config?.url,
      data: err.response?.data,
      stack: err.response?.data?.stack
    })
    
    if (err.response?.status === 500) {
      error.value = 'Ошибка сервера. Проверь логи бэкенда.'
      console.log('🔧 Server error - check Laravel logs')
    }
    
    if (err.response?.status === 401) {
      error.value = 'Ошибка аутентификации. Сессия истекла.'
      // Перенаправляем на логин
      setTimeout(() => {
        window.location.href = '/login'
      }, 2000)
    } else if (err.response?.status === 404) {
      error.value = 'Игра не найдена'
    } else {
      error.value = `Ошибка: ${err.response?.data?.message || err.message}`
    }
  } finally {
    isLoading.value = false
  }
}

// 🔧 COMPUTED НА ОСНОВЕ РЕАЛЬНЫХ ДАННЫХ
const gameStatus = computed(() => gameState.value?.status || 'waiting')
const players = computed(() => gameState.value?.players || [])
const currentPlayerPosition = computed(() => gameState.value?.current_player_position || 0)
const bank = computed(() => gameState.value?.bank || 0)
const currentRound = computed(() => {
  const round = gameState.value?.round || 'waiting'
  // Преобразуем строку в число для совместимости
  if (round === 'waiting') return 0
  if (round === 'bidding') return 1
  if (round === 'active') return 2
  return typeof round === 'number' ? round : 1
})

const currentPlayerId = computed(() => {
  const user = usePage().props.auth.user
  return user?.id || null
})

const isMyTurn = computed(() => {
  if (!gameState.value || !currentPlayerId.value) return false
  const currentPlayer = players.value.find(p => p.position === currentPlayerPosition.value)
  return currentPlayer ? currentPlayer.id === currentPlayerId.value : false
})

const currentPlayerInfo = computed(() => 
  players.value.find(p => p.id === currentPlayerId.value)
)

const currentMaxBet = computed(() => gameState.value?.max_bet || 0)

const activePlayersCount = computed(() => 
  players.value.filter(p => p.status === 'active' || p.is_playing).length
)

const availableActions = computed(() => {
  if (!isMyTurn.value || gameStatus.value !== 'active') return []
  return gameState.value?.current_player_actions || []
})

const minRaise = computed(() => currentMaxBet.value * 2)
const maxRaise = computed(() => currentPlayerInfo.value?.balance || 0)

// 🔧 ДЕЙСТВИЯ ИГРОКА
const takeAction = async (action, betAmount = null) => {
  console.log('🎯 TAKE ACTION CALLED:', { action, betAmount })
  
  try {
    const user = usePage().props.auth.user
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    
    console.log('🔄 Sending action to API...')
    
    const response = await axios.post(`/api/seka/${props.gameId}/action`, {
      player_id: user.id,
      action: action,
      bet_amount: betAmount
    }, {
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
      },
      withCredentials: true
    })
    
    console.log('✅ Action response:', response.data)
    
    // Обновляем состояние после действия
    await loadGameState()
    
  } catch (err) {
    console.error('❌ Action failed:', err)
    error.value = err.response?.data?.message || 'Ошибка выполнения действия'
  }
}

const executeRaise = async (amount) => {
  await takeAction('raise', amount)
  showRaiseModal.value = false
}

const cancelRaise = () => {
  showRaiseModal.value = false
}

const redirectToLogin = () => {
  window.location.href = '/login'
}

// 🔧 LIFECYCLE
onMounted(async () => {
  console.log('🎯 SekaGame mounted - starting game...')
  
  try {
    await ensureCsrfToken()
    await loadGameState()
    setupRealTimeUpdates()
  } catch (err) {
    console.error('❌ Game initialization failed:', err)
  }
})

// 🔧 ДЛЯ СОВМЕСТИМОСТИ С СТАРЫМ КОДОМ (временно)
const initializeGame = () => loadGameState()
const switchPlayer = () => console.log('Switch player - TODO')
const updateGameState = () => loadGameState()

defineExpose({
  gameState,
  loadGameState,
  takeAction
})
</script>

<style scoped>
.auth-error {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
  background: #f8d7da;
  border: 2px solid #dc3545;
  border-radius: 10px;
  padding: 40px;
}

.error-message {
  text-align: center;
  color: #721c24;
}

.btn-login {
  background: #dc3545;
  color: white;
  padding: 12px 24px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
  margin-top: 15px;
}

.btn-login:hover {
  background: #c82333;
}
.seka-game {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
  background: #f8f9fa;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

@media (max-width: 768px) {
  .seka-game {
    padding: 10px;
    gap: 15px;
  }
}
</style>