<template>
  <div class="seka-game" :class="{ 'mobile': isMobile }">

    <!-- Система готовности -->
    <ReadyCheck 
      v-if="gameStatus === 'waiting'"
      :players="players"
      :time-remaining="readyCheck.timeRemaining"
      @player-ready="handlePlayerReady"
      @timeout="handleReadyTimeout"
    />

    <!-- Заголовок игры -->
    <div class="game-header">
      <h1>🎴 SEKA</h1>
      <div class="game-meta">
        <div class="meta-item">Банк: <strong>{{ pot }} 🪙</strong></div>
        <div class="meta-item">Раунд: <strong>{{ currentRound }}</strong></div>
        <div class="meta-item" v-if="gameStatus === 'waiting'">
          Готовы: <strong class="waiting-status">{{ readyPlayersCount }}/6</strong>
        </div>
        <div class="meta-item" v-if="gameStatus === 'active'">
          Ходит: <strong class="current-player">{{ getCurrentPlayer()?.name }}</strong>
        </div>
        <div class="meta-item" v-if="gameStatus === 'active'">
          Игроков: <strong>{{ activePlayersCount }}/6</strong>
        </div>
      </div>
    </div>

    <!-- Игровой стол -->
    <GameTable
      :players="players"
      :player-cards="playerCards"
      :current-player-id="currentPlayerId"
      :bank="pot"
      :current-round="currentRound"
      :game-status="gameStatus"
      :dealer-id="dealerId"
      :is-mobile="isMobile"
      @player-action="handlePlayerAction"
      @player-ready="handlePlayerReady"
    />

    <!-- Модальное окно повышения ставки для ПК -->
    <div v-if="raiseModal && !isMobile" class="modal-overlay desktop-modal">
      <div class="modal-content">
        <h3>
          <span v-if="gameMode === 'dark'">🌑 Игра в Темную</span>
          <span v-else>🎯 Повышение Ставки</span>
        </h3>
        
        <div class="raise-info">
          <div v-if="gameMode === 'dark'" class="dark-benefits">
            <p>🎁 <strong>Привилегии темной игры (1-2 раунды):</strong></p>
            <ul>
              <li>• Ставка рассчитывается в 2 раза меньше</li>
              <li>• Базовая ставка: <strong>{{ raiseAmount }}🪙</strong></li>
              <li>• Ваша ставка: <strong>{{ getAdjustedBet(raiseAmount) }}🪙</strong></li>
              <li>• Экономия: <strong>{{ raiseAmount - getAdjustedBet(raiseAmount) }}🪙</strong></li>
            </ul>
          </div>
          
          <div class="bet-info">
            <p>Текущая максимальная ставка: <strong>{{ currentMaxBet }}🪙</strong></p>
            <p>Минимальное повышение: <strong>{{ minBet }}🪙</strong></p>
            <p>Ваш баланс: <strong>{{ getCurrentPlayer()?.balance }}🪙</strong></p>
          </div>
        </div>
        
        <!-- Ползунок -->
        <div class="slider-container">
          <input 
            type="range" 
            v-model.number="raiseAmount"
            :min="minBet"
            :max="maxBet"
            :step="1"
            class="slider"
          >
          <div class="slider-labels">
            <span>{{ minBet }}</span>
            <span class="current-bet">
              <template v-if="gameMode === 'dark'">
                {{ getAdjustedBet(raiseAmount) }}🪙
                <small>(было {{ raiseAmount }}🪙)</small>
              </template>
              <template v-else>
                {{ raiseAmount }}🪙
              </template>
            </span>
            <span>{{ maxBet }}</span>
          </div>
        </div>
        
        <!-- Цифровой ввод -->
        <div class="number-input-container">
          <label>Сумма ставки:</label>
          <input 
            type="number" 
            v-model.number="raiseAmount"
            :min="minBet"
            :max="maxBet"
            class="number-input"
          >
          <span class="currency">🪙</span>
        </div>
        
        <div class="modal-actions">
          <button @click="confirmRaise" class="confirm-btn">
            <span v-if="gameMode === 'dark'">🌑 Играть в Темную ({{ getAdjustedBet(raiseAmount) }}🪙)</span>
            <span v-else>🎯 Поднять Ставку ({{ raiseAmount }}🪙)</span>
          </button>
          <button @click="cancelRaise" class="cancel-btn">❌ Отмена</button>
        </div>
      </div>
    </div>

    <!-- Модальное окно повышения ставки для мобильных -->
    <div v-if="raiseModal && isMobile" class="mobile-raise-panel">
      <div class="mobile-raise-content">
        <div class="mobile-raise-header">
          <h4>
            <span v-if="gameMode === 'dark'">🌑 Темная</span>
            <span v-else>📈 Повысить</span>
          </h4>
          <button @click="cancelRaise" class="close-btn">✕</button>
        </div>
        
        <div class="mobile-raise-body">
          <div class="mobile-bet-info">
            <div class="info-row">
              <span>Текущая ставка:</span>
              <strong>{{ currentMaxBet }}🪙</strong>
            </div>
            <div class="info-row">
              <span>Ваш баланс:</span>
              <strong>{{ getCurrentPlayer()?.balance }}🪙</strong>
            </div>
            <div v-if="gameMode === 'dark'" class="dark-discount">
              <span>Скидка 50%:</span>
              <strong>{{ getAdjustedBet(raiseAmount) }}🪙</strong>
            </div>
          </div>

          <div class="mobile-slider">
            <input 
              type="range" 
              v-model.number="raiseAmount"
              :min="minBet"
              :max="maxBet"
              :step="1"
              class="slider"
            >
            <div class="slider-value">
              {{ gameMode === 'dark' ? getAdjustedBet(raiseAmount) : raiseAmount }}🪙
            </div>
          </div>

          <div class="quick-buttons">
            <button 
              v-for="amount in quickAmounts" 
              :key="amount"
              @click="raiseAmount = amount"
              class="quick-btn"
              :class="{ active: raiseAmount === amount }"
            >
              +{{ amount }}
            </button>
          </div>
        </div>

        <div class="mobile-raise-actions">
          <button @click="confirmRaise" class="mobile-confirm-btn">
            <span v-if="gameMode === 'dark'">🌑 Подтвердить ({{ getAdjustedBet(raiseAmount) }}🪙)</span>
            <span v-else>📈 Повысить ({{ raiseAmount }}🪙)</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

