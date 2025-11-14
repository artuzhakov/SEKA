<template>
  <div class="seka-game" :class="{ 'mobile': isMobile }">

    <!-- Убираем переключатель режимов -->
    <div class="game-status-indicator">
      <div class="status-badge">🌐 Реальный режим</div>
    </div>

    <!-- Система готовности -->
    <ReadyCheck 
      v-if="gameStatus === 'waiting'"
      :players="players"
      :time-remaining="readyTimeRemaining"
      @player-ready="handlePlayerReady"
      @player-cancel-ready="handlePlayerCancelReady"
      @timeout="handleReadyTimeout"
    />

    <!-- Заголовок игры -->
    <div class="game-header">
      <h1>🎴 SEKA</h1>
      <div class="game-meta">
        <div class="meta-item">Банк: <strong>{{ pot }} 🪙</strong></div>
        <div class="meta-item">Раунд: <strong>{{ currentRound }}</strong></div>
        <div class="meta-item">Дилер: <strong>{{ dealerName }}</strong></div>
        <div class="meta-item" v-if="gameStatus === 'waiting'">
          Готовы: <strong class="waiting-status">{{ readyCount }}/6</strong>
        </div>
        <div class="meta-item" v-if="gameStatus === 'active'">
          Ходит: <strong class="current-player">{{ currentPlayerName }}</strong>
        </div>
        <div class="meta-item" v-if="gameStatus === 'active'">
          Игроков: <strong>{{ activePlayersCount }}/6</strong>
        </div>
      </div>
    </div>

    <div v-if="error" class="error-overlay">
      <div class="error-message">
        <h3>❌ Ошибка</h3>
        <p>{{ error }}</p>
        <p class="redirect-info">Перенаправление в лобби...</p>
      </div>
    </div>

    <!-- Панель информации о ставках -->
    <div class="betting-info-panel">
      <div class="betting-stats">
        <div class="stat-item">
          <span class="label">Текущая ставка:</span>
          <span class="value">{{ currentMaxBet }}🪙</span>
        </div>
        <div class="stat-item">
          <span class="label">Базовая ставка:</span>
          <span class="value">{{ baseBet }}🪙</span>
        </div>
        <div class="stat-item">
          <span class="label">Минимальное повышение:</span>
          <span class="value">{{ minBet }}🪙</span>
        </div>
        <div class="stat-item" v-if="gameStatus === 'active'">
          <span class="label">Раунд:</span>
          <span class="value">{{ currentRound }}/3</span>
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
      @deal-cards="handleDealCards"
    />

    <!-- Модальное окно повышения ставки для ПК -->
    <div v-if="raiseModal && !isMobile" class="modal-overlay desktop-modal">
      <div class="modal-content">
        <h3>
          <span v-if="currentActionMode === 'dark'">🌑 Игра в Темную</span>
          <span v-else>🎯 Повышение Ставки</span>
        </h3>
        
        <div class="raise-info">
          <div v-if="currentActionMode === 'dark'" class="dark-benefits">
            <p>🎁 <strong>Привилегии темной игры (1-2 раунды):</strong></p>
            <ul>
              <li>• Ставка рассчитывается в 2 раза меньше</li>
              <li>• Базовая ставка: <strong>{{ raiseAmount }}🪙</strong></li>
              <li>• Ваша ставка: <strong>{{ getAdjustedBet(raiseAmount) }}🪙</strong></li>
              <li>• Экономия: <strong>{{ raiseAmount - getAdjustedBet(raiseAmount) }}🪙</strong></li>
              <li v-if="currentRound >= 3" class="warning">⚠️ В 3 раунде привилегии не действуют</li>
            </ul>
          </div>
          
          <div class="bet-info">
            <p>Текущая максимальная ставка: <strong>{{ currentMaxBet }}🪙</strong></p>
            <p>Минимальное повышение: <strong>{{ minBet }}🪙</strong> (на 1 больше)</p>
            <p>Максимальная ставка: <strong>{{ maxBet }}🪙</strong></p>
            <p>Ваш баланс: <strong>{{ currentPlayer.balance }}🪙</strong></p>
            <p v-if="currentPlayer.currentBet > 0">
              Ваша текущая ставка: <strong>{{ currentPlayer.currentBet }}🪙</strong>
            </p>
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
              <template v-if="currentActionMode === 'dark' && currentRound < 3">
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
        
        <!-- Итоговая информация -->
        <div v-if="currentActionMode === 'dark' && currentRound < 3" class="final-info">
          <p><strong>Итоговая ставка:</strong> {{ getAdjustedBet(raiseAmount) }}🪙</p>
          <p><strong>Экономия:</strong> {{ raiseAmount - getAdjustedBet(raiseAmount) }}🪙</p>
        </div>
        
        <div class="modal-actions">
          <button @click="confirmRaise" class="confirm-btn" :disabled="isActionLoading">
            <span v-if="isActionLoading">⏳ Обработка...</span>
            <span v-else-if="currentActionMode === 'dark'">
              🌑 Играть в Темную ({{ getAdjustedBet(raiseAmount) }}🪙)
            </span>
            <span v-else>🎯 Поднять Ставку ({{ raiseAmount }}🪙)</span>
          </button>
          <button @click="cancelRaise" class="cancel-btn" :disabled="isActionLoading">
            ❌ Отмена
          </button>
        </div>
      </div>
    </div>

    <!-- Модальное окно повышения ставки для мобильных -->
    <div v-if="raiseModal && isMobile" class="mobile-raise-panel">
      <div class="mobile-raise-content">
        <div class="mobile-raise-header">
          <h4>
            <span v-if="currentActionMode === 'dark'">🌑 Темная</span>
            <span v-else>📈 Повысить</span>
          </h4>
          <button @click="cancelRaise" class="close-btn" :disabled="isActionLoading">✕</button>
        </div>
        
        <div class="mobile-raise-body">
          <div class="mobile-bet-info">
            <div class="info-row">
              <span>Текущая ставка:</span>
              <strong>{{ currentMaxBet }}🪙</strong>
            </div>
            <div class="info-row">
              <span>Ваш баланс:</span>
              <strong>{{ currentPlayer.balance }}🪙</strong>
            </div>
            <div v-if="currentActionMode === 'dark' && currentRound < 3" class="dark-discount">
              <span>Скидка 50%:</span>
              <strong>{{ getAdjustedBet(raiseAmount) }}🪙</strong>
            </div>
          </div>

          <!-- Ползунок -->
          <div class="mobile-slider">
            <input 
              type="range" 
              v-model.number="raiseAmount"
              :min="minBet"
              :max="maxBet"
              :step="1"
              class="slider"
              :disabled="isActionLoading"
            >
            <div class="slider-value">
              {{ currentActionMode === 'dark' && currentRound < 3 ? 
                getAdjustedBet(raiseAmount) : raiseAmount }}🪙
            </div>
          </div>

          <!-- Быстрые кнопки -->
          <div class="quick-buttons">
            <button 
              v-for="amount in quickAmounts" 
              :key="amount"
              @click="raiseAmount = amount"
              class="quick-btn"
              :class="{ active: raiseAmount === amount }"
              :disabled="isActionLoading"
            >
              +{{ amount - currentMaxBet }}
            </button>
          </div>
        </div>

        <div class="mobile-raise-actions">
          <button @click="confirmRaise" class="mobile-confirm-btn" :disabled="isActionLoading">
            <span v-if="isActionLoading">⏳ Обработка...</span>
            <span v-else-if="currentActionMode === 'dark'">
              🌑 Подтвердить ({{ getAdjustedBet(raiseAmount) }}🪙)
            </span>
            <span v-else>📈 Повысить ({{ raiseAmount }}🪙)</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Индикатор загрузки -->
    <div v-if="isLoading" class="loading-overlay">
      <div class="loading-spinner">🎴</div>
      <p>Загрузка игры...</p>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

