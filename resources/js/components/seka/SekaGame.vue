<template>
  <div class="seka-game" :class="{ 'mobile': isMobile }">

    <!-- Убираем переключатель режимов -->
    <div class="game-status-indicator">
      <div class="status-badge">🌐 Реальный режим</div>
    </div>

    <!-- 🎯 КОМПОНЕНТЫ ТАЙМЕРОВ -->
    <GameTimers 
      :turn-time-left="turnTimeLeft"
      :ready-time-left="readyTimeLeft"
      :reveal-time-left="revealTimeLeft"
      :turn-progress="turnProgress"
      :ready-progress="readyProgress"
      :is-turn-critical="isTurnTimeCritical"
      :is-ready-critical="isReadyTimeCritical"
      :game-status="gameStatus"
      :current-player-name="currentPlayerName"
    />

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
        <div class="meta-item" v-if="gameStatus === 'waiting'">
          Готовы: <strong class="waiting-status">{{ readyCount }}/6</strong>
          <span v-if="readyTimeLeft > 0" class="timer-badge">
            {{ formatTime(readyTimeLeft) }}
          </span>
        </div>
        <div class="meta-item" v-if="gameStatus === 'active'">
          Ходит: <strong class="current-player">{{ currentPlayerName }}</strong>
          <span v-if="turnTimeLeft > 0" class="timer-badge" :class="{ critical: isTurnTimeCritical }">
            {{ formatTime(turnTimeLeft) }}
          </span>
        </div>
      </div>
      <div class="game-actions-header">
        <button 
          @click="leaveGame" 
          class="leave-game-btn"
          :disabled="isActionLoading"
        >
          🚪 Выйти в лобби
        </button>
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
    <div v-if="shouldShowBettingInfo" class="betting-info-panel">
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

    <!-- ПОКАЗЫВАЕМ ИНФОРМАЦИЮ О ЖДАНИИ -->
    <div v-else class="waiting-info-panel">
      <div class="waiting-stats">
        <div class="stat-item">
          <span class="label">Статус:</span>
          <span class="value">Ожидание игроков</span>
        </div>
        <div class="stat-item">
          <span class="label">Игроков:</span>
          <span class="value">{{ players.length }}/6</span>
        </div>
        <div class="stat-item">
          <span class="label">Базовая ставка:</span>
          <span class="value">{{ baseBet }}🪙</span>
        </div>
      </div>
    </div>

    <!-- 🎯 ПЕРЕХОД ХОДА -->
    <!-- <TurnTransition 
      :is-visible="isTurnTransitioning"
      :previous-player="previousPlayer"
      :current-player="currentPlayer"
      :current-player-actions="currentPlayerActions"
      :turn-time-left="turnTimeLeft"
    /> -->

    <!-- 🎯 REVEAL OVERLAY -->
    <!-- <RevealOverlay 
      :reveal-state="revealState"
      :players="players"
      :reveal-time-left="revealTimeLeft"
    /> -->

    <!-- Игровой стол -->
  <GameTable
    v-if="shouldRenderGameTable && !isLoading && players.length > 0"
    :players="players"
    :player-cards="playerCards"
    :current-player-id="currentPlayerId || 0"
    :bank="pot"
    :current-round="currentRound"
    :game-status="gameStatus"
    :dealer-id="dealerId"
    :is-mobile="isMobile"
    :is-action-loading="isActionLoading"
    @player-action="handlePlayerAction"
    @player-ready="handlePlayerReady"
    @deal-cards="handleDealCards"
  />

  <!-- Loading state -->
  <div v-else class="loading-state">
    <div class="loading-spinner">🎴</div>
    <p>Загрузка игры...</p>
  </div>

    <!-- 🎯 ИНДИКАТОР ТЕКУЩЕГО ХОДА -->
    <div v-if="gameStatus === 'active' && currentPlayer" class="current-turn-indicator">
      <div class="indicator-content">
        <div class="turn-info">
          <span class="turn-icon">🎯</span>
          <span class="turn-text">Сейчас ходит:</span>
          <span class="player-name">{{ currentPlayer.name }}</span>
        </div>
        <div v-if="turnTimeLeft > 0" class="turn-timer" :class="{ critical: isTurnTimeCritical }">
          {{ formatTime(turnTimeLeft) }}
        </div>
      </div>
    </div>

    <div v-if="!isUserInGame && gameStatus === 'waiting_for_players'" class="join-game-overlay">
      <div class="join-game-panel">
        <h3>Присоединиться к игре?</h3>
        <p>Вы не участвуете в этой игре</p>
        <button @click="joinCurrentGame" class="join-game-btn">
          🎮 Присоединиться к игре
        </button>
      </div>
    </div>

    Кнопка готовности (показывается когда есть другие игроки)
    <div v-if="canMarkReady" class="ready-check-overlay">
      <div class="ready-check-panel">
        <h3>Готовы начать?</h3>
        <p>В игре уже {{ otherPlayersCount }} игрок(ов). Отметьтесь готовым чтобы начать!</p>
        <button @click="markPlayerReady" class="ready-btn">
          ✅ Готов играть
        </button>
      </div>
    </div>

    <!-- Информация о ожидании других игроков -->
    <!-- <div v-if="isUserInGame && !isMyPlayerReady && otherPlayersCount === 0" class="waiting-overlay">
      <div class="waiting-panel">
        <h3>Ожидаем других игроков...</h3>
        <p>Присоединитесь к игре с другого устройства или пригласите друзей</p>
        <div class="waiting-spinner">🎴</div>
      </div>
    </div> -->

    <!-- Информация о том что игрок готов и ждет других -->
    <div v-if="isMyPlayerReady && readyPlayersCount < 2" class="waiting-ready-overlay">
      <div class="waiting-ready-panel">
        <h3>Вы готовы! 🎯</h3>
        <p>Ожидаем других игроков... Готово: {{ readyPlayersCount }}/2</p>
      </div>
    </div>

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
import { usePage } from '@inertiajs/vue3'

