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
        <PlayerDisplay 
          :player="getPlayer(1)"
          :is-current="currentPlayerId === 1"
          :is-dealer="dealerId === 1"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Игрок 2 (верхний центр) -->
      <div class="player-seat seat-2" :class="getPlayerClasses(2)">
        <PlayerDisplay 
          :player="getPlayer(2)"
          :is-current="currentPlayerId === 2"
          :is-dealer="dealerId === 2"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Игрок 3 (верхний правый) -->
      <div class="player-seat seat-3" :class="getPlayerClasses(3)">
        <PlayerDisplay 
          :player="getPlayer(3)"
          :is-current="currentPlayerId === 3"
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
        <PlayerDisplay 
          :player="getPlayer(4)"
          :is-current="currentPlayerId === 4"
          :is-dealer="dealerId === 4"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Игрок 5 (нижний центр) -->
      <div class="player-seat seat-5" :class="getPlayerClasses(5)">
        <PlayerDisplay 
          :player="getPlayer(5)"
          :is-current="currentPlayerId === 5"
          :is-dealer="dealerId === 5"
          @player-action="handlePlayerAction"
        />
      </div>

      <!-- Игрок 6 (нижний левый) -->
      <div class="player-seat seat-6" :class="getPlayerClasses(6)">
        <PlayerDisplay 
          :player="getPlayer(6)"
          :is-current="currentPlayerId === 6"
          :is-dealer="dealerId === 6"
          @player-action="handlePlayerAction"
        />
      </div>
    </div>

    <!-- Мобильная панель действий -->
    <ActionPanel 
      v-if="isMobile && isMyTurn"
      :player="currentPlayer"
      :available-actions="availableActions"
      @action="takeAction"
    />

    <!-- Дебаг информация -->
    <DebugPanel v-if="showDebug" :game-state="gameState" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import PlayerDisplay from '../seka/components/PlayerDisplay.vue'
import ActionPanel from '../seka/components/ActionPanel.vue'
import DebugPanel from '../seka/components/DebugPanel.vue'

// 🎯 РЕАКТИВНОЕ СОСТОЯНИЕ
const gameState = reactive({
  pot: 0,
  currentRound: 1,
  currentPlayerId: 2, // Начинает после дилера
  dealerId: 1,
  baseBet: 50
})

const players = reactive([
  { id: 1, name: 'Вы', balance: 1000, currentBet: 0, isFolded: false, isDark: false, cards: [], lastAction: '' },
  { id: 2, name: 'Алексей', balance: 1000, currentBet: 0, isFolded: false, isDark: false, cards: [], lastAction: '' },
  { id: 3, name: 'Мария', balance: 1000, currentBet: 0, isFolded: false, isDark: false, cards: [], lastAction: '' },
  { id: 4, name: 'Дмитрий', balance: 1000, currentBet: 0, isFolded: false, isDark: false, cards: [], lastAction: '' },
  { id: 5, name: 'Светлана', balance: 1000, currentBet: 0, isFolded: false, isDark: false, cards: [], lastAction: '' },
  { id: 6, name: 'Игорь', balance: 1000, currentBet: 0, isFolded: false, isDark: false, cards: [], lastAction: '' }
])

const showDebug = ref(true)
const isMobile = ref(false)

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
const getPlayer = (id) => players.find(p => p.id === id) || { 
  id, name: 'Свободно', balance: 0, currentBet: 0, isFolded: true, isDark: false, cards: [], lastAction: '' 
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
  console.log('Действие:', action)
  // Логика действий будет в следующих компонентах
}

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

// 🎯 LIFECYCLE
onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})
</script>

<style scoped>
.seka-game {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
  color: white;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Заголовок */
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

/* Игровой стол - Десктоп версия */
.game-table {
  display: grid;
  grid-template-areas: 
    "player1 player2 player3"
    "player6 pot player4"
    "player6 player5 player4";
  grid-template-columns: 1fr auto 1fr;
  grid-template-rows: 1fr auto 1fr;
  gap: 30px;
  padding: 40px;
  max-width: 1200px;
  margin: 0 auto;
  min-height: 70vh;
  align-items: center;
}

.player-seat {
  min-width: 180px;
  transition: all 0.3s ease;
}

.player-seat.occupied {
  opacity: 1;
}

.player-seat.empty {
  opacity: 0.6;
}

.player-seat.current {
  transform: scale(1.05);
}

.player-seat.dealer::before {
  content: "🎫";
  position: absolute;
  top: -10px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 1.5rem;
}

/* Позиции игроков */
.seat-1 { grid-area: player1; justify-self: start; align-self: end; }
.seat-2 { grid-area: player2; justify-self: center; align-self: end; }
.seat-3 { grid-area: player3; justify-self: end; align-self: end; }
.seat-4 { grid-area: player4; justify-self: end; align-self: start; }
.seat-5 { grid-area: player5; justify-self: center; align-self: start; }
.seat-6 { grid-area: player6; justify-self: start; align-self: start; }

/* Банк */
.pot-display {
  grid-area: pot;
  text-align: center;
  background: rgba(74, 85, 104, 0.8);
  padding: 20px 30px;
  border-radius: 15px;
  border: 3px solid #d69e2e;
  min-width: 150px;
}

.pot-amount {
  font-size: 2rem;
  font-weight: bold;
  color: #f6e05e;
  margin-bottom: 5px;
}

.pot-label {
  font-size: 1rem;
  color: #e2e8f0;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Мобильная версия */
.seka-game.mobile .game-table {
  grid-template-areas: 
    "player1"
    "player2" 
    "player3"
    "pot"
    "player4"
    "player5"
    "player6";
  grid-template-columns: 1fr;
  grid-template-rows: repeat(7, auto);
  gap: 20px;
  padding: 20px;
}

.seka-game.mobile .player-seat {
  min-width: auto;
  width: 100%;
}

/* Адаптивность */
@media (max-width: 768px) {
  .game-meta {
    flex-direction: column;
    gap: 10px;
  }
  
  .meta-item {
    font-size: 1rem;
  }
  
  .pot-display {
    padding: 15px 20px;
  }
  
  .pot-amount {
    font-size: 1.5rem;
  }
}

@media (max-width: 480px) {
  .game-header {
    padding: 15px;
  }
  
  .game-header h1 {
    font-size: 2rem;
  }
  
  .game-table {
    padding: 15px;
    gap: 15px;
  }
}
</style>