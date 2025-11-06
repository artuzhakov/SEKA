<template>
  <div class="seka-game" :class="{ 'mobile': isMobile }">

    <!-- Система готовности -->
    <ReadyCheck 
      v-if="gameState.status === 'waiting'"
      :players="players"
      :time-remaining="readyCheck.timeRemaining"
      @player-ready="handlePlayerReady"
      @player-cancel-ready="handlePlayerCancelReady"
      @timeout="handleReadyTimeout"
    />

    <div class="debug-controls">
      <button @click="clearSave" class="debug-btn">🗑️ Очистить сохранение</button>
    </div>

    <!-- Заголовок игры -->
    <div class="game-header">
      <h1>🎴 SEKA</h1>
      <div class="game-meta">
        <div class="meta-item">Банк: <strong>{{ pot }} 🪙</strong></div>
        <div class="meta-item">Раунд: <strong>{{ currentRound }}</strong></div>
        <div class="meta-item">Дилер: <strong>{{ getDealer().name }}</strong></div>
        <div class="meta-item" v-if="gameState.status === 'waiting'">
          Готовы: <strong class="waiting-status">{{ readyCount }}/6</strong>
          <div class="timer-display">⏱️ {{ readyCheck.timeRemaining }}с</div>
        </div>
        <!-- ДОБАВЛЯЕМ ТЕКУЩЕГО ИГРОКА -->
        <div class="meta-item" v-if="gameState.status === 'active'">
          Ходит: <strong class="current-player">{{ getCurrentPlayer().name }}</strong>
        </div>
        <div class="meta-item" v-if="gameState.status === 'active'">
          Игроков: <strong>{{ activePlayersCount }}/6</strong>
        </div>
      </div>
    </div>

    <!-- Игровой стол управляет своей логикой -->
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
      @deal-cards="startGame"
    />

    <!-- Дебаг панель -->
    <DebugPanel 
      v-if="showDebug" 
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
          <!-- 🎯 ИНФОРМАЦИЯ О ТЕМНОЙ ИГРЕ -->
          <div v-if="gameMode === 'dark'" class="dark-benefits">
            <p>🎁 <strong>Привилегии темной игры (1-2 раунды):</strong></p>
            <ul>
              <li>• Ставка рассчитывается в 2 раза меньше</li>
              <li>• Базовая ставка: <strong>{{ raiseAmount }}🪙</strong></li> <!-- ОБНОВЛЕНО -->
              <li>• Ваша ставка: <strong>{{ getAdjustedBet(raiseAmount) }}🪙</strong></li> <!-- ОБНОВЛЕНО -->
              <li>• Экономия: <strong>{{ raiseAmount - getAdjustedBet(raiseAmount) }}🪙</strong></li> <!-- ОБНОВЛЕНО -->
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
              <!-- УБРАЛИ лишний внешний span, оставили только условный рендеринг -->
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
        
        <!-- 🎯 ИТОГОВАЯ ИНФОРМАЦИЯ -->
        <div v-if="gameMode === 'dark' && gameState.currentRound < 3" class="final-info">
          <p><strong>Итоговая ставка:</strong> {{ getAdjustedBet(raiseAmount) }}🪙</p> <!-- ОБНОВЛЕНО -->
          <p><strong>Экономия:</strong> {{ raiseAmount - getAdjustedBet(raiseAmount) }}🪙</p> <!-- ОБНОВЛЕНО -->
        </div>
        
        <div class="modal-actions">
          <button @click="confirmRaise" class="confirm-btn">
            <span v-if="gameMode === 'dark'">🌑 Играть в Темную ({{ getAdjustedBet(raiseAmount) }}🪙)</span> <!-- ОБНОВЛЕНО -->
            <span v-else-if="gameMode === 'open'">👁️ Открыть Карты ({{ raiseAmount }}🪙)</span> <!-- ОБНОВЛЕНО -->
            <span v-else>🎯 Поднять Ставку ({{ raiseAmount }}🪙)</span> <!-- ОБНОВЛЕНО -->
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
import GameTable from './components/GameTable.vue'
import DebugPanel from './components/DebugPanel.vue'
import ReadyCheck from './components/ReadyCheck.vue'

// 🎯 СОЗДАНИЕ ТЕСТОВЫХ КАРТ
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

