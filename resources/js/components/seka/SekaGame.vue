<template>
  <div class="seka-game" :class="{ 'mobile': isMobile }">

    <!-- Переключатель режимов (только в разработке) -->
    <div v-if="isDevelopment" class="mode-switcher">
      <button 
        @click="switchMode('demo')" 
        :class="{ active: currentMode === 'demo' }"
        class="mode-btn"
      >
        🎮 Демо-режим
      </button>
      <button 
        @click="switchMode('real')" 
        :class="{ active: currentMode === 'real' }"
        class="mode-btn"
      >
        🌐 Реальный режим
      </button>
    </div>

    <!-- Система готовности -->
    <ReadyCheck 
      v-if="gameState.status === 'waiting'"
      :players="players"
      :time-remaining="readyCheck.timeRemaining"
      @player-ready="handlePlayerReady"
      @player-cancel-ready="handlePlayerCancelReady"
      @timeout="handleReadyTimeout"
    />

    <div class="debug-controls" v-if="currentMode === 'demo' && isDevelopment">
      <button @click="clearSave" class="debug-btn">🗑️ Очистить сохранение</button>
    </div>

    <!-- Заголовок игры -->
    <div class="game-header">
      <h1>🎴 SEKA</h1>
      <div class="game-meta">
        <div class="meta-item">Банк: <strong>{{ pot }} 🪙</strong></div>
        <div class="meta-item">Раунд: <strong>{{ currentRound }}</strong></div>
        <div class="meta-item">Дилер: <strong>{{ getDealerName() }}</strong></div>
        <div class="meta-item" v-if="gameState.status === 'waiting'">
          Готовы: <strong class="waiting-status">{{ readyCount }}/6</strong>
          <div class="timer-display">⏱️ {{ readyCheck.timeRemaining }}с</div>
        </div>
        <div class="meta-item" v-if="gameState.status === 'active'">
          Ходит: <strong class="current-player">{{ getCurrentPlayerName()}}</strong>
        </div>
        <div class="meta-item" v-if="gameState.status === 'active'">
          Игроков: <strong>{{ activePlayersCount }}/6</strong>
        </div>
        <div class="meta-item mode-indicator">
          <span v-if="currentMode === 'demo'">🎮 Демо</span>
          <span v-else>🌐 Режим</span>
        </div>
      </div>
    </div>

    <!-- После game-header -->
    <div class="betting-info-panel">
      <div class="betting-stats">
        <div class="stat-item">
          <span class="label">Текущая ставка:</span>
          <span class="value">{{ getCurrentBet() }}🪙</span>
        </div>
        <div class="stat-item">
          <span class="label">Базовая ставка:</span>
          <span class="value">{{ gameState.baseBet }}🪙</span>
        </div>
        <div class="stat-item">
          <span class="label">Минимальное повышение:</span>
          <span class="value">{{ minBet }}🪙</span>
        </div>
        <div class="stat-item" v-if="gameState.status === 'active'">
          <span class="label">Раунд:</span>
          <span class="value">{{ gameState.currentRound }}/3</span>
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
      :game-status="gameState.status"
      :dealer-id="dealerId"
      :is-mobile="isMobile"
      @player-action="handlePlayerAction"
      @player-ready="handlePlayerReady"
      @deal-cards="handleDealCards"
    />

    <!-- Дебаг панель -->
    <DebugPanel 
      v-if="showDebug && currentMode === 'demo'" 
      :game-state="gameState"
      @test-action="handleTestAction"
    />

    <!-- Модальное окно повышения ставки для ПК -->
    <div v-if="raiseModal && !isMobile" class="modal-overlay desktop-modal">
      <div class="modal-content">
        <h3>
          <span v-if="gameMode === 'dark'">🌑 Игра в Темную</span>
          <span v-else-if="gameMode === 'open'">👁️ Открытие Карт</span>
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
              <li v-if="gameState.currentRound >= 3" class="warning">⚠️ В 3 раунде привилегии не действуют</li>
            </ul>
          </div>
          
          <div class="bet-info">
            <p>Текущая максимальная ставка: <strong>{{ getCurrentBet() }}🪙</strong></p>
            <p>Минимальное повышение: <strong>{{ minBet }}🪙</strong> (на 1 больше)</p>
            <p>Максимальная ставка: <strong>{{ maxBet }}🪙</strong></p>
            <p>Ваш баланс: <strong>{{ getCurrentPlayer().balance }}🪙</strong></p>
            <p v-if="getCurrentPlayer().currentBet > 0">
              Ваша текущая ставка: <strong>{{ getCurrentPlayer().currentBet }}🪙</strong>
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
              <template v-if="gameMode === 'dark' && gameState.currentRound < 3">
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
        <div v-if="gameMode === 'dark' && gameState.currentRound < 3" class="final-info">
          <p><strong>Итоговая ставка:</strong> {{ getAdjustedBet(raiseAmount) }}🪙</p>
          <p><strong>Экономия:</strong> {{ raiseAmount - getAdjustedBet(raiseAmount) }}🪙</p>
        </div>
        
        <div class="modal-actions">
          <button @click="confirmRaise" class="confirm-btn">
            <span v-if="gameMode === 'dark'">🌑 Играть в Темную ({{ getAdjustedBet(raiseAmount) }}🪙)</span>
            <span v-else-if="gameMode === 'open'">👁️ Открыть Карты ({{ raiseAmount }}🪙)</span>
            <span v-else>🎯 Поднять Ставку ({{ raiseAmount }}🪙)</span>
          </button>
          <button @click="cancelRaise" class="cancel-btn">
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
            <span v-if="gameMode === 'dark'">🌑 Темная</span>
            <span v-else-if="gameMode === 'open'">👁️ Открыть</span>
            <span v-else>📈 Повысить</span>
          </h4>
          <button @click="cancelRaise" class="close-btn">✕</button>
        </div>
        
        <div class="mobile-raise-body">
          <!-- Упрощенная версия для мобильных -->
          <div class="mobile-bet-info">
            <div class="info-row">
              <span>Текущая ставка:</span>
              <strong>{{ getCurrentBet() }}🪙</strong>
            </div>
            <div class="info-row">
              <span>Ваш баланс:</span>
              <strong>{{ getCurrentPlayer().balance }}🪙</strong>
            </div>
            <div v-if="gameMode === 'dark' && gameState.currentRound < 3" class="dark-discount">
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
            >
            <div class="slider-value">
              {{ gameMode === 'dark' && gameState.currentRound < 3 ? 
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
            >
              +{{ amount }}
            </button>
          </div>
        </div>

        <div class="mobile-raise-actions">
          <button @click="confirmRaise" class="mobile-confirm-btn">
            <span v-if="gameMode === 'dark'">🌑 Подтвердить ({{ getAdjustedBet(raiseAmount) }}🪙)</span>
            <span v-else-if="gameMode === 'open'">👁️ Открыть ({{ raiseAmount }}🪙)</span>
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

// 🎯 ИМПОРТ КОМПОЗАБЛОВ ДЛЯ РЕАЛЬНОГО РЕЖИМА
import { useGameState } from './composables/useGameState'
import { useGameActions } from './composables/useGameActions'
import { useGameLogic } from './composables/useGameLogic'

// Компоненты
import GameTable from './components/GameTable.vue'
import DebugPanel from './components/DebugPanel.vue'
import ReadyCheck from './components/ReadyCheck.vue'

const props = defineProps({
  gameId: Number
})

// 🎯 РЕЖИМЫ РАБОТЫ
const currentMode = ref('demo') // 'demo' | 'real'
const isDevelopment = import.meta.env.DEV

// 🎯 КОМПОЗАБЛЫ ДЛЯ РЕАЛЬНОГО РЕЖИМА
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
  loadGameState: loadBackendState
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
  currentRound: logicCurrentRound,
  currentMaxBet: logicCurrentMaxBet
} = useGameLogic()

// 🎯 ДАННЫЕ ДЕМО-РЕЖИМА
const players = reactive([])
const playerCards = reactive({})
const gameState = reactive({
  pot: 0,
  currentRound: 1,
  currentPlayerId: 1,
  dealerId: 1,
  baseBet: 50,
  status: 'waiting'
})

const readyCheck = reactive({
  timeRemaining: 10,
  timer: null,
  canStart: false
})

const gameMode = ref(null)
const showDebug = ref(false)
const isMobile = ref(false)
const windowWidth = ref(0)

// 🎯 ОБЩИЕ ПЕРЕМЕННЫЕ
const raiseModal = ref(false)
const raiseAmount = ref(0)

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА (адаптивные к режиму)
const pot = computed(() => currentMode.value === 'demo' ? gameState.pot : backendGameState.value?.bank || 0)
const currentRound = computed(() => currentMode.value === 'demo' ? gameState.currentRound : logicCurrentRound.value || 1)
const currentPlayerId = computed(() => currentMode.value === 'demo' ? gameState.currentPlayerId : backendGameState.value?.current_player_id)
const dealerId = computed(() => currentMode.value === 'demo' ? gameState.dealerId : backendGameState.value?.dealer_id || 1)

const readyCount = computed(() => {
  if (currentMode.value === 'demo') {
    return players.filter(p => p.isReady && p.id).length
  } else {
    return backendReadyCount.value || 0
  }
})

const activePlayersCount = computed(() => {
  if (currentMode.value === 'demo') {
    return players.filter(p => p.id && !p.isFolded).length
  } else {
    return backendActivePlayers.value?.length || 0
  }
})

const minBet = computed(() => {
  const currentMax = getCurrentBet()
  const player = getCurrentPlayer()
  
  if (gameMode.value === 'dark' && gameState.currentRound < 3) {
    // 🎯 ТЕМНАЯ ИГРА: минимальная ВИДИМАЯ ставка = текущей максимальной + 1
    const minVisibleBet = currentMax + 1
    const minRealBet = minVisibleBet
    
    console.log('🎯 minBet для темной игры:', {
      currentMax: currentMax,
      minVisibleBet: minVisibleBet,
      minRealBet: minRealBet
    })
    
    return minRealBet
  }
  
  // 🎯 ОБЫЧНАЯ ИГРА: текущая + 1
  return currentMax + 1
})

const maxBet = computed(() => {
  const player = getCurrentPlayer()
  return player ? Math.min(player.balance + player.currentBet, 500) : 0
})

const quickAmounts = computed(() => {
  const currentMax = getCurrentBet()
  return [
    currentMax + 10,
    currentMax + 25, 
    currentMax + 50,
    currentMax + 100
  ].filter(amount => amount <= maxBet.value)
})

// 🎯 МЕТОДЫ ДЕМО-РЕЖИМА
const createTestCards = () => {
  const suits = ['♥', '♦', '♣', '♠']
  const ranks = ['10', 'J', 'Q', 'K', 'A']
  
  return Array.from({ length: 3 }, (_, index) => ({
    id: `card-${index + 1}`,
    rank: ranks[Math.floor(Math.random() * ranks.length)],
    suit: suits[Math.floor(Math.random() * suits.length)],
    isVisible: false,
    isJoker: false
  }))
}

const initializeDemoPlayers = () => {
  players.splice(0, players.length, ...[
    { 
      id: 1, 
      name: 'Вы', 
      balance: 1000, 
      currentBet: 0, 
      isFolded: false, 
      isDark: false, 
      isReady: false,
      readyTimeRemaining: 30,
      position: 1
    },
    { 
      id: 2, 
      name: 'Алексей', 
      balance: 1000, 
      currentBet: 0, 
      isFolded: false, 
      isDark: false, 
      isReady: false,
      readyTimeRemaining: 30,
      position: 2
    },
    { 
      id: 3, 
      name: 'Мария', 
      balance: 1000, 
      currentBet: 0, 
      isFolded: false, 
      isDark: false, 
      isReady: false,
      readyTimeRemaining: 15,
      position: 3
    },
    { 
      id: 4, 
      name: 'Дмитрий', 
      balance: 1000, 
      currentBet: 0, 
      isFolded: false, 
      isDark: false, 
      isReady: false,
      readyTimeRemaining: 30,
      position: 4
    },
    { 
      id: 5, 
      name: 'Светлана', 
      balance: 1000, 
      currentBet: 0, 
      isFolded: false, 
      isDark: false, 
      isReady: false,
      readyTimeRemaining: 30,
      position: 5
    },
    { 
      id: 6, 
      name: 'Игорь', 
      balance: 1000, 
      currentBet: 0, 
      isFolded: false, 
      isDark: false, 
      isReady: false,
      readyTimeRemaining: 30,
      position: 6
    }
  ])
}

// 🎯 ОСНОВНЫЕ МЕТОДЫ (адаптивные к режиму)
const getCurrentPlayer = () => {
  if (currentMode.value === 'demo') {
    return players.find(p => p.id === currentPlayerId.value) || { name: 'Неизвестно', balance: 0, currentBet: 0 }
  } else {
    return backendCurrentPlayer.value || { name: 'Игрок', balance: 0, currentBet: 0 }
  }
}

const getDealer = () => {
  if (currentMode.value === 'demo') {
    return players.find(p => p.id === dealerId.value) || players[0]
  } else {
    return backendActivePlayers.value?.find(p => p.id === dealerId.value) || { name: 'Дилер' }
  }
}

const getCurrentBet = () => {
  if (currentMode.value === 'demo') {
    // 🎯 Для темной игры возвращаем ВИДИМУЮ максимальную ставку
    const maxPlayerBet = Math.max(...players.map(p => {
      // Темнящий игрок - его ставка уже видимая (не нужно умножать)
      return p.currentBet
    }))
    const currentBet = Math.max(maxPlayerBet, gameState.baseBet)
    
    console.log('🎯 [getCurrentBet] РАСЧЕТ:', {
      maxPlayerBet: maxPlayerBet,
      baseBet: gameState.baseBet,
      result: currentBet,
      players: players.map(p => ({ 
        name: p.name, 
        visibleBet: p.currentBet, 
        isDark: p.isDark
      }))
    })
    
    return currentBet
  } else {
    return logicCurrentMaxBet.value || 0
  }
}

const handlePlayerAction = (action, betAmount = null) => {
  console.log('🎯 Action received:', action, 'Mode:', currentMode.value)
  
  if (currentMode.value === 'demo') {
    handleDemoAction(action, betAmount)
  } else {
    handleRealAction(action, betAmount)
  }
}

const handlePlayerReady = (playerId) => {
  if (currentMode.value === 'demo') {
    handleDemoPlayerReady(playerId)
  } else {
    handleRealPlayerReady()
  }
}

// 🎯 ДЕМО-РЕЖИМ МЕТОДЫ
const handleDemoPlayerReady = (playerId) => {
  console.log('🎯 [Demo] handlePlayerReady CALLED with playerId:', playerId)
  
  const player = players.find(p => p.id === playerId)
  if (!player || gameState.status !== 'waiting') return
  
  player.isReady = !player.isReady
  console.log('✅ [Demo] Player state updated:', {
    name: player.name,
    isReady: player.isReady
  })
  
  if (readyCount.value >= 2 && !readyCheck.canStart) {
    console.log('🚀 [Demo] 2+ players ready, starting countdown...')
    readyCheck.canStart = true
    
    setTimeout(() => {
      if (gameState.status === 'waiting' && readyCount.value >= 2) {
        console.log('⏰ [Demo] Auto-start timer expired, starting game!')
        startDemoGame()
      }
    }, 10000)
  }
}

const handleDemoAction = (action) => {
  console.log('🎯 [Demo] handlePlayerAction called:', action)
  
  if (gameState.status === 'active') {
    takeDemoAction(action)
  } else {
    console.log('⚠️ [Demo] Action ignored - game not active')
  }
}

const takeDemoAction = async (action) => {
  console.log('🎯 [Demo] Действие:', action, 'Игрок:', getCurrentPlayer().name)
  
  const player = players.find(p => p.id === currentPlayerId.value)
  if (!player) return

  console.log('🔍 [BEFORE] hasActed состояние ДО действия:', {
    player: player.name,
    hasActed: player.hasActed,
    currentBet: player.currentBet
  })

  // 🎯 ВАЖНО: Устанавливаем флаг ДО выполнения действия
  player.lastAction = action
  player.hasActed = true // ← ТОЛЬКО ЗДЕСЬ ОТМЕЧАЕМ ВЫПОЛНЕННЫЙ ХОД

  console.log('🔍 [AFTER] hasActed состояние ПОСЛЕ действия:', {
    player: player.name,
    hasActed: player.hasActed,
    currentBet: player.currentBet
  })

  switch(action) {
    case 'check':
      const checkCurrentMaxBet = getCurrentBet()
      
      console.log('💰 CHECK проверка:', {
        player: player.name,
        currentBet: player.currentBet,
        maxBet: checkCurrentMaxBet,
        balance: player.balance
      })
      
      if (checkCurrentMaxBet === 0) {
        // 🎯 Если ставок нет - вносим базовую ставку (ПОЛНАЯ ПЕРЕЗАПИСЬ)
        if (player.balance >= gameState.baseBet) {
          const previousBet = player.currentBet
          const oldBalance = player.balance
          const oldPot = gameState.pot
          
          // Возвращаем если что-то было
          player.balance += previousBet
          
          // Ставим заново
          player.currentBet = gameState.baseBet
          player.balance -= gameState.baseBet
          gameState.pot = gameState.pot - previousBet + gameState.baseBet
          
          console.log('✅ CHECK: Внесена базовая ставка (ПОЛНАЯ ПЕРЕЗАПИСЬ)', {
            player: player.name,
            previousBetReturned: previousBet,
            baseBet: gameState.baseBet,
            oldBalance: oldBalance,
            newBalance: player.balance,
            oldPot: oldPot,
            newPot: gameState.pot
          })
          
          console.log('✅ CHECK выполнен, передаем ход')
          passToNextPlayer()
          checkForRoundEnd()
        } else {
          console.log('❌ Недостаточно средств для базовой ставки')
        }
      } else if (player.currentBet === checkCurrentMaxBet) {
        // 🎯 Если ставка уже равна текущей максимальной - просто передаем ход
        console.log('✅ CHECK: Ставка уже равна, передача хода')
        console.log('✅ CHECK выполнен, передаем ход')
        passToNextPlayer()
        checkForRoundEnd()
      } else {
        // 🎯 Если есть ставки и наша ставка меньше - нельзя CHECK, нужно CALL
        console.log('❌ Нельзя CHECK при наличии ставок, используйте CALL')
      }
      break

    case 'call':
      const callCurrentMaxBet = getCurrentBet()
      
      console.log('💰 CALL расчет:', {
        player: player.name,
        currentBet: player.currentBet,
        maxBet: callCurrentMaxBet,
        balance: player.balance
      })
      
      if (player.isDark && gameState.currentRound < 3) {
        // 🎯 ТЕМНЫЙ CALL - ПРИВИЛЕГИЯ 1-2 РАУНДЫ
        // Игрок платит ПОЛОВИНУ от ТЕКУЩЕЙ МАКСИМАЛЬНОЙ СТАВКИ
        const playerPaidAmount = Math.floor(callCurrentMaxBet / 2)  // Половина от текущей ставки
        const bankReceivedAmount = callCurrentMaxBet               // Банк получает полную сумму
        
        console.log('💰 DARK CALL расчет:', {
          currentBet: player.currentBet,
          maxBet: callCurrentMaxBet,
          playerPaid: playerPaidAmount,
          bankReceived: bankReceivedAmount
        })
        
        if (player.balance >= playerPaidAmount) {
          const oldBalance = player.balance
          const oldPot = gameState.pot
          
          player.currentBet = callCurrentMaxBet
          player.balance -= playerPaidAmount
          gameState.pot += bankReceivedAmount
          
          console.log('✅ DARK CALL: Поддержка ставки', {
            player: player.name,
            playerPaid: playerPaidAmount,
            bankReceived: bankReceivedAmount,
            newBet: player.currentBet,
            oldBalance: oldBalance,
            newBalance: player.balance,
            oldPot: oldPot,
            newPot: gameState.pot
          })
          
          console.log('✅ CALL выполнен, передаем ход')
          passToNextPlayer()
          checkForRoundEnd()
        } else {
          console.log('❌ Недостаточно средств для поддержки ставки')
        }
      } else {
        // 🎯 ОБЫЧНЫЙ CALL ИЛИ ТЕМНЫЙ В 3 РАУНДЕ
        const difference = callCurrentMaxBet - player.currentBet
        const playerPaidAmount = difference     // Игрок платит полную разницу
        const bankReceivedAmount = difference   // Банк получает полную разницу
        
        if (player.balance >= playerPaidAmount) {
          const oldBalance = player.balance
          const oldPot = gameState.pot
          
          player.currentBet = callCurrentMaxBet
          player.balance -= playerPaidAmount
          gameState.pot += bankReceivedAmount
          
          console.log('✅ CALL: Поддержка ставки', {
            player: player.name,
            difference: difference,
            playerPaid: playerPaidAmount,
            bankReceived: bankReceivedAmount,
            newBet: player.currentBet,
            oldBalance: oldBalance,
            newBalance: player.balance,
            oldPot: oldPot,
            newPot: gameState.pot
          })
          
          console.log('✅ CALL выполнен, передаем ход')
          passToNextPlayer()
          checkForRoundEnd()
        } else {
          console.log('❌ Недостаточно средств для поддержки ставки')
        }
      }
      break

    case 'raise':
      gameMode.value = null
      openRaiseModal(player)
      break
      
    case 'fold':
      player.isFolded = true
      player.isDark = false
      if (playerCards[player.id]) {
        playerCards[player.id].forEach(card => card.isVisible = false)
      }
      console.log('✅ Игрок сбросил карты')
      console.log('✅ FOLD выполнен, передаем ход')
      passToNextDemoPlayer()
      checkForDemoRoundEnd()
      break
      
    case 'dark':
      if (gameState.currentRound >= 3) {
        console.log('❌ Темная игра недоступна в 3 раунде')
        return
      }
      gameMode.value = 'dark'
      openRaiseModal(player)
      // 🎯 НЕ сбрасываем hasActed - игрок уже сделал ход выбрав "темную игру"
      console.log('🌑 DARK: Открыто окно темной игры, hasActed сохранен')
      break
      
    case 'open':
      if (playerCards[player.id]) {
        playerCards[player.id].forEach(card => card.isVisible = true)
        
        // Асинхронный подсчет очков с бэкенда
        try {
          const result = await calculateSekaHandPoints(playerCards[player.id])
          console.log(`🎯 Комбинация ${player.name}: ${result.combination} (${result.points} очков)`)
          
          // 🎯 ВАЖНО: Сохраняем результат в данные игрока
          player.points = result.points
          player.combination = result.combination
          
        } catch (error) {
          console.error('❌ Ошибка подсчета очков:', error)
        }
      }
      console.log('👁️ Игрок открыл карты:', player.name)
      // 🎯 НЕ сбрасываем hasActed - игрок уже сделал ход выбрав "открытие карт"
      console.log('👁️ OPEN: Карты открыты, hasActed сохранен')
      break

    default:
      console.log('❌ Неизвестное действие:', action)
      return
  }

  // 🎯 ВАЖНО: Проверяем завершение раунда с задержкой (только для действий где передается ход)
  if (action !== 'dark' && action !== 'open' && action !== 'raise') {
    setTimeout(() => {
      console.log('⏰ Запускаем проверку завершения раунда...')
      checkForRoundEnd()
    }, 500)
  }
}

const passToNextPlayer = () => {
  console.log('🔄 [passToNextPlayer] Начало передачи хода')
  if (currentMode.value === 'demo') {
    passToNextDemoPlayer()
  } else {
    // Для реального режима
    console.log('🔄 Pass to next player in real mode')
  }
}

const checkForRoundEnd = () => {
  console.log('🎯 [checkForRoundEnd] Проверка завершения раунда')
  if (currentMode.value === 'demo') {
    checkForDemoRoundEnd()
  } else {
    // Для реального режима
    console.log('🎯 [checkForRoundEnd] Режим реальной игры')
  }
}

const startDemoGame = () => {
  if (readyCount.value < 2) {
    console.log('❌ Недостаточно игроков для старта')
    return
  }

  console.log('🚀 Запускаем демо-игру...')
  
  players.forEach(player => {
    if (player.id && !player.isReady) {
      const position = player.position
      player.hasActed = false
      Object.assign(player, {
        id: null,
        name: 'Свободно',
        balance: 0,
        isFolded: true,
        isReady: false,
        isDark: false,
        currentBet: 0,
        position: position,
        lastAction: ''
      })
      
      if (playerCards[player.id]) {
        delete playerCards[player.id]
      }
    }
  })

  const activePlayers = players.filter(p => p.id && !p.isFolded)
  console.log(`🎯 Активных игроков после фильтрации: ${activePlayers.length}`)
  
  if (activePlayers.length < 2) {
    console.log('❌ После фильтрации осталось меньше 2 игроков!')
    return
  }

  gameState.status = 'active'
  
  if (readyCheck.timer) {
    clearInterval(readyCheck.timer)
  }
  
  localStorage.removeItem('sekaGameState')
  
  selectRandomDemoDealer()
  collectDemoBaseBets()
  dealUniqueCards()
  
  // 🎯 ВАЖНО: Установить первого игрока после дилера
  const dealerIndex = activePlayers.findIndex(p => p.id === gameState.dealerId)
  
  console.log('🎯 Поиск первого игрока:', {
    dealerId: gameState.dealerId,
    dealerIndex: dealerIndex,
    activePlayers: activePlayers.map(p => ({ name: p.name, id: p.id, position: p.position }))
  })
  
  if (dealerIndex === -1) {
    console.log('❌ Дилер не найден среди активных игроков')
    // Выбираем случайного игрока как запасной вариант
    const randomIndex = Math.floor(Math.random() * activePlayers.length)
    gameState.currentPlayerId = activePlayers[randomIndex].id
    console.log('🎯 Случайный первый ход:', activePlayers[randomIndex].name)
  } else {
    const firstPlayerIndex = (dealerIndex + 1) % activePlayers.length
    const firstPlayer = activePlayers[firstPlayerIndex]
    
    gameState.currentPlayerId = firstPlayer.id
    console.log('🎯 Первый ход у:', firstPlayer.name, 'ID:', firstPlayer.id, 'Position:', firstPlayer.position)
  }
}

const dealDemoCards = () => {
  console.log('🃏 Начинаем раздачу карты активным игрокам...')
  
  players.forEach((player) => {
    if (player.id && !player.isFolded) {
      playerCards[player.id] = createTestCards()
      playerCards[player.id].forEach(card => {
        card.isVisible = false
      })
      console.log(`🎴 Игрок ${player.name} получил карты`)
    }
  })

  const firstActivePlayer = players.find(p => p.id && !p.isFolded)
  if (firstActivePlayer) {
    setTimeout(() => {
      gameState.currentPlayerId = firstActivePlayer.id
      console.log('🎯 Демо-игра началась! Первый ход у:', firstActivePlayer.name)
    }, 1000)
  }
}

const passToNextDemoPlayer = () => {
  console.log('🔄 [passToNextDemoPlayer] Начало передачи демо-хода')

  const activePlayers = players.filter(p => p.id && !p.isFolded)
  
  console.log('🔍 Активные игроки перед передачей хода:', activePlayers.map(p => ({
    name: p.name,
    hasActed: p.hasActed,
    currentBet: p.currentBet
  })))
  
  if (activePlayers.length === 0) return
  
  const currentIndex = activePlayers.findIndex(p => p.id === currentPlayerId.value)
  const nextIndex = (currentIndex + 1) % activePlayers.length
  const isNewRound = nextIndex === 0
  
  console.log('🔍 Индексы передачи:', {
    currentIndex,
    nextIndex, 
    isNewRound,
    currentPlayer: activePlayers[currentIndex]?.name,
    nextPlayer: activePlayers[nextIndex]?.name
  })
  
  // 🎯 КОММЕНТИРУЕМ сброс флагов - переносим в checkDemoRoundCompletion
  // if (isNewRound) {
  //   console.log('🔄 НОВЫЙ КРУГ ТОРГОВ - сбрасываем флаги действий')
  //   activePlayers.forEach(player => {
  //     player.hasActed = false
  //   })
  // }
  
  const nextPlayer = activePlayers[nextIndex]
  gameState.currentPlayerId = nextPlayer.id
  
  console.log('🔄 Ход передан:', {
    from: players.find(p => p.id === currentPlayerId.value)?.name,
    to: nextPlayer.name,
    newCurrentPlayerId: gameState.currentPlayerId,
    isNewRound: isNewRound
  })
}

// 🎯 РЕАЛЬНЫЙ РЕЖИМ МЕТОДЫ
const handleRealPlayerReady = async () => {
  try {
    console.log('✅ Marking player ready in real mode')
    await markPlayerReady()
  } catch (error) {
    console.error('❌ Ready action failed:', error)
  }
}

const handleRealAction = async (action, betAmount = null) => {
  try {
    console.log('🎯 Handling real action:', action, 'betAmount:', betAmount)
    await performAction(action, betAmount)
  } catch (error) {
    console.error('❌ Real action failed:', error)
  }
}

const syncWithBackendState = (backendState) => {
  if (!backendState) return
  
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

// 🎯 ОБЩИЕ МЕТОДЫ СТАВОК
const openRaiseModal = (player) => {
  const currentMax = getCurrentBet()
  raiseAmount.value = currentMax + 1
  raiseModal.value = true
  
  console.log('🎯 Открыто окно повышения ставки:', {
    mode: gameMode.value,
    min: minBet.value,
    max: maxBet.value,
    current: raiseAmount.value,
    currentMax: currentMax,
    player: player.name
  })
}

const confirmRaise = async () => {
  if (currentMode.value === 'demo') {
    confirmDemoRaise()
  } else {
    confirmRealRaise()
  }
}

const confirmDemoRaise = () => {
  const player = players.find(p => p.id === currentPlayerId.value)
  if (!player) return
  
  const baseRaiseAmount = raiseAmount.value // Это ВИДИМАЯ сумма ставки
  
  console.log('🔍 [confirmDemoRaise] НАЧАЛО:', {
    player: player.name,
    baseRaiseAmount: baseRaiseAmount,
    playerCurrentBet: player.currentBet, // Текущая ставка игрока
    gameMode: gameMode.value,
    currentRound: gameState.currentRound,
    baseBet: gameState.baseBet
  })

  // 🎯 ПРОВЕРКА: видимая ставка не может быть ниже базовой
  if (baseRaiseAmount < gameState.baseBet) {
    console.log('❌ Ставка не может быть ниже базовой:', gameState.baseBet)
    return
  }

  let playerPaidAmount
  let bankReceivedAmount

  if (gameMode.value === 'dark' && gameState.currentRound < 3) {
    // 🎯 ТЕМНАЯ СТАВКА (RAISE) - ПРИВИЛЕГИЯ 1-2 РАУНДЫ
    // Игрок платит ПОЛОВИНУ от ВИДИМОЙ суммы ставки
    playerPaidAmount = Math.floor(baseRaiseAmount / 2)
    bankReceivedAmount = baseRaiseAmount // Банк получает полную видимую сумму
    
    console.log('💰 [DARK RAISE] ПАРАМЕТРЫ:', {
      visibleBet: baseRaiseAmount,
      playerPaid: playerPaidAmount, // Половина от видимой
      bankReceived: bankReceivedAmount // Полная видимая
    })
  } else {
    // 🎯 ОБЫЧНАЯ СТАВКА (RAISE) ИЛИ ТЕМНАЯ В 3 РАУНДЕ
    playerPaidAmount = baseRaiseAmount
    bankReceivedAmount = baseRaiseAmount
    
    console.log('💰 [REGULAR RAISE] ПАРАМЕТРЫ:', {
      visibleBet: baseRaiseAmount,
      playerPaid: playerPaidAmount,
      bankReceived: bankReceivedAmount
    })
  }

  // Проверяем баланс
  if (player.balance >= playerPaidAmount) {
    console.log('💰 БАЛАНС ДОСТАТОЧЕН')
    
    const oldBalance = player.balance
    const oldPot = gameState.pot
    
    // Обновляем ставку и баланс
    player.currentBet = baseRaiseAmount
    player.balance -= playerPaidAmount
    gameState.pot += bankReceivedAmount
    
    if (gameMode.value === 'dark') {
      player.isDark = true
    }

    console.log('✅ СТАВКА ВЫПОЛНЕНА:', {
      player: player.name,
      oldBalance: oldBalance,
      newBalance: player.balance,
      oldPot: oldPot,
      newPot: gameState.pot,
      playerPaid: playerPaidAmount,
      bankReceived: bankReceivedAmount,
      newBet: player.currentBet
    })
    
    gameMode.value = null
    raiseModal.value = false
    passToNextPlayer()
    checkForRoundEnd()
  } else {
    console.log('❌ Недостаточно средств для ставки')
  }
}

const confirmRealRaise = async () => {
  try {
    const action = gameMode.value === 'dark' ? 'dark' : 'raise'
    await performAction(action, raiseAmount.value)
    raiseModal.value = false
    gameMode.value = null
  } catch (error) {
    console.error('❌ Real raise failed:', error)
  }
}

const cancelRaise = () => {
  raiseModal.value = false
  gameMode.value = null
}

const getDealerName = () => {
  const dealer = getDealer()
  return dealer?.name || 'Не выбран'
}

const getCurrentPlayerName = () => {
  const currentPlayer = getCurrentPlayer()
  return currentPlayer?.name || 'Без имени'
}

const handleDealCards = () => {
  if (currentMode.value === 'demo') {
    startDemoGame()
  } else {
    // Для реального режима - возможно другой метод или оставить пустым
    console.log('🎯 Deal cards in real mode')
  }
}

const getAdjustedBet = (baseAmount) => {
  if (gameMode.value === 'dark' && gameState.currentRound < 3) {
    const adjusted = Math.floor(baseAmount / 2)
    console.log(`🎯 Dark game adjustment: ${baseAmount} -> ${adjusted}`)
    return adjusted
  }
  return baseAmount
}

// 🎯 ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ДЕМО-РЕЖИМА
const selectRandomDemoDealer = () => {
  const activePlayers = players.filter(p => p.id && !p.isFolded)
  if (activePlayers.length === 0) return
  
  const randomIndex = Math.floor(Math.random() * activePlayers.length)
  const newDealer = activePlayers[randomIndex]
  gameState.dealerId = newDealer.id
  console.log(`🎫 Новый дилер: ${newDealer.name}`)
}

const collectDemoBaseBets = () => {
  console.log(`💰 Списываем базовую ставку ${gameState.baseBet}🪙 с каждого игрока`)
  
  players.forEach(player => {
    if (player.id && !player.isFolded) {
      if (player.balance >= gameState.baseBet) {
        player.balance -= gameState.baseBet
        player.currentBet = gameState.baseBet
        gameState.pot += gameState.baseBet
      } else {
        player.isFolded = true
      }
    }
  })
}

const checkForDemoRoundEnd = () => {
  console.log('🎯 [checkForDemoRoundEnd] Проверка завершения демо-раунда')
  setTimeout(() => {
    if (checkDemoRoundCompletion()) {
      console.log('🎯 Демо-раунд завершен!')
    }
  }, 1000)
}

const checkDemoRoundCompletion = () => {
  const activePlayers = players.filter(p => p.id && !p.isFolded)
  
  console.log('🎯 [checkDemoRoundCompletion] ПОДРОБНАЯ ПРОВЕРКА:', {
    activePlayers: activePlayers.map(p => ({
      name: p.name,
      bet: p.currentBet,
      folded: p.isFolded,
      hasActed: p.hasActed,
      isDark: p.isDark
    })),
    currentMaxBet: getCurrentBet(),
    round: gameState.currentRound
  })

  // 🎯 1. Если остался 1 игрок - игра завершается
  if (activePlayers.length === 1) {
    console.log('🎯 Остался 1 игрок - завершаем игру')
    endDemoGame(activePlayers[0])
    return true
  }

  // 🎯 2. Проверяем, все ли активные игроки сделали ход в этом круге
  const playersWhoNeedToAct = activePlayers.filter(p => !p.hasActed)
  
  console.log('🔍 Игроки, которые еще не сделали ход:', 
    playersWhoNeedToAct.map(p => p.name)
  )

  // Если есть игроки, которые еще не ходили - раунд не завершен
  if (playersWhoNeedToAct.length > 0) {
    console.log('❌ Раунд не завершен: есть игроки без хода')
    console.log('🔍 Детали неходивших игроков:', playersWhoNeedToAct.map(p => ({
      name: p.name,
      hasActed: p.hasActed,
      currentBet: p.currentBet
    })))
    return false
  }

  // 🎯 3. Проверяем, что все НЕСБРОСИВШИЕ игроки имеют одинаковые ставки
  const nonFoldedPlayers = activePlayers.filter(p => !p.isFolded)
  const currentMaxBet = getCurrentBet()
  
  const allBetsEqual = nonFoldedPlayers.every(player => 
    player.currentBet === currentMaxBet
  )

  console.log('🔍 Проверка равенства ставок:', {
    nonFoldedPlayers: nonFoldedPlayers.map(p => ({ name: p.name, bet: p.currentBet })),
    currentMaxBet: currentMaxBet,
    allBetsEqual: allBetsEqual
  })

  if (!allBetsEqual) {
    console.log('❌ Ставки не равны - продолжаем торги')
    return false
  }

  // 🎯 4. ВСЕ УСЛОВИЯ ВЫПОЛНЕНЫ - завершаем раунд
  console.log('✅ ВСЕ УСЛОВИЯ ВЫПОЛНЕНЫ! Завершаем раунд', gameState.currentRound)
  
  if (gameState.currentRound < 3) {
    // Переход на следующий раунд
    gameState.currentRound++
    console.log(`🔄 ПЕРЕХОД НА РАУНД ${gameState.currentRound}`)
    
    // Сбрасываем флаги действий для нового раунда
    console.log('🔍 Сбрасываем флаги hasActed для нового раунда')
    activePlayers.forEach(player => {
      console.log(`🔄 Сброс ${player.name}: hasActed = false`)
      player.hasActed = false
    })
    
    // Находим первого игрока после дилера
    const dealerIndex = activePlayers.findIndex(p => p.id === gameState.dealerId)
    const firstPlayerIndex = dealerIndex !== -1 ? (dealerIndex + 1) % activePlayers.length : 0
    const firstPlayer = activePlayers[firstPlayerIndex]
    
    gameState.currentPlayerId = firstPlayer.id
    
    console.log('🎯 Новый раунд начат!', {
      round: gameState.currentRound,
      firstPlayer: firstPlayer.name,
      activePlayers: activePlayers.length
    })
  } else {
    // Последний раунд - определяем победителя
    console.log('🏁 ПОСЛЕДНИЙ РАУНД - определяем победителя')
    determineDemoWinner()
  }
  
  return true
}

const determineDemoWinner = () => {
  const activePlayers = players.filter(p => !p.isFolded && p.id)
  if (activePlayers.length === 1) {
    endDemoGame(activePlayers[0])
  } else {
    endDemoGame(activePlayers[0])
  }
}

const endDemoGame = (winner) => {
  console.log(`🎉 Победитель: ${winner.name}! Выигрыш: ${gameState.pot}🪙`)
  winner.balance += gameState.pot
  gameState.status = 'finished'
  
  setTimeout(() => {
    alert(`🎉 Победитель: ${winner.name}! Выигрыш: ${gameState.pot}🪙`)
    setTimeout(() => {
      resetDemoGame()
    }, 5000)
  }, 1000)
}

const resetDemoGame = () => {
  gameState.status = 'waiting'
  gameState.pot = 0
  gameState.currentRound = 1
  gameState.currentPlayerId = 1
  
  players.forEach(player => {
    if (player.id) {
      player.isFolded = false
      player.isDark = false
      player.currentBet = 0
      player.isReady = false
      player.balance = 1000
    }
  })
  
  Object.keys(playerCards).forEach(key => delete playerCards[key])
  readyCheck.timeRemaining = 30
  startReadyTimer()
}

// 🎯 СИСТЕМА SEKA (общая)
const calculateSekaHandPoints = async (cards) => {
  if (!cards || cards.length < 2) {
    return { points: 0, combination: 'Неверное количество карт' }
  }
  
  try {
    // 🎯 ПЕРЕДАЕМ ВСЕ КАРТЫ (2 или 3) - бэкенд сам решит как считать
    const cardStrings = cards.map(card => `${card.rank}${card.suit}`)
    
    const response = await fetch('/api/public/seka/calculate-points', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        cards: cardStrings,
        card_count: cards.length // Передаем количество карт
      })
    })
    
    if (response.ok) {
      const result = await response.json()
      if (result.success) {
        console.log('✅ Очки успешно подсчитаны:', result)
        return { 
          points: result.points, 
          combination: result.combination 
        }
      } else {
        return { 
          points: 0, 
          combination: result.message || 'Ошибка сервера' 
        }
      }
    } else {
      const errorText = await response.text()
      console.error('❌ HTTP ошибка:', response.status, errorText)
      return { 
        points: 0, 
        combination: `Ошибка сервера: ${response.status}` 
      }
    }
  } catch (error) {
    console.error('❌ Ошибка запроса:', error)
    return { 
      points: 0, 
      combination: 'Ошибка сети' 
    }
  }
}