// 🎯 РЕАЛЬНЫЕ КОМПОЗАБЛЫ
import { useGameState } from './composables/useGameState'
import { useGameActions } from './composables/useGameActions'

// Компоненты
import GameTable from './components/GameTable.vue'
import ReadyCheck from './components/ReadyCheck.vue'

const props = defineProps({
  gameId: Number
})

// 🎯 РЕАЛЬНЫЕ ДАННЫЕ ИЗ БЭКЕНДА
const { 
  gameState: backendGameState, 
  isLoading, 
  error,
  currentPlayer: backendCurrentPlayer,
  isCurrentPlayerTurn,
  activePlayers: backendActivePlayers,
  readyPlayersCount: backendReadyCount,
  gameStatus: backendGameStatus,
  joinGame,
  loadGameState
} = useGameState(props.gameId)

const { 
  performAction,
  markPlayerReady,
  isActionLoading,
  lastError 
} = useGameActions(props.gameId)

// 🎯 ЛОКАЛЬНЫЕ СОСТОЯНИЯ ДЛЯ UI
const raiseModal = ref(false)
const raiseAmount = ref(0)
const currentActionMode = ref(null) // 'dark' | 'raise'
const isMobile = ref(false)

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ИЗ РЕАЛЬНЫХ ДАННЫХ
const gameStatus = computed(() => backendGameStatus.value || 'waiting')
const pot = computed(() => backendGameState.value?.bank || 0)
const currentRound = computed(() => backendGameState.value?.current_round || 1)
const currentPlayerId = computed(() => backendGameState.value?.current_player_id)
const dealerId = computed(() => backendGameState.value?.dealer_id || 1)
const currentMaxBet = computed(() => backendGameState.value?.current_max_bet || 0)
const baseBet = computed(() => backendGameState.value?.base_bet || 50)