// 🎯 РЕАЛЬНЫЕ КОМПОЗАБЛЫ
import { useGameState } from './composables/useGameState'
import { useGameActions } from './composables/useGameActions'

// Компоненты
import GameTable from './components/GameTable.vue'
import ReadyCheck from './components/ReadyCheck.vue'
import GameTimers from './components/GameTimers.vue'
import RevealOverlay from './components/RevealOverlay.vue'
import TurnTransition from './components/TurnTransition.vue'

const props = defineProps({
  gameId: Number
})

const page = usePage()
const authUser = computed(() => page.props.auth.user)

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

  // 🎯 ТАЙМЕРЫ
  turnTimeLeft,
  readyTimeLeft,
  revealTimeLeft,
  turnProgress,
  readyProgress,
  isTurnTimeCritical,
  isReadyTimeCritical,
  isRevealTimeCritical,

  joinGame,
  loadGameState
} = useGameState(props.gameId)

// 🎯 ВРЕМЕННЫЕ ЗАГЛУШКИ ДЛЯ НЕРЕАЛИЗОВАННЫХ ФУНКЦИЙ
const isTurnTransitioning = ref(false)
const previousPlayer = ref(null)
const currentPlayerActions = ref([])
const revealState = ref({ 
  isActive: false, 
  participants: [], 
  winnerId: null, 
  loserId: null, 
  resolved: false 
})
const resetRevealState = () => {
  revealState.value = { 
    isActive: false, 
    participants: [], 
    winnerId: null, 
    loserId: null, 
    resolved: false 
  }
}

// 🎯 ИНИЦИАЛИЗИРУЕМ ДЕЙСТВИЯ
const { 
  performAction,
  leaveGame,
  isActionLoading,
  lastError,
  lastSuccess,
  clearError,
  clearSuccess
} = useGameActions(props.gameId)

// 🎯 ЛОКАЛЬНЫЕ СОСТОЯНИЯ ДЛЯ UI
const raiseModal = ref(false)
const raiseAmount = ref(0)
const currentActionMode = ref(null) // 'dark' | 'raise'
const isMobile = ref(false)

// SekaGame.vue - ПОЛУЧАЕМ ДАННЫЕ СТОЛА
const tableData = ref(null)

