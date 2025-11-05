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

    <!-- Ползунок для повышения ставки -->
    <div class="slider-modal">
      <div class="slider-content">
        <h3>Повышение ставки</h3>
        <div class="slider-range">
          <span>Min: {{ minBet }}</span>
          <input type="range" :min="minBet" :max="maxBet" v-model="currentBet">
          <span>Max: {{ maxBet }}</span>
        </div>
        <div class="bet-amount">Ставка: {{ currentBet }}🪙</div>
        <button @click="confirmRaise">Подтвердить</button>
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

const takeAction = (action) => {
  console.log('🎯 Действие:', action)
  
  const player = players.find(p => p.id === currentPlayerId.value)
  if (!player) return

  player.lastAction = action

  switch(action) {
    case 'check':
      // Только если нет текущей ставки
      if (getCurrentBet() === 0) {
        console.log('✅ Пропуск хода')
      } else {
        console.log('❌ Нельзя пропустить при наличии ставки')
        return
      }
      break
      
    case 'call':
      const callAmount = getCurrentBet() - player.currentBet
      if (player.balance >= callAmount) {
        player.currentBet += callAmount
        player.balance -= callAmount
        gameState.pot += callAmount
        console.log('✅ Поддержка ставки:', callAmount)
      }
      break
      
    case 'raise':
      // Открываем модалку с ползунком
      openRaiseModal(player)
      return // не передаем ход пока не подтвердят
      
    case 'fold':
      player.isFolded = true
      player.cards.forEach(card => card.isVisible = false)
      console.log('✅ Игрок сбросил карты')
      break
      
    case 'dark':
    case 'open':
      // Для темной/открытия нужна ставка
      if (player.currentBet === 0) {
        console.log('❌ Сначала сделайте ставку')
        return
      }
      player.isDark = (action === 'dark')
      player.cards.forEach(card => card.isVisible = (action === 'open'))
      console.log(`✅ ${action === 'dark' ? 'Игра в темную' : 'Открытие карт'}`)
      break
      
    case 'reveal':
      // Вскрытие - ставка в 2x от предыдущего игрока
      const lastPlayerBet = getLastPlayerBet()
      const revealAmount = lastPlayerBet * 2
      if (player.balance >= revealAmount) {
        player.currentBet += revealAmount
        player.balance -= revealAmount
        gameState.pot += revealAmount
        console.log('✅ Вскрытие с ставкой:', revealAmount)
      }
      break
  }

  // Передаем ход только после успешного действия со ставкой
  if (gameState.status === 'active' && action !== 'raise') {
    passToNextPlayer()
  }
}

const passToNextPlayer = () => {
  const active = players.filter(p => !p.isFolded && p.id)
  if (active.length === 0) return
  
  const currentIndex = active.findIndex(p => p.id === currentPlayerId.value)
  const nextIndex = (currentIndex + 1) % active.length
  gameState.currentPlayerId = active[nextIndex].id
  
  console.log('🔄 Ход передан:', players.find(p => p.id === gameState.currentPlayerId)?.name)
}

// 🎯 МЕТОДЫ ДЛЯ СТАВОК
const getCurrentBet = () => {
  // Максимальная ставка среди всех игроков
  return Math.max(...players.map(p => p.currentBet), gameState.baseBet)
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
const currentRaiseAmount = ref(0)
const minBet = computed(() => getCurrentBet() + gameState.baseBet)
const maxBet = computed(() => {
  const player = players.find(p => p.id === currentPlayerId.value)
  return player ? player.balance : 0
})

const openRaiseModal = (player) => {
  currentRaiseAmount.value = minBet.value
  raiseModal.value = true
}

const confirmRaise = () => {
  const player = players.find(p => p.id === currentPlayerId.value)
  if (player && player.balance >= currentRaiseAmount.value) {
    player.currentBet += currentRaiseAmount.value
    player.balance -= currentRaiseAmount.value
    gameState.pot += currentRaiseAmount.value
    console.log('✅ Повышение ставки:', currentRaiseAmount.value)
    raiseModal.value = false
    passToNextPlayer()
  }
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