const players = computed(() => {
  if (!backendGameState.value?.players) return []
  return backendGameState.value.players.map(player => ({
    id: player.id,
    name: player.name,
    position: player.position,
    balance: player.balance,
    currentBet: player.current_bet,
    isFolded: player.status === 'folded',
    isDark: player.status === 'dark',
    isReady: player.is_ready,
    status: player.status
  }))
})

const playerCards = computed(() => {
  const cards = {}
  if (backendGameState.value?.players) {
    backendGameState.value.players.forEach(player => {
      if (player.cards) {
        cards[player.id] = player.cards.map(card => ({
          ...card,
          isVisible: card.is_visible || false
        }))
      }
    })
  }
  return cards
})

const readyCount = computed(() => backendReadyCount.value || 0)
const activePlayersCount = computed(() => backendActivePlayers.value?.length || 0)

const currentPlayer = computed(() => {
  return backendCurrentPlayer.value || { 
    name: 'Игрок', 
    balance: 0, 
    currentBet: 0,
    position: 0
  }
})

const dealerName = computed(() => {
  const dealer = players.value.find(p => p.id === dealerId.value)
  return dealer?.name || 'Не выбран'
})

const currentPlayerName = computed(() => {
  return currentPlayer.value?.name || 'Без имени'
})

const readyTimeRemaining = computed(() => {
  return backendGameState.value?.ready_time_remaining || 30
})

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ДЛЯ СТАВОК
const minBet = computed(() => {
  return currentMaxBet.value + 1
})

const maxBet = computed(() => {
  return Math.min(currentPlayer.value.balance + currentPlayer.value.currentBet, 500)
})

const quickAmounts = computed(() => {
  const amounts = [
    currentMaxBet.value + 10,
    currentMaxBet.value + 25, 
    currentMaxBet.value + 50,
    currentMaxBet.value + 100
  ]
  return amounts.filter(amount => amount <= maxBet.value)
})

// 🎯 ОСНОВНЫЕ МЕТОДЫ
const handlePlayerAction = async (action, betAmount = null) => {
  console.log('🎯 Real action:', action, 'betAmount:', betAmount)
  
  if (action === 'raise' || action === 'dark') {
    currentActionMode.value = action
    openRaiseModal()
  } else {
    await performAction(action, betAmount)
  }
}

const handlePlayerReady = async (playerId) => {
  console.log('✅ Marking player ready')
  await markPlayerReady()
}

const handlePlayerCancelReady = async (playerId) => {
  // 🎯 В реальном режиме отмена готовности может быть отдельным действием
  console.log('❌ Cancel ready - need backend support')
}

const handleReadyTimeout = () => {
  console.log('⏰ Ready timeout - handled by backend')
}

const handleDealCards = () => {
  // 🎯 В реальном режиме раздача карт инициируется бэкендом
  console.log('🎯 Card dealing handled by backend')
}

// 🎯 МЕТОДЫ СТАВОК
const openRaiseModal = () => {
  raiseAmount.value = minBet.value
  raiseModal.value = true
  
  console.log('🎯 Open raise modal:', {
    mode: currentActionMode.value,
    min: minBet.value,
    max: maxBet.value,
    currentMaxBet: currentMaxBet.value
  })
}

const confirmRaise = async () => {
  try {
    const action = currentActionMode.value === 'dark' ? 'dark' : 'raise'
    await performAction(action, raiseAmount.value)
    raiseModal.value = false
    currentActionMode.value = null
  } catch (error) {
    console.error('❌ Raise action failed:', error)
  }
}

