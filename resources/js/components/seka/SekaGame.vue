<template>
  <div class="seka-game" :class="{ 'mobile': isMobile }">
    <!-- Система готовности -->
    <ReadyCheck 
      v-if="gameState.status === 'waiting'"
      :players="players"
      :is-active="gameState.status === 'waiting'"
      :time-remaining="readyCheck.timeRemaining"
      @player-ready="handlePlayerReady"
      @player-cancel-ready="handlePlayerCancelReady"
      @timeout="handleReadyTimeout"
    />

    <!-- Заголовок и информация -->
    <div class="game-header">
      <h1>🎴 SEKA</h1>
      <div class="game-meta">
        <div class="meta-item">Банк: <strong>{{ pot }} 🪙</strong></div>
        <div class="meta-item">Раунд: <strong>{{ currentRound }}</strong></div>
        <div class="meta-item">Дилер: <strong>{{ getDealer().name }}</strong></div>
        <div class="meta-item" v-if="gameState.status === 'waiting'">
          Статус: <strong class="waiting-status">⏳ Ожидание игроков</strong>
        </div>
        <div class="meta-item" v-else-if="gameState.status === 'active'">
          Статус: <strong class="active-status">🎯 Игра идет</strong>
        </div>
      </div>
    </div>

    <!-- Игровой стол -->
    <div class="game-table">
      <!-- Игрок 1 (верхний левый) -->
      <div class="player-seat seat-1" :class="getPlayerClasses(1)">
        <PlayerSpot 
          :player="getPlayer(1)"
          :cards="getPlayerCards(1)"
          :is-current-turn="currentPlayerId === 1 && gameState.status === 'active'"
          :is-dealer="dealerId === 1"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Игрок 2 (верхний центр) -->
      <div class="player-seat seat-2" :class="getPlayerClasses(2)">
        <PlayerSpot 
          :player="getPlayer(2)"
          :cards="getPlayerCards(2)"
          :is-current-turn="currentPlayerId === 2 && gameState.status === 'active'"
          :is-dealer="dealerId === 2"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Игрок 3 (верхний правый) -->
      <div class="player-seat seat-3" :class="getPlayerClasses(3)">
        <PlayerSpot 
          :player="getPlayer(3)"
          :cards="getPlayerCards(3)"
          :is-current-turn="currentPlayerId === 3 && gameState.status === 'active'"
          :is-dealer="dealerId === 3"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Банк в центре -->
      <div class="pot-display">
        <div class="pot-amount">{{ pot }} 🪙</div>
        <div class="pot-label">Банк</div>
        
        <!-- Индикатор состояния игры -->
        <div class="game-status-indicator" :class="gameState.status">
          {{ getGameStatusText() }}
        </div>
      </div>

      <!-- Игрок 4 (нижний правый) -->
      <div class="player-seat seat-4" :class="getPlayerClasses(4)">
        <PlayerSpot 
          :player="getPlayer(4)"
          :cards="getPlayerCards(4)"
          :is-current-turn="currentPlayerId === 4 && gameState.status === 'active'"
          :is-dealer="dealerId === 4"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Игрок 5 (нижний центр) -->
      <div class="player-seat seat-5" :class="getPlayerClasses(5)">
        <PlayerSpot 
          :player="getPlayer(5)"
          :cards="getPlayerCards(5)"
          :is-current-turn="currentPlayerId === 5 && gameState.status === 'active'"
          :is-dealer="dealerId === 5"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Игрок 6 (нижний левый) -->
      <div class="player-seat seat-6" :class="getPlayerClasses(6)">
        <PlayerSpot 
          :player="getPlayer(6)"
          :cards="getPlayerCards(6)"
          :is-current-turn="currentPlayerId === 6 && gameState.status === 'active'"
          :is-dealer="dealerId === 6"
          @player-action="handlePlayerAction"
        />
      </div>
    </div>

    <!-- Мобильная панель действий -->
    <MobileActionPanel 
      v-if="isMobile && isMyTurn && gameState.status === 'active'"
      :player="currentPlayer"
      :is-visible="showMobileActions"
      @action="takeAction"
      @close="showMobileActions = false"
    />

    <!-- Дебаг информация -->
    <DebugPanel 
      v-if="showDebug" 
      :game-state="gameState"
      @test-action="handleTestAction"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import PlayerSpot from './components/PlayerSpot.vue'
import MobileActionPanel from './components/MobileActionPanel.vue'
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