// 🎯 СИСТЕМА ГОТОВНОСТИ (демо)
const handlePlayerCancelReady = (playerId) => {
  if (currentMode.value === 'demo') {
    const player = players.find(p => p.id === playerId)
    if (player) {
      player.isReady = false
    }
  }
}

const handleReadyTimeout = () => {
  if (currentMode.value === 'demo') {
    const readyPlayers = players.filter(p => p.isReady && p.id)
    if (readyPlayers.length >= 2) {
      startDemoGame()
    }
  }
}

// 🎯 СОЗДАНИЕ ПОЛНОЙ КОЛОДЫ SEKA
const createFullDeck = () => {
  const suits = ['♥', '♦', '♣', '♠'];
  const ranks = ['6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
  
  const deck = [];
  suits.forEach(suit => {
    ranks.forEach(rank => {
      deck.push({ rank, suit, id: `${rank}${suit}` });
    });
  });
  
  // Добавляем джокера
  deck.push({ rank: '6', suit: '♣', isJoker: true, id: '6♣' });
  
  return deck;
}

// 🎯 СОЗДАНИЕ УКОРОЧЕННОЙ КОЛОДЫ SEKA (21 карта)
const createSekaDeck = () => {
  const suits = ['♥', '♦', '♣', '♠'];
  // Только карты от 10 до туза + джокер
  const ranks = ['10', 'J', 'Q', 'K', 'A'];
  
  const deck = [];
  suits.forEach(suit => {
    ranks.forEach(rank => {
      deck.push({ rank, suit, id: `${rank}${suit}` });
    });
  });
  
  // Добавляем джокера (6♣)
  deck.push({ rank: '6', suit: '♣', isJoker: true, id: '6♣' });
  
  console.log('🃏 Создана колода SEKA:', deck.length, 'карт');
  return deck;
}

// 🎯 РАЗДАТЬ УНИКАЛЬНЫЕ КАРТЫ ИЗ КОЛОДЫ SEKA
const dealUniqueCards = () => {
  const deck = createSekaDeck();
  shuffleArray(deck); // Перемешать колоду
  
  console.log('🃏 Начинаем раздачу из колоды SEKA...');
  
  players.forEach((player, index) => {
    if (player.id && !player.isFolded) {
      // Берем 3 карты из колоды
      playerCards[player.id] = deck.splice(0, 3).map(card => ({
        ...card,
        isVisible: false
      }));
      console.log(`🎴 ${player.name} получил карты:`, playerCards[player.id].map(c => `${c.rank}${c.suit}`));
    }
  });
  
  // Проверяем остаток колоды
  console.log(`🃏 Осталось карт в колоде: ${deck.length}`);
}

// 🎯 ФУНКЦИЯ ПЕРЕМЕШИВАНИЯ
const shuffleArray = (array) => {
  for (let i = array.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [array[i], array[j]] = [array[j], array[i]];
  }
  return array;
}

const startReadyTimer = () => {
  readyCheck.timer = setInterval(() => {
    if (readyCheck.timeRemaining > 0) {
      readyCheck.timeRemaining--
    } else {
      handleReadyTimeout()
    }
  }, 1000)
}

// 🎯 ПЕРЕКЛЮЧЕНИЕ РЕЖИМОВ
const switchMode = (newMode) => {
  currentMode.value = newMode
  console.log(`🔄 Switching to ${newMode} mode`)
  
  if (newMode === 'demo') {
    initializeDemoPlayers()
    resetDemoGame()
  } else {
    loadBackendState()
  }
}

// 🎯 СОХРАНЕНИЕ/ЗАГРУЗКА (демо)
const saveGameState = () => {
  if (currentMode.value !== 'demo') return
  
  const stateToSave = {
    players: players.map(p => ({ ...p })),
    gameState: { ...gameState },
    readyCheck: { ...readyCheck },
    playerCards: { ...playerCards },
    currentMode: currentMode.value
  }
  localStorage.setItem('sekaGameState', JSON.stringify(stateToSave))
}

const loadGameState = () => {
  const saved = localStorage.getItem('sekaGameState')
  if (saved) {
    try {
      const state = JSON.parse(saved)
      
      if (state.currentMode === 'demo') {
        players.splice(0, players.length, ...state.players)
        Object.assign(gameState, state.gameState)
        Object.assign(readyCheck, state.readyCheck)
        
        Object.keys(state.playerCards).forEach(playerId => {
          playerCards[playerId] = state.playerCards[playerId].map(card => ({
            ...card,
            isVisible: false
          }))
        })
        
        currentMode.value = 'demo'
        console.log('💾 Demo game state loaded from storage')
        
        // 🎯 ВОССТАНОВЛЕНИЕ ТЕКУЩЕГО ИГРОКА
        // Если текущий игрок "Свободно" - найти первого активного
        const currentPlayer = players.find(p => p.id === gameState.currentPlayerId)
        if (!currentPlayer || !currentPlayer.id) {
          const firstActive = players.find(p => p.id && !p.isFolded)
          if (firstActive) {
            gameState.currentPlayerId = firstActive.id
            console.log('🔄 Восстановлен текущий игрок:', firstActive.name)
          }
        }
        
        return true
      }
    } catch (error) {
      console.error('❌ Error loading game state:', error)
      localStorage.removeItem('sekaGameState')
    }
  }
  return false
}

const clearSave = () => {
  localStorage.removeItem('sekaGameState')
  location.reload()
}

// 🎯 ОБРАБОТЧИКИ
const handleTestAction = (action) => {
  if (currentMode.value === 'demo') {
    if (action === 'reset') {
      gameState.status = 'waiting'
      readyCheck.timeRemaining = 30
      players.forEach(player => {
        if (player.id) {
          player.isReady = false
          player.readyTimeRemaining = 30
          player.isFolded = false
          player.isDark = false
          player.currentBet = 0
        }
      })
      Object.keys(playerCards).forEach(key => delete playerCards[key])
      startReadyTimer()
    }
  }
}

// 🎯 LIFECYCLE
onMounted(() => {
  checkDevice()
  window.addEventListener('resize', checkDevice)
  
  const stateLoaded = loadGameState()
  
  if (!stateLoaded) {
    if (isDevelopment) {
      currentMode.value = 'demo'
      initializeDemoPlayers()
      readyCheck.timeRemaining = 10
      startReadyTimer()
    } else {
      currentMode.value = 'real'
      loadBackendState()
    }
  } else {
    // 🎯 ПРОВЕРКА СОСТОЯНИЯ ПОСЛЕ ЗАГРУЗКИ
    console.log('🔍 State after load:', {
      currentPlayerId: gameState.currentPlayerId,
      status: gameState.status,
      activePlayers: players.filter(p => p.id && !p.isFolded).map(p => ({ id: p.id, name: p.name }))
    })
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', checkDevice)
  if (readyCheck.timer) {
    clearInterval(readyCheck.timer)
  }
})

// 🎯 WATCHERS
watch([players, gameState, readyCheck], () => {
  if (currentMode.value === 'demo') {
    saveGameState()
  }
}, { deep: true })

watch(backendGameState, (newBackendState) => {
  if (currentMode.value === 'real' && newBackendState) {
    syncWithBackendState(newBackendState)
  }
})

// 🎯 ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
const checkDevice = () => {
  isMobile.value = window.innerWidth < 768
}
</script>

<style scoped>
.seka-game {
  position: relative;
  min-height: 100vh;
  background: linear-gradient(135deg, #0a2f0a 0%, #1a5a1a 100%);
  padding: 20px;
  overflow: hidden;
}

/* Переключатель режимов */
.mode-switcher {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-bottom: 20px;
}

.mode-btn {
  padding: 8px 16px;
  border: 2px solid #38a169;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  cursor: pointer;
  transition: all 0.3s;
}

.mode-btn.active {
  background: #38a169;
  transform: scale(1.05);
}

.mode-btn:hover {
  background: #2d8559;
}

.mode-indicator {
  background: rgba(56, 161, 105, 0.3) !important;
  border-color: #38a169 !important;
}

/* Остальные стили из предыдущих версий */
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

.timer-display {
  font-size: 0.8rem;
  color: #fbbf24;
  margin-top: 4px;
  font-weight: bold;
}

.debug-controls {
  text-align: center;
  margin-bottom: 10px;
}

.debug-btn {
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid #ef4444;
  color: #ef4444;
  padding: 5px 10px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 0.8rem;
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

.cancel-btn {
  background: #4a5568;
  color: white;
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
  
  .mode-switcher {
    flex-direction: column;
    align-items: center;
  }
  
  .mode-btn {
    width: 150px;
  }
}
</style>