// 🎯 ИНИЦИАЛИЗАЦИЯ ИГРОКОВ
const players = reactive([
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

// 🎯 КАРТЫ ИГРОКОВ
const playerCards = reactive({})

// 🎯 СОСТОЯНИЕ ИГРЫ
const gameState = reactive({
  pot: 0,
  currentRound: 1,
  currentPlayerId: 1,
  dealerId: 1,
  baseBet: 50,
  status: 'waiting'
})

// 🎯 СИСТЕМА ГОТОВНОСТИ
const readyCheck = reactive({
  timeRemaining: 10,
  timer: null,
  canStart: false
})

const gameMode = ref(null)

const showDebug = ref(false)
const isMobile = ref(false)
const windowWidth = ref(0)

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
const pot = computed(() => gameState.pot)
const currentRound = computed(() => gameState.currentRound)
const currentPlayerId = computed(() => gameState.currentPlayerId)
const dealerId = computed(() => gameState.dealerId)

const readyPlayers = computed(() => players.filter(p => p.isReady && p.id))
const readyCount = computed(() => {
  const count = readyPlayers.value.length
  console.log('🔢 Ready count updated:', count)
  return count
})

// 🎯 ДОБАВИМ быстрые суммы для ставок
const quickAmounts = computed(() => {
  const currentMax = getCurrentBet()
  return [
    currentMax + 10,
    currentMax + 25, 
    currentMax + 50,
    currentMax + 100
  ].filter(amount => amount <= maxBet.value)
})

const activePlayersCount = computed(() => {
  return players.filter(p => p.id && !p.isFolded).length
})

const getDealer = () => players.find(p => p.id === dealerId.value) || players[0]

// 🎯 СИСТЕМА ГОТОВНОСТИ
const handlePlayerReady = (playerId) => {

  console.log('🎯 [SekaGame] handlePlayerReady CALLED with playerId:', playerId)
  
  const player = players.find(p => p.id === playerId)
  if (!player || gameState.status !== 'waiting') return
  
  player.isReady = !player.isReady
  console.log('✅ [SekaGame] Player state updated:', {
    name: player.name,
    isReady: player.isReady
  })
  
  // 🔥 НОВАЯ ЛОГИКА: Запускаем таймер автостарта при 2+ игроках
  if (readyCount.value >= 2 && !readyCheck.canStart) {
    console.log('🚀 [SekaGame] 2+ players ready, starting countdown...')
    readyCheck.canStart = true
    
    // Таймер автостарта через 10 секунд
    setTimeout(() => {
      if (gameState.status === 'waiting' && readyCount.value >= 2) {
        console.log('⏰ [SekaGame] Auto-start timer expired, starting game!')
        startGame()
      }
    }, 10000) // 10 секунд
  }
}

const handlePlayerCancelReady = (playerId) => {
  const player = players.find(p => p.id === playerId)
  if (player) {
    player.isReady = false
    console.log(`❌ Игрок ${player.name} отменил готовность`)
  }
}

const handleReadyTimeout = () => {
  console.log('⏰ Таймаут готовности!')
  
  // 🔥 ИСПРАВЛЕНО: ВЫКИДЫВАЕМ неготовых, а не отмечаем их готовыми
  const readyPlayers = players.filter(p => p.isReady && p.id)
  console.log(`⏰ Таймаут! Готовых игроков: ${readyPlayers.length}`)
  
  if (readyPlayers.length >= 2) {
    console.log('⏰ Запускаем игру с готовыми игроками...')
    startGame()
  } else {
    console.log('⏰ Недостаточно готовых игроков для старта')
    // Можно показать сообщение или перезапустить таймер
  }
}

const selectRandomDealer = () => {
  const activePlayers = players.filter(p => p.id && !p.isFolded)
  if (activePlayers.length === 0) return
  
  const randomIndex = Math.floor(Math.random() * activePlayers.length)
  const newDealer = activePlayers[randomIndex]
  gameState.dealerId = newDealer.id
  console.log(`🎫 Новый дилер: ${newDealer.name}`)
}
// 🎯 СПИСАНИЕ БАЗОВОЙ СТАВКИ (добавить новый метод)
const collectBaseBets = () => {
  console.log(`💰 Списываем базовую ставку ${gameState.baseBet}🪙 с каждого игрока`)
  
  players.forEach(player => {
    if (player.id && !player.isFolded) {
      if (player.balance >= gameState.baseBet) {
        player.balance -= gameState.baseBet
        player.currentBet = gameState.baseBet
        gameState.pot += gameState.baseBet
        console.log(`✅ ${player.name}: -${gameState.baseBet}🪙 (баланс: ${player.balance}🪙)`)
      } else {
        console.log(`❌ ${player.name}: недостаточно средств, выбывает`)
        player.isFolded = true
      }
    }
  })
  
  console.log(`💰 Итоговый банк: ${gameState.pot}🪙`)
}

// 🎯 ЗАПУСК ИГРЫ
const startGame = () => {

  console.log('🔍 [DEBUG] Before start - players:', players.map(p => ({
    name: p.name,
    id: p.id,
    isReady: p.isReady,
    isFolded: p.isFolded
  })))

  if (readyCount.value < 2) {
    console.log('❌ Недостаточно игроков для старта')
    return
  }

  console.log('🚀 Запускаем игру...')
  
  // 🔥 ПРАВИЛЬНО ВЫКИДЫВАЕМ НЕГОТОВЫХ ИГРОКОВ
  players.forEach(player => {
    if (player.id && !player.isReady) {
      console.log(`👋 Игрок ${player.name} выкинут из игры (не готов)`)
      
      // Сохраняем позицию перед очисткой
      const position = player.position
      
      // Полностью очищаем игрока
      Object.assign(player, {
        id: null,
        name: 'Свободно',
        balance: 0,
        isFolded: true,
        isReady: false,
        isDark: false,
        currentBet: 0,
        position: position, // сохраняем позицию
        lastAction: ''
      })
      
      // Очищаем карты
      if (playerCards[player.id]) {
        delete playerCards[player.id]
      }
    }
  })

  // 🔥 ПЕРЕСЧИТЫВАЕМ АКТИВНЫХ ИГРОКОВ
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
  
  // Раздаем карты ТОЛЬКО активным игрокам
  selectRandomDealer() // Выбор дилера
  collectBaseBets()    // Сбор базовой ставки
  dealCards()
}

// 🎯 РАЗДАЧА КАРТ
const dealCards = () => {
  console.log('🃏 Начинаем раздачу карты активным игрокам...')
  
  players.forEach((player, index) => {
    // Раздаем карты ТОЛЬКО активным игрокам (с id и не сбросившим)
    if (player.id && !player.isFolded) {
      playerCards[player.id] = createTestCards()
      playerCards[player.id].forEach(card => {
        card.isVisible = false
      })
      console.log(`🎴 Игрок ${player.name} получил карты`)
    } else {
      console.log(`⏭️ Игрок ${player.name} пропускается (не активен)`)
    }
  })

  // Находим первого активного игрока для хода
  const firstActivePlayer = players.find(p => p.id && !p.isFolded)
  if (firstActivePlayer) {
    setTimeout(() => {
      gameState.currentPlayerId = firstActivePlayer.id
      console.log('🎯 Игра началась! Первый ход у:', firstActivePlayer.name)
    }, 1000)
  }
}

const handlePlayerAction = (action) => {
  console.log('🎯 [SekaGame] handlePlayerAction called:', action)
  console.log('🎯 [SekaGame] Current player ID:', currentPlayerId.value)
  
  // ИСПРАВЛЕНО: проверяем что действие от ЛЮБОГО текущего игрока
  if (gameState.status === 'active') {
    takeAction(action)
  } else {
    console.log('⚠️ [SekaGame] Action ignored - game not active')
  }
}

// 🎯 ОБНОВИТЬ метод takeAction для кнопки "open"
const takeAction = (action) => {
  console.log('🎯 Действие:', action)
  
  const player = players.find(p => p.id === currentPlayerId.value)
  if (!player) return

  player.lastAction = action

  switch(action) {
    case 'check':
      if (getCurrentBet() === 0) {
        console.log('✅ Пропуск хода')
        passToNextPlayer()
        checkForRoundEnd()
      } else {
        console.log('❌ Нельзя пропустить при наличии ставки')
      }
      break
      
    case 'call':
      const currentMaxBet = getCurrentBet()
      const callAmount = currentMaxBet - player.currentBet
      
      console.log('💰 CALL расчет:', {
        player: player.name,
        currentBet: player.currentBet,
        maxBet: currentMaxBet,
        callAmount: callAmount,
        balance: player.balance
      })
      
      if (callAmount > 0 && player.balance >= callAmount) {
        player.currentBet += callAmount
        player.balance -= callAmount
        gameState.pot += callAmount
        
        console.log('✅ Поддержка ставки:', {
          player: player.name,
          callAmount: callAmount,
          newBet: player.currentBet,
          newBalance: player.balance,
          newPot: gameState.pot
        })
        
        // 🎯 ПЕРЕДАЕМ ХОД СРАЗУ ЖЕ
        passToNextPlayer()
      } else if (callAmount === 0) {
        console.log('✅ Нет необходимости поддерживать (ставка уже равна)')
        passToNextPlayer()
      } else {
        console.log('❌ Недостаточно средств для поддержки:', {
          needed: callAmount,
          balance: player.balance
        })
      }
      break
      
    case 'raise':
      gameMode.value = null // Обычное повышение
      openRaiseModal(player)
      break
      
    case 'fold':
      player.isFolded = true
      player.isDark = false // Сбрасываем темную игру при фолде
      if (playerCards[player.id]) {
        playerCards[player.id].forEach(card => card.isVisible = false)
      }
      console.log('✅ Игрок сбросил карты')
      passToNextPlayer()
      checkForRoundEnd()
      break
      
    case 'dark':
      // 🎯 ТЕМНАЯ ИГРА - выбираем режим и открываем ставку
      if (gameState.currentRound >= 3) {
        console.log('❌ Темная игра недоступна в 3 раунде')
        return
      }
      gameMode.value = 'dark'
      openRaiseModal(player)
      break
      
    case 'open':
      // 🎯 ОТКРЫТИЕ КАРТ (после темной игры)
      if (playerCards[player.id]) {
        playerCards[player.id].forEach(card => card.isVisible = true)

      // 🎯 РАССЧИТЫВАЕМ ОЧКИ КОМБИНАЦИИ SEKA
      const result = calculateSekaHandPoints(playerCards[player.id])
      console.log(`🎯 Комбинация ${player.name}: ${result.combination} (${result.points} очков)`)
      }
      console.log('👁️ Игрок открыл карты:', player.name)
      // gameMode.value = 'open'
      // openRaiseModal(player)
      break
      
    case 'reveal':
      const lastPlayerBet = getLastPlayerBet()
      const revealAmount = lastPlayerBet * 2
      if (player.balance >= revealAmount) {
        player.currentBet += revealAmount
        player.balance -= revealAmount
        gameState.pot += revealAmount
        console.log('✅ Вскрытие с ставкой:', revealAmount)
        passToNextPlayer()
      }
      break
  }
}

// 🎯 ДОБАВИМ логику завершения раундов
const checkRoundCompletion = () => {
  const activePlayers = players.filter(p => !p.isFolded && p.id)
  
  // Если остался 1 игрок - он выигрывает
  if (activePlayers.length === 1) {
    endGame(activePlayers[0])
    return true
  }
  
  // 🎯 ИСПРАВЛЕНИЕ: Правильная проверка завершения раунда
  // Раунд завершается, когда ВСЕ активные игроки:
  // 1. Сделали одинаковые ставки ИЛИ
  // 2. Сбросили карты (fold)
  const currentMaxBet = getCurrentBet()
  const playersWithActions = activePlayers.filter(player => 
    player.currentBet === currentMaxBet || player.isFolded
  )
  
  console.log(`🔄 Проверка раунда: ${playersWithActions.length}/${activePlayers.length} игроков сделали действия`)
  
  // Раунд завершен если ВСЕ активные игроки сделали действия
  if (playersWithActions.length === activePlayers.length && activePlayers.length > 1) {
    if (gameState.currentRound < 3) {
      // Переходим к следующему раунду
      gameState.currentRound++
      console.log(`🔄 Переход к раунду ${gameState.currentRound}`)
      
      // Сбрасываем ставки игроков для нового раунда
      players.forEach(player => {
        if (player.id) {
          player.currentBet = 0
        }
      })
      
      // Начинаем новый раунд с первого активного игрока после дилера
      const dealerIndex = activePlayers.findIndex(p => p.id === gameState.dealerId)
      const firstPlayerIndex = (dealerIndex + 1) % activePlayers.length
      const firstPlayer = activePlayers[firstPlayerIndex]
      
      gameState.currentPlayerId = firstPlayer.id
      console.log(`🎯 Начало раунда ${gameState.currentRound}, ход у: ${firstPlayer.name}`)
      
    } else {
      // 3 раунд завершен - определяем победителя
      determineWinner()
    }
    return true
  }
  
  return false
}

// 🎯 ОБНОВИМ метод передачи хода
const passToNextPlayer = () => {
  const activePlayers = players.filter(p => p.id && !p.isFolded)
  if (activePlayers.length === 0) return
  
  const currentIndex = activePlayers.findIndex(p => p.id === currentPlayerId.value)
  
  // 🎯 ПЕРЕДАЕМ ХОД СЛЕДУЮЩЕМУ ИГРОКУ (не через одного)
  const nextIndex = (currentIndex + 1) % activePlayers.length
  const nextPlayer = activePlayers[nextIndex]
  
  gameState.currentPlayerId = nextPlayer.id
  
  console.log('🔄 Ход передан:', {
    from: players.find(p => p.id === currentPlayerId.value)?.name,
    to: nextPlayer.name,
    activePlayers: activePlayers.map(p => p.name)
  })
}

// 🎯 ДОБАВИТЬ метод для принудительной проверки раунда
const checkForRoundEnd = () => {
  setTimeout(() => {
    if (checkRoundCompletion()) {
      console.log('🎯 Раунд завершен!')
    }
  }, 1000)
}

// 🎯 РЕАЛЬНЫЕ ПРАВИЛА SEKA ИЗ ScoringService.php
const calculateSekaHandPoints = (cards) => {
  if (!cards || cards.length !== 3) return { points: 0, combination: 'Нет карт' }
  
  // Преобразуем карты в формат для подсчета
  const cardStrings = cards.map(card => `${card.rank}${card.suit}`)
  const hasJoker = cardStrings.includes('6♣')
  const suits = cards.map(card => card.suit)
  const ranks = cards.map(card => card.rank)
  
  // 🎯 ПРОВЕРКА СПЕЦИАЛЬНЫХ КОМБИНАЦИЙ
  
  // Убираем джокер из подсчета для специальных комбинаций
  const ranksForSpecial = hasJoker ? ranks.filter(rank => rank !== '6') : [...ranks]
  const rankCounts = {}
  ranksForSpecial.forEach(rank => {
    rankCounts[rank] = (rankCounts[rank] || 0) + 1
  })
  
  // Три десятки (33)
  if ((rankCounts['10'] || 0) === 3) return { points: 33, combination: 'Три десятки' }
  if (hasJoker && (rankCounts['10'] || 0) === 2) return { points: 33, combination: 'Три десятки' }
  
  // Три вальта (34)
  if ((rankCounts['J'] || 0) === 3) return { points: 34, combination: 'Три вальта' }
  if (hasJoker && (rankCounts['J'] || 0) === 2) return { points: 34, combination: 'Три вальта' }
  
  // Три дамы (35)
  if ((rankCounts['Q'] || 0) === 3) return { points: 35, combination: 'Три дамы' }
  if (hasJoker && (rankCounts['Q'] || 0) === 2) return { points: 35, combination: 'Три дамы' }
  
  // Три короля (36)
  if ((rankCounts['K'] || 0) === 3) return { points: 36, combination: 'Три короля' }
  if (hasJoker && (rankCounts['K'] || 0) === 2) return { points: 36, combination: 'Три короля' }
  
  // Три туза (37)
  if ((rankCounts['A'] || 0) === 3) return { points: 37, combination: 'Три туза' }
  if (hasJoker && (rankCounts['A'] || 0) === 2) return { points: 37, combination: 'Три туза' }
  
  // 🎯 ПРОВЕРКА КОМБИНАЦИЙ С МАСТЯМИ
  
  const suitCounts = {}
  suits.forEach(suit => {
    suitCounts[suit] = (suitCounts[suit] || 0) + 1
  })
  const maxSameSuit = Math.max(...Object.values(suitCounts))
  const hasAce = ranks.includes('A')
  
  // Джокер + Туз + карта той же масти (32)
  if (hasJoker && hasAce) {
    const aceIndex = ranks.indexOf('A')
    const aceSuit = suits[aceIndex]
    let aceSuitCount = 0
    suits.forEach((suit, index) => {
      if (suit === aceSuit && ranks[index] !== '6') {
        aceSuitCount++
      }
    })
    if (aceSuitCount >= 2) {
      return { points: 32, combination: 'Джокер + Туз + масть' }
    }
  }
  
  // Три одинаковые масти (30)
  if (maxSameSuit === 3 && !hasJoker && !hasAce) {
    return { points: 30, combination: 'Три одинаковые масти' }
  }
  
  // Три одинаковые + Туз (31) ИЛИ Джокер + две одинаковые (31)
  if ((maxSameSuit === 3 && hasAce) || (hasJoker && maxSameSuit === 2)) {
    return { points: 31, combination: 'Три масти + Туз/Джокер' }
  }
  
  // 🎯 БАЗОВЫЕ КОМБИНАЦИИ
  
  const uniqueSuits = new Set(suits).size
  
  if (uniqueSuits === 3 && !hasJoker && !hasAce) {
    return { points: 10, combination: 'Разные масти' }
  }
  
  if (uniqueSuits === 3 && hasAce && !hasJoker) {
    return { points: 11, combination: 'Разные масти + Туз' }
  }
  
  // Если есть джокер, но нет особых комбинаций
  if (hasJoker) {
    return { points: 10, combination: 'С джокером' }
  }
  
  return { points: 10, combination: 'Базовая' }
}

// 🎯 МЕТОД ДЛЯ ПОЛУЧЕНИЯ КРАСИВОГО НАЗВАНИЯ КОМБИНАЦИИ
const getCombinationDisplayName = (points) => {
  const names = {
    33: '🎯 Три десятки',
    34: '🎯 Три вальта', 
    35: '🎯 Три дамы',
    36: '🎯 Три короля',
    37: '🎯 Три туза',
    32: '🌟 Джокер + Туз + масть',
    31: '✨ Три масти + Туз/Джокер',
    30: '💎 Три одинаковые масти',
    22: '⭐ Два туза',
    21: '🔸 Две масти + Туз/Джокер',
    20: '🔹 Две одинаковые масти',
    11: '♣ Разные масти + Туз',
    10: '♠ Базовая комбинация'
  }
  return names[points] || `Комбинация (${points})`
}

// 🎯 МЕТОДЫ ДЛЯ СТАВОК
const getCurrentBet = () => {
  // Максимальная ставка среди всех игроков
  const maxPlayerBet = Math.max(...players.map(p => p.currentBet))
  const currentBet = Math.max(maxPlayerBet, gameState.baseBet)
  
  console.log('🎯 Текущая максимальная ставка:', {
    maxPlayerBet: maxPlayerBet,
    baseBet: gameState.baseBet,
    result: currentBet,
    players: players.map(p => ({ name: p.name, bet: p.currentBet }))
  })
  
  return currentBet
}

const getLastPlayerBet = () => {
  // Ставка предыдущего игрока (исключая текущего)
  const activePlayers = players.filter(p => !p.isFolded && p.id)
  const currentIndex = activePlayers.findIndex(p => p.id === currentPlayerId.value)
  const prevIndex = (currentIndex - 1 + activePlayers.length) % activePlayers.length
  return activePlayers[prevIndex]?.currentBet || 0
}

const getPlayerAfterDealer = () => {
  const dealerPosition = players.find(p => p.id === dealerId.value)?.position
  if (!dealerPosition) return null
  
  // Находим следующего активного игрока после дилера
  const activePlayers = players.filter(p => !p.isFolded && p.id)
  const dealerIndex = activePlayers.findIndex(p => p.position === dealerPosition)
  const nextIndex = (dealerIndex + 1) % activePlayers.length
  return activePlayers[nextIndex]
}

// 🎯 ПОЛЗУНОК ДЛЯ ПОВЫШЕНИЯ
const raiseModal = ref(false)
const raiseAmount = ref(0) // для ползунка повышения ставки
const currentRaiseAmount = ref(0)
const minBet = computed(() => {
  const currentMaxBet = getCurrentBet()
  return currentMaxBet + 1
})
const maxBet = computed(() => {
  const player = players.find(p => p.id === currentPlayerId.value)
  return player ? Math.min(player.balance + player.currentBet, 500) : 0
})

const openRaiseModal = (player) => {
  // Начальная ставка = текущая максимальная + 1
  const currentMax = getCurrentBet()
  raiseAmount.value = currentMax + 1
  raiseModal.value = true
  
  console.log('🎯 Открыто окно повышения ставки:', {
    mode: gameMode.value,           // было: режим
    min: minBet.value,              // было: min (уже нормально)
    max: maxBet.value,              // было: max (уже нормально)  
    current: raiseAmount.value,     // ✅ ИСПРАВЛЕНО + английское название
    currentMax: currentMax,         // было: текущийМаксимум
    player: player.name             // было: игрок
  })
}

// 🎯 ОБНОВИМ метод confirmRaise для проверки раундов
const confirmRaise = () => {
  const player = players.find(p => p.id === currentPlayerId.value)
  if (!player) {
    console.log('❌ Player not found')
    return
  }
  
  const baseRaiseAmount = raiseAmount.value - player.currentBet
  
  if (baseRaiseAmount < 1) {
    console.log('❌ Raise amount must be at least 1 more than current bet')
    return
  }
  
  // 🎯 ИСПРАВЛЕНИЕ: getAdjustedBet должен применяться к raiseAmount.value, а не к baseRaiseAmount
  const adjustedBetAmount = getAdjustedBet(raiseAmount.value)
  const actualPaidAmount = adjustedBetAmount - player.currentBet
  
  if (player.balance >= actualPaidAmount) {
    // Выполняем ставку с учетом скидки
    player.currentBet = adjustedBetAmount
    player.balance -= actualPaidAmount
    gameState.pot += actualPaidAmount
    
    // Применяем выбранный режим игры
    if (gameMode.value === 'dark') {
      player.isDark = true
      console.log('🌑 Dark game selected:', {
        displayedBet: raiseAmount.value,
        actualBet: adjustedBetAmount,
        paid: actualPaidAmount,
        saved: raiseAmount.value - adjustedBetAmount
      })
    } else if (gameMode.value === 'open') {
      if (playerCards[player.id]) {
        playerCards[player.id].forEach(card => card.isVisible = true)
      }
      console.log('👁️ Игрок открыл карты со ставкой:', finalBetAmount)
    }
    
    console.log('✅ Raise confirmed:', {
      player: player.name,
      baseAmount: baseRaiseAmount,
      finalAmount: actualPaidAmount,
      newBet: adjustedBetAmount,
      mode: gameMode.value
    })
    
    // Сбрасываем режим и закрываем модалку
    gameMode.value = null
    raiseModal.value = false
    
    // 🎯 ВАЖНО: Передаем ход следующему игроку
    passToNextPlayer()
    
  } else {
    console.log('❌ Insufficient funds for raise')
  }
}

// 🎯 ДОБАВИМ метод определения победителя
const determineWinner = () => {
  console.log('🏆 Завершение игры, определение победителя...')
  
  const activePlayers = players.filter(p => !p.isFolded && p.id)
  
  if (activePlayers.length === 1) {
    // Остался один игрок - он победитель
    endGame(activePlayers[0])
  } else {
    // TODO: Реализовать логику подсчета очков карт
    // Пока берем первого активного игрока как победителя
    endGame(activePlayers[0])
  }
}

// 🎯 ДОБАВИМ метод завершения игры
const endGame = (winner) => {
  console.log(`🎉 Победитель: ${winner.name}! Выигрыш: ${gameState.pot}🪙`)
  
  // Начисляем выигрыш
  winner.balance += gameState.pot
  
  gameState.status = 'finished'
  
  // Показываем сообщение о победе
  setTimeout(() => {
    alert(`🎉 Победитель: ${winner.name}! Выигрыш: ${gameState.pot}🪙`)
    
    // Перезапускаем игру через 5 секунд
    setTimeout(() => {
      resetGame()
    }, 5000)
  }, 1000)
}

// 🎯 ДОБАВИМ метод сброса игры
const resetGame = () => {
  console.log('🔄 Перезапуск игры...')
  
  gameState.status = 'waiting'
  gameState.pot = 0
  gameState.currentRound = 1
  gameState.currentPlayerId = 1
  
  // Сбрасываем игроков
  players.forEach(player => {
    if (player.id) {
      player.isFolded = false
      player.isDark = false
      player.currentBet = 0
      player.isReady = false
      player.balance = 1000
    }
  })
  
  // Очищаем карты
  Object.keys(playerCards).forEach(key => delete playerCards[key])
  
  // Запускаем таймер готовности
  readyCheck.timeRemaining = 30
  startReadyTimer()
}

// 🎯 ДОБАВИТЬ расчет ставки с учетом темной игры
const getAdjustedBet = (baseAmount) => {
  if (gameMode.value === 'dark' && gameState.currentRound < 3) {
    // Для темной игры в 1-2 раундах ставка в 2 раза меньше
    const adjusted = Math.floor(baseAmount / 2)
    console.log(`🎯 Dark game adjustment: ${baseAmount} -> ${adjusted}`)
    return adjusted
  }
  return baseAmount
}

const cancelRaise = () => {
  raiseModal.value = false
  gameMode.value = null
  console.log('❌ Повышение ставки отменено')
}

// 🎯 ДОБАВИТЬ метод для открытия карт (после темной игры)
const handleOpenCards = () => {
  const player = players.find(p => p.id === currentPlayerId.value)
  if (!player || !player.isDark) {
    console.log('❌ Нельзя открыть карты - игрок не в темной игре')
    return
  }
  
  // 🎯 OPEN доступен всем - убираем проверку на isDark
  if (playerCards[player.id]) {
    playerCards[player.id].forEach(card => card.isVisible = true)
  }
  
  // 🎯 Если игрок был в темной игре - сбрасываем этот статус
  if (player.isDark) {
    player.isDark = false
    console.log('👁️ Игрок открыл карты после темной игры')
  } else {
    console.log('👁️ Игрок открыл карты')
  }

  passToNextPlayer()
}

// 🎯 ТАЙМЕР ГОТОВНОСТИ
const startReadyTimer = () => {
  readyCheck.timer = setInterval(() => {
    if (readyCheck.timeRemaining > 0) {
      readyCheck.timeRemaining--
      
      players.forEach(player => {
        if (player.id && player.readyTimeRemaining > 0) {
          player.readyTimeRemaining--
        }
      })
    } else {
      handleReadyTimeout()
    }
  }, 1000)
}

const getCurrentPlayer = () => {
  return players.find(p => p.id === currentPlayerId.value) || { name: 'Неизвестно' }
}

const handleTestAction = (action) => {
  console.log('🔧 Тестовое действие:', action)
  
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

// Проверка устройства
const checkDevice = () => {
  windowWidth.value = window.innerWidth
  isMobile.value = windowWidth.value < 768
}

// 🎯 СОХРАНЕНИЕ СОСТОЯНИЯ
const saveGameState = () => {
  const stateToSave = {
    players: players.map(p => ({ ...p })),
    gameState: { ...gameState },
    readyCheck: { ...readyCheck },
    playerCards: { ...playerCards }
  }
  localStorage.setItem('sekaGameState', JSON.stringify(stateToSave))
  console.log('💾 Game state saved')
}

// 🎯 ЗАГРУЗКА СОСТОЯНИЯ
const loadGameState = () => {
  const saved = localStorage.getItem('sekaGameState')
  if (saved) {
    try {
      const state = JSON.parse(saved)
      
      // Восстанавливаем игроков
      players.splice(0, players.length, ...state.players)
      
      // Восстанавливаем состояние игры
      Object.assign(gameState, state.gameState)
      Object.assign(readyCheck, state.readyCheck)
      
      // 🔥 ИСПРАВЛЕНИЕ: Восстанавливаем карты, но ВСЕ закрываем
      Object.keys(state.playerCards).forEach(playerId => {
        playerCards[playerId] = state.playerCards[playerId].map(card => ({
          ...card,
          isVisible: false // ← ВСЕ КАРТЫ ЗАКРЫТЫ ПРИ ЗАГРУЗКЕ
        }))
      })
      
      console.log('💾 Game state loaded from storage')
      console.log('⏱️ Remaining time:', readyCheck.timeRemaining)
      return true
    } catch (error) {
      console.error('❌ Error loading game state:', error)
      localStorage.removeItem('sekaGameState')
    }
  }
  return false
}

// 🎯 ПРОДОЛЖЕНИЕ ТАЙМЕРА
const continueReadyTimer = () => {
  if (readyCheck.timer) {
    clearInterval(readyCheck.timer)
  }
  
  readyCheck.timer = setInterval(() => {
    if (readyCheck.timeRemaining > 0) {
      readyCheck.timeRemaining--
      
      players.forEach(player => {
        if (player.id && player.readyTimeRemaining > 0) {
          player.readyTimeRemaining--
        }
      })
    } else {
      handleReadyTimeout()
    }
  }, 1000)
}

const clearSave = () => {
  localStorage.removeItem('sekaGameState')
  location.reload()
}

// 🎯 АВТОСОХРАНЕНИЕ ПРИ ИЗМЕНЕНИЯХ
watch([players, gameState, readyCheck], () => {
  saveGameState()
}, { deep: true, immediate: false })

// 🎯 LIFECYCLE
onMounted(() => {
  checkDevice()
  window.addEventListener('resize', checkDevice)
  
  // Пытаемся загрузить сохраненное состояние
  const stateLoaded = loadGameState()
  
  if (!stateLoaded) {
    // Только если нет сохраненного состояния - запускаем новый таймер
    console.log('🎮 Новая игра инициализирована!')
    readyCheck.timeRemaining = 10
    startReadyTimer()
  } else {
    console.log('🎮 Игра восстановлена из сохранения!')
    
    // Продолжаем таймер с сохраненного времени
    if (gameState.status === 'waiting' && readyCheck.timeRemaining > 0) {
      continueReadyTimer()
    }
  }
  
  // Для отладки
  window.debugPlayers = players
})

onUnmounted(() => {
  window.removeEventListener('resize', checkDevice)
  if (readyCheck.timer) {
    clearInterval(readyCheck.timer)
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

/* Заголовок */
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

.timer-display {
  font-size: 0.8rem;
  color: #fbbf24;
  margin-top: 4px;
  font-weight: bold;
}

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

.final-info p {
  margin: 0.5rem 0;
  font-size: 1.1rem;
}

.warning {
  color: #fbbf24;
  font-weight: bold;
}

.slider-labels .current-bet small {
  font-size: 0.8rem;
  opacity: 0.8;
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
  position: relative;
  top: auto;
  bottom: auto;
  left: auto;
  right: auto;
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
  position: relative;
}

.slider-labels::before,
.slider-labels::after {
  content: "|";
  color: #6b7280;
  font-size: 1rem;
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

/* Адаптивность */
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