// 🎯 ИНИЦИАЛИЗАЦИЯ ИГРОКОВ С СИСТЕМОЙ ГОТОВНОСТИ
const players = reactive([
  { 
    id: 1, 
    name: 'Вы', 
    balance: 1000, 
    currentBet: 0, 
    isFolded: false, 
    isDark: false, 
    isReady: false, // 🆕 Готовность
    readyTimeRemaining: 30, // 🆕 Таймер готовности
    cards: [],
    lastAction: '',
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
    cards: [],
    lastAction: '',
    position: 2
  },
  { 
    id: 3, 
    name: 'Мария', 
    balance: 1000, 
    currentBet: 0, 
    isFolded: false, 
    isDark: false, 
    isReady: true, // 🆕 Уже готов
    readyTimeRemaining: 15,
    cards: [],
    lastAction: '',
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
    cards: [],
    lastAction: '',
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
    cards: [],
    lastAction: '',
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
    cards: [],
    lastAction: '',
    position: 6
  }
])

// 🎯 СОСТОЯНИЕ ИГРЫ С СИСТЕМОЙ ГОТОВНОСТИ
const gameState = reactive({
  pot: 0,
  currentRound: 1,
  currentPlayerId: 1,
  dealerId: 1,
  baseBet: 50,
  status: 'waiting' // 🆕 waiting, active, finished
})

// 🆕 СИСТЕМА ГОТОВНОСТИ
const readyCheck = reactive({
  timeRemaining: 30,
  timer: null,
  canStart: false
})

const showDebug = ref(true)
const isMobile = ref(false)
const showMobileActions = ref(false)

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
const pot = computed(() => gameState.pot)
const currentRound = computed(() => gameState.currentRound)
const currentPlayerId = computed(() => gameState.currentPlayerId)
const dealerId = computed(() => gameState.dealerId)
const baseBet = computed(() => gameState.baseBet)

const currentPlayer = computed(() => players.find(p => p.id === currentPlayerId.value))
const isMyTurn = computed(() => currentPlayerId.value === 1 && gameState.status === 'active')
const activePlayers = computed(() => players.filter(p => !p.isFolded))

// 🆕 Готовые игроки
const readyPlayers = computed(() => players.filter(p => p.isReady && p.id))
const readyCount = computed(() => readyPlayers.value.length)

// 🎯 МЕТОДЫ
const getPlayer = (id) => {
  const player = players.find(p => p.id === id)
  if (player) return player
  
  return { 
    id: null, 
    name: 'Свободно', 
    balance: 0, 
    currentBet: 0, 
    isFolded: true, 
    isDark: false, 
    isReady: false,
    readyTimeRemaining: 0,
    cards: [], 
    lastAction: '',
    position: id
  }
}

const getPlayerCards = (playerId) => {
  const player = getPlayer(playerId)
  return player.cards || []
}

const getDealer = () => players.find(p => p.id === dealerId.value) || players[0]

const getPlayerClasses = (seatId) => ({
  'occupied': getPlayer(seatId).name !== 'Свободно',
  'empty': getPlayer(seatId).name === 'Свободно',
  'current': currentPlayerId.value === seatId && gameState.status === 'active',
  'dealer': dealerId.value === seatId
})

