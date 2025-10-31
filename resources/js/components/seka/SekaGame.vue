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

    <!-- Заголовок игры -->
    <div class="game-header">
      <h1>🎴 SEKA</h1>
      <div class="game-meta">
        <div class="meta-item">Банк: <strong>{{ pot }} 🪙</strong></div>
        <div class="meta-item">Раунд: <strong>{{ currentRound }}</strong></div>
        <div class="meta-item">Дилер: <strong>{{ getDealer().name }}</strong></div>
        <div class="meta-item" v-if="gameState.status === 'waiting'">
          Готовы: <strong class="waiting-status">{{ readyCount }}/6</strong>
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
      @deal-cards="startGame"
    />

    <!-- Дебаг панель -->
    <DebugPanel 
      v-if="showDebug" 
      :game-state="gameState"
      @test-action="handleTestAction"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
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
    isReady: true,
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
  timeRemaining: 30,
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
const readyCount = computed(() => readyPlayers.value.length)

const getDealer = () => players.find(p => p.id === dealerId.value) || players[0]

// 🎯 СИСТЕМА ГОТОВНОСТИ
const handlePlayerReady = (playerId) => {
  const player = players.find(p => p.id === playerId)
  if (player && gameState.status === 'waiting') {
    player.isReady = true
    console.log(`✅ Игрок ${player.name} готов`)
    
    if (readyCount.value >= 2) {
      setTimeout(() => {
        if (gameState.status === 'waiting' && readyCount.value >= 2) {
          startGame()
        }
      }, 2000)
    }
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
  players.forEach(player => {
    if (player.id && !player.isFolded) {
      player.isReady = true
    }
  })
  startGame()
}

// 🎯 ЗАПУСК ИГРЫ
const startGame = () => {
  if (readyCount.value < 2) {
    console.log('❌ Недостаточно игроков для старта')
    return
  }

  console.log('🚀 Запускаем игру...')
  
  players.forEach(player => {
    if (player.id && !player.isReady) {
      console.log(`👋 Игрок ${player.name} выкинут из игры`)
      player.id = null
      player.name = 'Свободно'
    }
  })

  gameState.status = 'active'
  
  if (readyCheck.timer) {
    clearInterval(readyCheck.timer)
  }
  
  // Раздаем карты
  dealCards()
}

// 🎯 РАЗДАЧА КАРТ
const dealCards = () => {
  console.log('🃏 Начинаем раздачу карты...')
  
  players.forEach((player, index) => {
    if (player.id) {
      playerCards[player.id] = createTestCards()
      if (player.id === 1) {
        playerCards[player.id].forEach(card => card.isVisible = true)
      }
      console.log(`🎴 Игрок ${player.name} получил карты`)
    }
  })

  setTimeout(() => {
    gameState.currentPlayerId = 2
    console.log('🎯 Игра началась! Первый ход у:', players.find(p => p.id === 2)?.name)
  }, 1000)
}

const handlePlayerAction = (action) => {
  if (currentPlayerId.value === 1 && gameState.status === 'active') {
    takeAction(action)
  }
}

const takeAction = (action) => {
  console.log('🎯 Действие:', action)
  
  const player = players.find(p => p.id === currentPlayerId.value)
  if (!player) return

  player.lastAction = action

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
      if (playerCards[player.id]) {
        playerCards[player.id].forEach(card => card.isVisible = false)
      }
      console.log('✅ Игра в темную')
      break
    case 'open':
      player.isDark = false
      if (playerCards[player.id]) {
        playerCards[player.id].forEach(card => card.isVisible = true)
      }
      console.log('✅ Открытие карт')
      break
  }

  if (gameState.status === 'active') {
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

// 🎯 LIFECYCLE
onMounted(() => {
  checkDevice()
  window.addEventListener('resize', checkDevice)
  startReadyTimer()
  console.log('🎮 SEKA инициализирована!')
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