const cancelRaise = () => {
  raiseModal.value = false
  currentActionMode.value = null
}

const getAdjustedBet = (baseAmount) => {
  if (currentActionMode.value === 'dark' && currentRound.value < 3) {
    return Math.floor(baseAmount / 2)
  }
  return baseAmount
}

// 🎯 LIFECYCLE
onMounted(() => {
  checkDevice()
  window.addEventListener('resize', checkDevice)
  
  // 🎯 Загружаем состояние игры при монтировании
  loadGameState()
})

onUnmounted(() => {
  window.removeEventListener('resize', checkDevice)
})

// 🎯 ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
const checkDevice = () => {
  isMobile.value = window.innerWidth < 768
}

// 🎯 ОБРАБОТКА ОШИБОК
watch(error, (newError) => {
  if (newError) {
    console.error('❌ Game error:', newError)
    // Можно показать уведомление пользователю
  }
})

watch(lastError, (newError) => {
  if (newError) {
    console.error('❌ Action error:', newError)
    // Можно показать уведомление пользователю
  }
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

.game-status-indicator {
  display: flex;
  justify-content: center;
  margin-bottom: 15px;
}

.status-badge {
  background: rgba(56, 161, 105, 0.3);
  border: 2px solid #38a169;
  color: white;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: bold;
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

/* Стили модальных окон (сохранены из предыдущих версий) */
.dark-benefits {
  background: rgba(104, 211, 145, 0.1);
  border: 1px solid #68d391;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.dark-benefits ul {
  margin: 0.5rem 0;
  padding-left: 1.5rem;
}

.dark-benefits li {
  margin: 0.25rem 0;
  font-size: 0.9rem;
  color: #68d391;
}

.bet-info {
  background: rgba(255, 255, 255, 0.05);
  padding: 1rem;
  border-radius: 8px;
  margin: 0.5rem 0;
}

.bet-info p {
  margin: 0.25rem 0;
  font-size: 0.9rem;
}

.final-info {
  background: rgba(104, 211, 145, 0.2);
  border: 1px solid #68d391;
  border-radius: 8px;
  padding: 1rem;
  margin: 1rem 0;
  text-align: center;
}

.warning {
  color: #fbbf24;
  font-weight: bold;
}

/* Стили для десктопного модального окна */
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

/* Стили для мобильной панели повышения */
.mobile-raise-panel {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.95);
  border-top: 3px solid #16a34a;
  z-index: 1000;
  padding: 15px;
  max-height: 70vh;
  overflow-y: auto;
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

.mobile-raise-header h4 {
  margin: 0;
  font-size: 1.2rem;
}

.close-btn {
  background: none;
  border: none;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 5px;
}

.close-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

.mobile-slider {
  padding: 10px 0;
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
  transition: all 0.2s;
}

.quick-btn.active {
  background: #3b82f6;
  transform: scale(0.95);
}

.quick-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.mobile-raise-actions {
  margin-top: 10px;
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

.mobile-confirm-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.slider-labels {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.5rem;
  font-size: 0.9rem;
  color: #d1d5db;
}

.slider-labels span:not(.current-bet) {
  flex: 1;
  text-align: center;
}

.current-bet {
  flex: 2;
  text-align: center;
  font-size: 1.2rem;
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

.confirm-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.cancel-btn {
  background: #4a5568;
  color: white;
}

.cancel-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.betting-info-panel {
  background: rgba(0, 0, 0, 0.8);
  border: 2px solid #fbbf24;
  border-radius: 10px;
  padding: 12px;
  margin: 10px auto;
  max-width: 600px;
}

.betting-stats {
  display: flex;
  justify-content: space-around;
  flex-wrap: wrap;
  gap: 15px;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.stat-item .label {
  font-size: 0.8rem;
  color: #9ca3af;
}

.stat-item .value {
  font-size: 1rem;
  font-weight: bold;
  color: #fbbf24;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  color: white;
}

.loading-spinner {
  font-size: 4rem;
  animation: spin 2s linear infinite;
  margin-bottom: 20px;
}

.error-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 3000;
}

.error-message {
  background: #dc2626;
  color: white;
  padding: 2rem;
  border-radius: 10px;
  text-align: center;
  max-width: 400px;
}

.redirect-info {
  font-size: 0.9rem;
  opacity: 0.8;
  margin-top: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
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