// 🆕 ОБРАБОТЧИКИ ГОТОВНОСТИ
const handlePlayerReady = (playerId) => {
  const player = players.find(p => p.id === playerId)
  if (player) {
    player.isReady = true
    console.log(`✅ Игрок ${player.name} готов`)
    
    // Проверяем можно ли начать игру
    checkGameStart()
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
  
  // Автоматически отмечаем готовыми всех активных игроков
  players.forEach(player => {
    if (player.id && !player.isFolded) {
      player.isReady = true
    }
  })
  
  // Запускаем игру
  startGame()
}

// 🆕 ПРОВЕРКА СТАРТА ИГРЫ
const checkGameStart = () => {
  if (readyCount.value >= 2 && gameState.status === 'waiting') {
    console.log('🚀 Достаточно игроков готово, запускаем игру...')
    startGame()
  }
}

// 🆕 ЗАПУСК ИГРЫ
const startGame = () => {
  gameState.status = 'active'
  console.log('🎮 Игра началась!')
  
  // Раздаем карты
  dealCards()
  
  // Останавливаем таймер готовности
  if (readyCheck.timer) {
    clearInterval(readyCheck.timer)
  }
}

// 🆕 РАЗДАЧА КАРТ
const dealCards = () => {
  console.log('🃏 Раздаем карты...')
  
  players.forEach(player => {
    if (player.id) {
      player.cards = createTestCards()
      // Текущий игрок видит свои карты
      if (player.id === 1) {
        player.cards.forEach(card => card.isVisible = true)
      }
    }
  })
  
  // Устанавливаем первого игрока
  gameState.currentPlayerId = 2 // Игрок после дилера начинает
}

const handlePlayerAction = (action) => {
  if (currentPlayerId.value === 1 && gameState.status === 'active') {
    takeAction(action)
  }
}

const takeAction = (action) => {
  console.log('🎯 Действие:', action)
  
  const player = currentPlayer.value
  if (!player) return

  player.lastAction = action

  // Простая логика действий для тестирования
  switch(action) {
    case 'check':
      console.log('✅ Пропуск хода')
      break
    case 'call':
      const callAmount = 50
      player.currentBet += callAmount
      player.balance -= callAmount
      gameState.pot += callAmount
      console.log('✅ Поддержка ставки:', callAmount)
      break
    case 'raise':
      const raiseAmount = 100
      player.currentBet += raiseAmount
      player.balance -= raiseAmount
      gameState.pot += raiseAmount
      console.log('✅ Повышение ставки:', raiseAmount)
      break
    case 'fold':
      player.isFolded = true
      console.log('✅ Пас')
      break
    case 'dark':
      player.isDark = true
      player.cards.forEach(card => card.isVisible = false)
      console.log('✅ Игра в темную')
      break
    case 'open':
      player.isDark = false
      player.cards.forEach(card => card.isVisible = true)
      console.log('✅ Открытие карт')
      break
  }

  // Передаем ход следующему игроку
  if (gameState.status === 'active') {
    passToNextPlayer()
  }
}

const passToNextPlayer = () => {
  const active = activePlayers.value
  if (active.length === 0) return
  
  const currentIndex = active.findIndex(p => p.id === currentPlayerId.value)
  const nextIndex = (currentIndex + 1) % active.length
  gameState.currentPlayerId = active[nextIndex].id
  
  console.log('🔄 Ход передан:', getPlayer(gameState.currentPlayerId).name)
}

// 🆕 ТАЙМЕР ГОТОВНОСТИ
const startReadyTimer = () => {
  readyCheck.timer = setInterval(() => {
    if (readyCheck.timeRemaining > 0) {
      readyCheck.timeRemaining--
      
      // Обновляем таймеры у игроков
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

// 🆕 ТЕКСТ СТАТУСА ИГРЫ
const getGameStatusText = () => {
  switch(gameState.status) {
    case 'waiting':
      return `⏳ Ожидание (${readyCount.value}/6)`
    case 'active':
      return '🎯 Игра идет'
    case 'finished':
      return '🏁 Игра завершена'
    default:
      return '❓ Неизвестно'
  }
}

const handleTestAction = (action) => {
  console.log('🔧 Тестовое действие из DebugPanel:', action)
  
  if (action === 'reset') {
    // Сброс игры для тестирования
    gameState.status = 'waiting'
    readyCheck.timeRemaining = 30
    players.forEach(player => {
      if (player.id) {
        player.isReady = false
        player.readyTimeRemaining = 30
        player.isFolded = false
        player.isDark = false
        player.currentBet = 0
        player.cards = []
      }
    })
    startReadyTimer()
  }
}

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

// 🎯 LIFECYCLE
onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  
  // Запускаем таймер готовности
  startReadyTimer()
  
  console.log('🎮 Игра SEKA инициализирована с системой готовности!')
  console.log('👥 Игроки:', players.map(p => `${p.name} (готов: ${p.isReady})`))
  console.log('🎯 Текущий статус:', gameState.status)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
  if (readyCheck.timer) {
    clearInterval(readyCheck.timer)
  }
})
</script>

<style scoped>
/* Стили остаются в основном те же, добавляем только новые */

.waiting-status {
  color: #f6e05e;
}

.active-status {
  color: #68d391;
}

.game-status-indicator {
  margin-top: 10px;
  padding: 6px 12px;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.game-status-indicator.waiting {
  background: rgba(246, 224, 94, 0.2);
  color: #f6e05e;
  border: 1px solid #f6e05e;
}

.game-status-indicator.active {
  background: rgba(104, 211, 145, 0.2);
  color: #68d391;
  border: 1px solid #68d391;
}

.game-status-indicator.finished {
  background: rgba(160, 174, 192, 0.2);
  color: #a0aec0;
  border: 1px solid #a0aec0;
}

/* Остальные стили из предыдущей версии... */
</style>