<template>
  <div class="seka-game" :class="{ 'mobile': isMobile }">
    <!-- Заголовок и информация -->
    <div class="game-header">
      <h1>🎴 SEKA</h1>
      <div class="game-meta">
        <div class="meta-item">Банк: <strong>{{ pot }} 🪙</strong></div>
        <div class="meta-item">Раунд: <strong>{{ currentRound }}</strong></div>
        <div class="meta-item">Дилер: <strong>{{ getDealer().name }}</strong></div>
      </div>
    </div>

    <!-- Игровой стол -->
    <div class="game-table">
      <!-- Игрок 1 (верхний левый) -->
      <div class="player-seat seat-1" :class="getPlayerClasses(1)">
        <PlayerSpot 
          :player="getPlayer(1)"
          :cards="getPlayerCards(1)"
          :is-current-turn="currentPlayerId === 1"
          :is-dealer="dealerId === 1"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Игрок 2 (верхний центр) -->
      <div class="player-seat seat-2" :class="getPlayerClasses(2)">
        <PlayerSpot 
          :player="getPlayer(2)"
          :cards="getPlayerCards(2)"
          :is-current-turn="currentPlayerId === 2"
          :is-dealer="dealerId === 2"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Игрок 3 (верхний правый) -->
      <div class="player-seat seat-3" :class="getPlayerClasses(3)">
        <PlayerSpot 
          :player="getPlayer(3)"
          :cards="getPlayerCards(3)"
          :is-current-turn="currentPlayerId === 3"
          :is-dealer="dealerId === 3"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Банк в центре -->
      <div class="pot-display">
        <div class="pot-amount">{{ pot }} 🪙</div>
        <div class="pot-label">Банк</div>
      </div>

      <!-- Игрок 4 (нижний правый) -->
      <div class="player-seat seat-4" :class="getPlayerClasses(4)">
        <PlayerSpot 
          :player="getPlayer(4)"
          :cards="getPlayerCards(4)"
          :is-current-turn="currentPlayerId === 4"
          :is-dealer="dealerId === 4"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Игрок 5 (нижний центр) -->
      <div class="player-seat seat-5" :class="getPlayerClasses(5)">
        <PlayerSpot 
          :player="getPlayer(5)"
          :cards="getPlayerCards(5)"
          :is-current-turn="currentPlayerId === 5"
          :is-dealer="dealerId === 5"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Игрок 6 (нижний левый) -->
      <div class="player-seat seat-6" :class="getPlayerClasses(6)">
        <PlayerSpot 
          :player="getPlayer(6)"
          :cards="getPlayerCards(6)"
          :is-current-turn="currentPlayerId === 6"
          :is-dealer="dealerId === 6"
          @player-action="handlePlayerAction"
        />
      </div>
    </div>

    <!-- Мобильная панель действий -->
    <MobileActionPanel 
      v-if="isMobile && isMyTurn"
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

const handleTestAction = (action) => {
  console.log('🔧 Тестовое действие из DebugPanel:', action)
  // Можно добавить специальную логику для тестовых действий
  if (action === 'dark') {
    // Например, принудительно включить темную игру
    currentPlayer.value.isDark = true
    currentPlayer.value.cards.forEach(card => card.isVisible = false)
  }
}

// 🎯 ИНИЦИАЛИЗАЦИЯ ИГРОКОВ С КАРТАМИ
const players = reactive([
  { 
    id: 1, 
    name: 'Вы', 
    balance: 1000, 
    currentBet: 50, 
    isFolded: false, 
    isDark: false, 
    cards: createTestCards().map(card => ({ ...card, isVisible: true })), // Игрок видит свои карты
    lastAction: '' 
  },
  { 
    id: 2, 
    name: 'Алексей', 
    balance: 1000, 
    currentBet: 50, 
    isFolded: false, 
    isDark: false, 
    cards: createTestCards(),
    lastAction: '' 
  },
  { 
    id: 3, 
    name: 'Мария', 
    balance: 1000, 
    currentBet: 50, 
    isFolded: false, 
    isDark: false, 
    cards: createTestCards(),
    lastAction: '' 
  },
  { 
    id: 4, 
    name: 'Дмитрий', 
    balance: 1000, 
    currentBet: 50, 
    isFolded: false, 
    isDark: true, // Тестируем темную игру
    cards: createTestCards().map(card => ({ ...card, isVisible: false })),
    lastAction: '' 
  },
  { 
    id: 5, 
    name: 'Светлана', 
    balance: 1000, 
    currentBet: 50, 
    isFolded: false, 
    isDark: false, 
    cards: createTestCards(),
    lastAction: '' 
  },
  { 
    id: 6, 
    name: 'Игорь', 
    balance: 1000, 
    currentBet: 50, 
    isFolded: true, // Тестируем пас
    isDark: false, 
    cards: createTestCards(),
    lastAction: 'fold' 
  }
])

// 🎯 СОСТОЯНИЕ ИГРЫ
const gameState = reactive({
  pot: 300,
  currentRound: 1,
  currentPlayerId: 2, // Начинает игрок после дилера
  dealerId: 1,
  baseBet: 50
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
const isMyTurn = computed(() => currentPlayerId.value === 1)
const activePlayers = computed(() => players.filter(p => !p.isFolded))

// 🎯 МЕТОДЫ
const getPlayer = (id) => {
  const player = players.find(p => p.id === id)
  if (player) return player
  
  // Возвращаем пустого игрока для свободных мест
  return { 
    id: null, 
    name: 'Свободно', 
    balance: 0, 
    currentBet: 0, 
    isFolded: true, 
    isDark: false, 
    cards: [], 
    lastAction: '' 
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
  'current': currentPlayerId.value === seatId,
  'dealer': dealerId.value === seatId
})

const handlePlayerAction = (action) => {
  if (currentPlayerId.value === 1) {
    takeAction(action)
  }
}

const takeAction = (action) => {
  console.log('🎯 Действие:', action)
  
  const player = currentPlayer.value
  if (!player) return

  // Обновляем последнее действие
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
  passToNextPlayer()
}

const passToNextPlayer = () => {
  const active = activePlayers.value
  if (active.length === 0) return
  
  const currentIndex = active.findIndex(p => p.id === currentPlayerId.value)
  const nextIndex = (currentIndex + 1) % active.length
  gameState.currentPlayerId = active[nextIndex].id
  
  console.log('🔄 Ход передан:', getPlayer(gameState.currentPlayerId).name)
}

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
  if (isMobile.value && isMyTurn.value) {
    showMobileActions.value = true
  }
}

// 🎯 LIFECYCLE
onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  
  console.log('🎮 Игра SEKA инициализирована!')
  console.log('👥 Игроки:', players.map(p => p.name))
  console.log('🎫 Дилер:', getDealer().name)
  console.log('🎯 Текущий ход:', currentPlayer.value.name)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})
</script>

<style scoped>
/* Стили остаются такими же как в предыдущей версии */
.seka-game {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
  color: white;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.game-header {
  text-align: center;
  padding: 20px;
  background: rgba(45, 55, 72, 0.8);
  border-bottom: 2px solid #4a5568;
}

.game-header h1 {
  margin: 0 0 10px 0;
  font-size: 2.5rem;
  color: #68d391;
}

.game-meta {
  display: flex;
  justify-content: center;
  gap: 30px;
  flex-wrap: wrap;
}

.meta-item {
  font-size: 1.1rem;
  color: #e2e8f0;
}

/* Остальные стили из предыдущей версии... */
</style>