// 🎯 ЗАГРУЖАЕМ ДАННЫЕ СТОЛА ПРИ ЗАХОДЕ В ИГРУ
const loadTableData = async () => {
  try {
    // 🎯 ПРОБУЕМ ПОЛУЧИТЬ ДАННЫЕ СТОЛА ИЗ ЛОББИ
    const response = await fetch('/api/seka/lobby')
    if (response.ok) {
      const data = await response.json()
      if (data.success && data.games) {
        // 🎯 НАХОДИМ НАШ СТОЛ ПО ID
        const currentTable = data.games.find(game => game.id === props.gameId)
        if (currentTable) {
          tableData.value = currentTable
          console.log('🎯 Table data loaded:', currentTable)
        }
      }
    }
  } catch (error) {
    console.error('❌ Failed to load table data:', error)
  }
}

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ИЗ РЕАЛЬНЫХ ДАННЫХ
const gameStatus = computed(() => backendGameStatus.value || 'waiting_for_players')
const currentPlayerId = computed(() => {
  return backendGameState.value?.current_player_id || 0 // 🎯 0 вместо null/undefined
})
const dealerId = computed(() => backendGameState.value?.dealer_id || 1)
// SekaGame.vue - ИСПРАВЛЯЕМ ДАННЫЕ ДЛЯ НЕНАЧАТОЙ ИГРЫ
const baseBet = computed(() => {
  // 🎯 ПРИОРИТЕТЫ: данные стола → бэкенд игры → дефолт
  if (tableData.value?.base_bet) {
    return tableData.value.base_bet // 🎯 5, 10, 25, 50 в зависимости от стола
  }
  
  if (backendGameState.value?.base_bet) {
    return backendGameState.value.base_bet
  }
  
  return 50 // 🎯 Фолбэк
})

const currentMaxBet = computed(() => {
  // 🎯 ЕСЛИ ИГРА НЕ НАЧАЛАСЬ - СТАВОК ЕЩЕ НЕТ
  if (gameStatus.value === 'waiting_for_players') {
    return 0
  }
  return backendGameState.value?.max_bet || 0
})

const pot = computed(() => {
  // 🎯 ЕСЛИ ИГРА НЕ НАЧАЛАСЬ - БАНК ПУСТОЙ
  if (gameStatus.value === 'waiting_for_players') {
    return 0
  }
  return backendGameState.value?.bank || 0
})

const currentRound = computed(() => {
  // 🎯 ЕСЛИ ИГРА НЕ НАЧАЛАСЬ - РАУНДА НЕТ
  if (gameStatus.value === 'waiting_for_players') {
    return 0
  }
  return backendGameState.value?.round || 1
})

const shouldShowBettingInfo = computed(() => {
  // 🎯 ПОКАЗЫВАТЬ ИНФОРМАЦИЮ О СТАВКАХ ТОЛЬКО КОГДА ИГРА НАЧАЛАСЬ
  return gameStatus.value !== 'waiting_for_players'
})


const shouldRenderGameTable = computed(() => {
  const shouldRender = !isLoading.value && players.value.length > 0
  console.log('🎯 [SekaGame] shouldRenderGameTable:', {
    isLoading: isLoading.value,
    playersCount: players.value.length,
    shouldRender,
    players: players.value
  })
  return shouldRender
})

// 🎯 АДАПТИРУЕМ ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ПОД РЕАЛЬНЫЙ API
const players = computed(() => {
  if (!backendGameState.value?.players_list) return []
  
  return backendGameState.value.players_list.map(player => {
    // 🎯 ИСПРАВЛЯЕМ ИМЯ - если это текущий пользователь, используем его имя
    let playerName = player.name
    if (player.id === authUser.value?.id) {
      playerName = authUser.value.name // "Admin" вместо "Player_27"
    }
    
    console.log('🎯 Player name mapping:', {
      backendName: player.name,
      authName: authUser.value?.name,
      finalName: playerName,
      isCurrentUser: player.id === authUser.value?.id
    })
    
    return {
      id: player.id,
      name: playerName, // 🎯 ИСПОЛЬЗУЕМ ИСПРАВЛЕННОЕ ИМЯ
      position: player.position,
      balance: player.balance,
      currentBet: 0,
      isFolded: player.status === 'folded',
      isDark: false,
      isReady: player.is_ready || false,
      status: player.status,
      is_current_player: player.id === authUser.value?.id
    }
  })
})