// 🎯 ИМПОРТ КОМПОЗАБЛОВ
import { useGameState } from './composables/useGameState'
import { useGameActions } from './composables/useGameActions'
import { useGameLogic } from './composables/useGameLogic'

// Компоненты
import GameTable from './components/GameTable.vue'
import ReadyCheck from './components/ReadyCheck.vue'

const props = defineProps({
  gameId: Number
})

// 🎯 ИНИЦИАЛИЗАЦИЯ КОМПОЗАБЛОВ
const { 
  gameState: backendGameState, 
  isLoading, 
  error,
  currentPlayer: backendCurrentPlayer,
  isCurrentPlayerTurn,
  activePlayers: backendActivePlayers,
  readyPlayersCount,
  gameStatus,
  joinGame,
  loadGameState
} = useGameState(props.gameId)

const { 
  performAction,
  markPlayerReady,
  isActionLoading,
  lastError 
} = useGameActions(props.gameId)

const { 
  gameState: logicGameState,
  availableActions,
  updateGameState,
  potAmount,
  currentRound,
  currentMaxBet
} = useGameLogic()

// 🎯 ЛОКАЛЬНОЕ СОСТОЯНИЕ ДЛЯ СОВМЕСТИМОСТИ
const players = reactive([])
const playerCards = reactive({})
const gameMode = ref(null)
const raiseModal = ref(false)
const raiseAmount = ref(0)
const isMobile = ref(false)

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
const pot = computed(() => backendGameState.value?.bank || 0)
const currentPlayerId = computed(() => backendGameState.value?.current_player_id)
const dealerId = computed(() => backendGameState.value?.dealer_id || 1)

const activePlayersCount = computed(() => backendActivePlayers.value?.length || 0)

const minBet = computed(() => currentMaxBet.value + 1)
const maxBet = computed(() => {
  const player = getCurrentPlayer()
  return player ? Math.min(player.balance + (player.currentBet || 0), 500) : 100
})

const quickAmounts = computed(() => {
  const currentMax = currentMaxBet.value
  return [
    currentMax + 10,
    currentMax + 25, 
    currentMax + 50,
    currentMax + 100
  ].filter(amount => amount <= maxBet.value)
})

// 🎯 СИНХРОНИЗАЦИЯ С БЭКЕНДОМ
watch(backendGameState, (newBackendState) => {
  if (newBackendState) {
    console.log('🔄 Syncing backend state')
    updateGameState(newBackendState)
    syncWithLocalState(newBackendState)
  }
})

const syncWithLocalState = (backendState) => {
  if (!backendState) return
  
  // Обновляем игроков из бэкенда
  if (backendState.players_list) {
    players.splice(0, players.length, ...formatPlayersFromBackend(backendState.players_list))
  }
}

const formatPlayersFromBackend = (backendPlayers) => {
  return backendPlayers.map(player => ({
    id: player.id,
    name: player.name || `Player_${player.id}`,
    position: player.position,
    balance: player.balance || player.chips || 1000,
    currentBet: player.current_bet || 0,
    isFolded: player.has_folded || false,
    isDark: player.is_playing_dark || false,
    isReady: player.is_ready || false,
    status: player.status || 'waiting'
  }))
}

// 🎯 ОСНОВНЫЕ МЕТОДЫ
const getCurrentPlayer = () => {
  return players.find(p => p.id === currentPlayerId.value)
}

const handlePlayerAction = async (action, betAmount = null) => {
  try {
    console.log('🎯 Handling action:', action, 'betAmount:', betAmount)
    await performAction(action, betAmount)
  } catch (error) {
    console.error('❌ Action failed:', error)
  }
}

const handlePlayerReady = async (playerId) => {
  try {
    console.log('✅ Marking player ready')
    await markPlayerReady()
  } catch (error) {
    console.error('❌ Ready action failed:', error)
  }
}

// 🎯 СИСТЕМА СТАВОК
const openRaiseModal = () => {
  raiseAmount.value = minBet.value
  raiseModal.value = true
}

const confirmRaise = async () => {
  try {
    const action = gameMode.value === 'dark' ? 'dark' : 'raise'
    await performAction(action, raiseAmount.value)
    raiseModal.value = false
    gameMode.value = null
  } catch (error) {
    console.error('❌ Raise failed:', error)
  }
}

const cancelRaise = () => {
  raiseModal.value = false
  gameMode.value = null
}

const getAdjustedBet = (baseAmount) => {
  if (gameMode.value === 'dark') {
    return Math.floor(baseAmount / 2)
  }
  return baseAmount
}

// 🎯 ТАЙМЕРЫ И СИСТЕМА ГОТОВНОСТИ
const readyCheck = reactive({
  timeRemaining: 30,
  timer: null
})

const handleReadyTimeout = () => {
  console.log('⏰ Ready timeout')
}

// 🎯 АДАПТИВНОСТЬ
const checkDevice = () => {
  isMobile.value = window.innerWidth < 768
}

// 🎯 LIFECYCLE
onMounted(() => {
  checkDevice()
  window.addEventListener('resize', checkDevice)
  loadGameState()
})

onUnmounted(() => {
  window.removeEventListener('resize', checkDevice)
})
</script>

<style scoped>
.seka-game {
  position: relative;
  min-height: 100vh;
  background: linear-gradient(135deg, #0a2f0a 0%, #1a5a1a 100%);
  padding: 20px;
  overflow: hidden;
}

.game-header {
  text-align: center;
  margin-bottom: 20px;
  color: white;
}

.game-header h1 {
  font-size: 2.5rem;
  margin-bottom: 15px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
}

.game-meta {
  display: flex;
  justify-content: center;
  gap: 20px;
  flex-wrap: wrap;
}

.meta-item {
  background: rgba(255, 255, 255, 0.1);
  padding: 8px 16px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  font-size: 1rem;
}

.waiting-status {
  color: #68d391;
}

.current-player {
  color: #fbbf24;
}

/* Модальные окна ставок */
.modal-overlay.desktop-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-overlay.desktop-modal .modal-content {
  background: linear-gradient(135deg, #1a5a1a 0%, #0a2f0a 100%);
  padding: 2rem;
  border-radius: 15px;
  border: 2px solid #38a169;
  color: white;
  min-width: 500px;
  max-width: 600px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
}

.dark-benefits {
  background: rgba(104, 211, 145, 0.1);
  border: 1px solid #68d391;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.bet-info {
  background: rgba(255, 255, 255, 0.05);
  padding: 1rem;
  border-radius: 8px;
  margin: 0.5rem 0;
}

.slider-container {
  margin: 1rem 0;
}

.slider-labels {
  display: flex;
  justify-content: space-between;
  margin-top: 0.5rem;
  color: #d1d5db;
}

.current-bet {
  font-weight: bold;
  color: #fbbf24;
}

.number-input-container {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 1rem 0;
}

.number-input {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid #4a5568;
  border-radius: 8px;
  padding: 8px 12px;
  color: white;
  width: 100px;
}

.modal-actions {
  display: flex;
  gap: 10px;
  margin-top: 1rem;
}

.confirm-btn, .cancel-btn {
  flex: 1;
  padding: 12px;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.2s;
}

.confirm-btn {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.cancel-btn {
  background: #4a5568;
  color: white;
}

/* Мобильная версия модалки */
.mobile-raise-panel {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.95);
  border-top: 3px solid #16a34a;
  z-index: 1000;
  padding: 15px;
}

.mobile-raise-content {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.mobile-raise-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
}

.mobile-bet-info {
  background: rgba(255, 255, 255, 0.1);
  padding: 12px;
  border-radius: 10px;
  color: white;
}

.info-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.dark-discount {
  display: flex;
  justify-content: space-between;
  color: #68d391;
  font-weight: bold;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.slider-value {
  text-align: center;
  font-size: 1.3rem;
  font-weight: bold;
  color: #fbbf24;
  margin-top: 10px;
}

.quick-buttons {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.quick-btn {
  background: #374151;
  color: white;
  border: none;
  padding: 10px;
  border-radius: 8px;
  font-size: 0.9rem;
  cursor: pointer;
}

.quick-btn.active {
  background: #3b82f6;
}

.mobile-confirm-btn {
  width: 100%;
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  padding: 15px;
  border-radius: 10px;
  font-size: 1.1rem;
  font-weight: bold;
  cursor: pointer;
}

@media (max-width: 768px) {
  .seka-game {
    padding: 10px;
  }
  
  .game-header h1 {
    font-size: 2rem;
  }
  
  .game-meta {
    gap: 10px;
  }
  
  .meta-item {
    padding: 6px 12px;
    font-size: 0.9rem;
  }
}
</style>