// 🎯 ОБРАБОТЧИК ВЫХОДА
const handleLeaveGame = async () => {
  try {
    await leaveGame()
    // 🎯 РЕДИРЕКТ В ЛОББИ ПОСЛЕ УСПЕШНОГО ВЫХОДА
    window.location.href = '/lobby'
  } catch (error) {
    console.error('❌ Leave game error:', error)
    // Ошибка уже обработана в композейбле
  }
}

const playerCards = computed(() => {
  const cards = {}
  // 🎯 ИСПРАВЛЕНО: берем из game.players
  // if (backendGameState.value?.game?.players) {
  //   backendGameState.value.game.players.forEach(player => {
  //     if (player.cards) {
  //       cards[player.id] = player.cards.map(card => ({
  //         ...card,
  //         isVisible: card.is_visible || false
  //       }))
  //     }
  //   })
  // }
  return cards
})

const readyCount = computed(() => backendReadyCount.value || 0)
const activePlayersCount = computed(() => backendActivePlayers.value?.length || 0)

const currentPlayer = computed(() => {
  if (!backendGameState.value?.players_list || !authUser.value) {
    return { 
      name: 'Неизвестный', 
      balance: 0, 
      currentBet: 0,
      position: 0
    }
  }
  
  // 🎯 НАХОДИМ ИГРОКА ПО АВТОРИЗАЦИИ
  const player = backendGameState.value.players_list.find(p => p.id === authUser.value.id)
  
  if (player) {
    console.log('🎯 Found current player by auth:', player)
    return {
      name: player.name || `Player_${player.id}`,
      balance: player.balance,
      currentBet: 0,
      position: player.position,
      id: player.id,
      isReady: player.is_ready || false,
      status: player.status
    }
  }
  
  console.log('⚠️ Current player not found in players_list, auth user:', authUser.value)
  return { 
    name: 'Неизвестный', 
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

// 🎯 ОБРАБОТЧИК ДЕЙСТВИЙ ИГРОКА
const handlePlayerAction = async (action, betAmount = null) => {
  console.log('🎯 [SekaGame] Handling player action:', action, 'amount:', betAmount)
  
  try {
    await performAction(action, betAmount)
    // 🎯 WebSocket обновит состояние автоматически
  } catch (error) {
    console.error('❌ Action failed in SekaGame:', error)
    // Ошибка уже сохранена в lastError
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

// SekaGame.vue - ДИАГНОСТИКА СТАВОК
const bettingData = computed(() => {
  return {
    baseBetFromBackend: backendGameState.value?.base_bet,
    currentMaxBet: backendGameState.value?.max_bet,
    bank: backendGameState.value?.bank,
    hasBettingData: !!backendGameState.value?.base_bet
  }
})

watch(bettingData, (newData) => {
  console.log('💰 [SekaGame] Betting data:', newData)
}, { deep: true })

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

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ДЛЯ УСЛОВИЙ
// 🎯 ИСПРАВЛЯЕМ ПРОВЕРКУ ПОЛЬЗОВАТЕЛЯ В ИГРЕ
const isUserInGame = computed(() => {
  if (!authUser.value || !backendGameState.value?.players_list) return false
  
  const userInGame = backendGameState.value.players_list.some(player => player.id === authUser.value.id)
  console.log('🎯 isUserInGame check:', {
    authUserId: authUser.value.id,
    players: backendGameState.value.players_list.map(p => p.id),
    result: userInGame
  })
  
  return userInGame
})

const otherPlayersCount = computed(() => {
  if (!players.value) return 0
  return players.value.filter(player => player.id !== authUser.value?.id).length
})

const canMarkReady = computed(() => {
  return isUserInGame.value && 
         !isMyPlayerReady.value && 
         otherPlayersCount.value >= 1 && // 🎯 Минимум 1 другой игрок
         gameStatus.value === 'waiting_for_players'
})

// 🎯 ИСПРАВЛЯЕМ ГОТОВНОСТЬ ТЕКУЩЕГО ИГРОКА
const isMyPlayerReady = computed(() => {
  const myPlayer = backendGameState.value?.players_list?.find(p => p.id === authUser.value?.id)
  const isReady = myPlayer?.is_ready || false
  console.log('🎯 isMyPlayerReady:', { playerId: authUser.value?.id, isReady })
  return isReady
})

const myPlayer = computed(() => {
  if (!authUser.value || !backendGameState.value?.players_list) return null
  return backendGameState.value.players_list.find(player => player.id === authUser.value.id)
})

// 🎯 ФОРМАТИРОВАНИЕ ВРЕМЕНИ ДЛЯ ОТОБРАЖЕНИЯ
const formatTime = (seconds) => {
  if (seconds <= 0) return '0:00'
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

// В SekaGame.vue - ДОБАВЛЯЕМ ДИАГНОСТИКУ
console.log('🎯 SekaGame mounted with gameId:', props.gameId)
console.log('👤 Auth user:', authUser.value)

// 🎯 ДИАГНОСТИКА КОМПОЗЕЙБЛА
console.log('🎯 useGameState results:', {
  backendGameState: backendGameState.value,
  isLoading: isLoading.value,
  currentPlayer: backendCurrentPlayer.value,
  gameStatus: backendGameStatus.value
})

watch(readyTimeLeft, (newTime, oldTime) => {
  if (oldTime > 0 && newTime === 0) {
    console.log('⏰ Ready timer expired - backend will handle...')
  }
})

// 🎯 ОБРАБОТКА ИСТЕЧЕНИЯ ТАЙМЕРОВ
watch(turnTimeLeft, (newTime, oldTime) => {
  if (oldTime > 0 && newTime === 0) {
    console.log('⏰ Turn timer expired - waiting for backend auto-fold...')
    // Можно показать уведомление
  }
})

// В SekaGame.vue после computed currentPlayer
watch(backendCurrentPlayer, (newPlayer) => {
  console.log('🎯 REAL Current Player from Backend:', newPlayer)
}, { immediate: true })

watch(currentPlayerId, (newId) => {
  console.log('🎯 Current Player ID:', newId)
  console.log('🎯 All Players:', players.value)
}, { immediate: true })

// 🎯 LIFECYCLE
onMounted(async () => {
  checkDevice()
  window.addEventListener('resize', checkDevice)
  
  // 🎯 ПРЯМАЯ ПРОВЕРКА API
  console.log('🎯 Loading game state for ID:', props.gameId)
  // try {
  //   const response = await fetch(`/api/seka/games/${props.gameId}/state`)
  //   console.log('🎯 API Response status:', response.status)
  //   console.log('🎯 API Response ok:', response.ok)
    
  //   if (response.ok) {
  //     const data = await response.json()
  //     console.log('🎯 API Response data:', data)
  //   } else {
  //     console.error('🎯 API Error:', response.status, response.statusText)
  //   }
  // } catch (error) {
  //   console.error('🎯 API Fetch error:', error)
  // }
  
  // 🎯 Затем загружаем через composable
  // loadGameState()

  await loadGameState()

  // 🎯 ДИАГНОСТИКА
  console.log('🎯 Game State Structure:', backendGameState.value)
  console.log('🎯 Players:', players.value)
  console.log('🎯 Current Player:', currentPlayer.value)
  console.log('🎯 Authenticated User:', authUser.value)
  console.log('🎯 Game ID:', props.gameId)
  loadTableData()
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

// 🎯 ОБРАБОТКА ОШИБОК И УСПЕХА ДЕЙСТВИЙ
watch(lastError, (error) => {
  if (error) {
    console.error('❌ Action error detected:', error)
    // Можно показать уведомление пользователю
    setTimeout(() => clearError(), 3000) // Автоочистка через 3 сек
  }
})

watch(lastSuccess, (success) => {
  if (success) {
    console.log('✅ Action success detected:', success)
    // Можно показать уведомление об успехе
    setTimeout(() => clearSuccess(), 2000) // Автоочистка через 2 сек
  }
})

// 🎯 ГЛУБОКАЯ ДИАГНОСТИКА ИГРОКОВ
watch(players, (newPlayers) => {
  console.log('🔍 [SekaGame] Players POSITIONS:')
  newPlayers.forEach(player => {
    console.log(`  Player ${player.id} (${player.name}): position ${player.position}`)
  })
}, { deep: true, immediate: true })

// 🎯 ДИАГНОСТИКА ВСЕХ COMPUTED
watch([pot, currentRound, currentPlayerId, dealerId, currentMaxBet], 
  ([newPot, newRound, newPlayerId, newDealerId, newMaxBet]) => {
    console.log('📊 ALL COMPUTED UPDATED:', {
      pot: newPot,
      round: newRound,
      playerId: newPlayerId,
      dealerId: newDealerId,
      maxBet: newMaxBet
    })
  }, { immediate: true }
)

// Временная глубокая диагностика
watch(backendGameState, (newState) => {
  if (newState?.players_list) {
    console.log('🔍 [SekaGame] Backend players data:', newState.players_list)
    newState.players_list.forEach(player => {
      console.log('  Player from backend:', {
        id: player.id,
        name: player.name,
        authUserId: authUser.value?.id,
        isCurrentUser: player.id === authUser.value?.id
      })
    })
  }
}, { deep: true })

// 🎯 ДИАГНОСТИКА БАЗОВОЙ СТАВКИ
watch(baseBet, (newBet) => {
  console.log('💰 Base bet calculated:', {
    tableData: tableData.value?.base_bet,
    backendData: backendGameState.value?.base_bet,
    finalBet: newBet
  })
})

// 🎯 ПЕРЕДАЕМ ПРОПСЫ В КОМПОНЕНТЫ
const gameTableProps = computed(() => ({
  players: players.value,
  playerCards: playerCards.value,
  currentPlayerId: currentPlayerId.value,
  bank: pot.value,
  currentRound: currentRound.value,
  gameStatus: gameStatus.value,
  dealerId: dealerId.value,
  isMobile: isMobile.value,
  isActionLoading: isActionLoading.value // 🎯 ПЕРЕДАЕМ СОСТОЯНИЕ ЗАГРУЗКИ
}))

</script>

<style scoped>

/* SekaGame.vue - СТИЛИ ДЛЯ ПАНЕЛИ ОЖИДАНИЯ */
.waiting-info-panel {
  background: rgba(0, 0, 0, 0.8);
  border: 2px solid #3b82f6;
  border-radius: 10px;
  padding: 12px;
  margin: 10px auto;
  max-width: 600px;
}

.waiting-stats {
  display: flex;
  justify-content: space-around;
  flex-wrap: wrap;
  gap: 15px;
}

.waiting-stats .stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.waiting-stats .stat-item .label {
  font-size: 0.8rem;
  color: #9ca3af;
}

.waiting-stats .stat-item .value {
  font-size: 1rem;
  font-weight: bold;
  color: #3b82f6;
}

.waiting-overlay,
.waiting-ready-overlay {
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

.waiting-panel,
.waiting-ready-panel {
  background: linear-gradient(135deg, #1a5a1a 0%, #0a2f0a 100%);
  padding: 2rem;
  border-radius: 15px;
  border: 2px solid #fbbf24;
  color: white;
  text-align: center;
  max-width: 400px;
}

.waiting-spinner {
  font-size: 3rem;
  animation: spin 2s linear infinite;
  margin-top: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
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


/* 🎯 СТИЛИ ДЛЯ ТАЙМЕРОВ */
.timer-badge {
  margin-left: 8px;
  padding: 2px 6px;
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid #3b82f6;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: bold;
  color: #3b82f6;
}

.timer-badge.critical {
  background: rgba(239, 68, 68, 0.2);
  border-color: #ef4444;
  color: #ef4444;
  animation: pulse 1s infinite;
}

/* В стили SekaGame.vue */
.game-actions-header {
  position: absolute;
  top: 20px;
  right: 20px;
  z-index: 100;
}

.leave-game-btn {
  background: rgba(220, 38, 38, 0.8);
  color: white;
  border: 1px solid rgba(220, 38, 38, 0.5);
  padding: 10px 16px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
  transition: all 0.3s ease;
}

.leave-game-btn:hover:not(:disabled) {
  background: rgba(220, 38, 38, 1);
  transform: translateY(-1px);
}

.leave-game-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
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