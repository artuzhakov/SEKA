<template>
  <div class="game-table" :class="{ 'mobile': isMobile }">
    <!-- Заголовок стола -->
    <div class="table-header">
      <h2>🎴 SEKA</h2>
      <div class="table-stats">
        <span class="bank">🏦 Банк: {{ bank }}</span>
        <span class="round">Раунд: {{ currentRound }}/3</span>
        <span class="dealer">🎫 Дилер: {{ dealerName }}</span>
      </div>
    </div>

    <!-- Игровой стол с фиксированными позициями -->
    <div class="poker-table">
      <!-- Овальный стол -->
      <div class="table-surface">
        <div class="pot-display">
          <div class="pot-amount">{{ bank }} 🪙</div>
          <div class="pot-label">БАНК</div>
        </div>
        
        <!-- Колода для анимаций -->
        <div class="deck-spot" v-if="showDeck">
          <div class="deck" @click="dealCards" title="Раздать карты">
            🃏
          </div>
        </div>
      </div>

      <!-- Фиксированные позиции игроков -->
      <div class="player-positions">
        <!-- Позиция 1: Верхний левый -->
        <div class="player-position pos-1" :class="getPositionClass(1)">
          <PlayerSpot 
            :player="getPlayer(1)"
            :cards="playerCards[1]"
            :is-current-turn="currentPlayerPosition === getPlayerPosition(1)"
            :is-dealer="dealerPosition === 1"
            @player-action="handlePlayerAction"
          />
        </div>

        <!-- Позиция 2: Верхний центр -->
        <div class="player-position pos-2" :class="getPositionClass(2)">
          <PlayerSpot 
            :player="getPlayer(2)"
            :cards="playerCards[2]"
            :is-current-turn="currentPlayerPosition === getPlayerPosition(2)"
            :is-dealer="dealerPosition === 2"
            @player-action="handlePlayerAction"
          />
        </div>

        <!-- Позиция 3: Верхний правый -->
        <div class="player-position pos-3" :class="getPositionClass(3)">
          <PlayerSpot 
            :player="getPlayer(3)"
            :cards="playerCards[3]"
            :is-current-turn="currentPlayerPosition === getPlayerPosition(3)"
            :is-dealer="dealerPosition === 3"
            @player-action="handlePlayerAction"
          />
        </div>

        <!-- Позиция 4: Нижний правый -->
        <div class="player-position pos-4" :class="getPositionClass(4)">
          <PlayerSpot 
            :player="getPlayer(4)"
            :cards="playerCards[4]"
            :is-current-turn="currentPlayerPosition === getPlayerPosition(4)"
            :is-dealer="dealerPosition === 4"
            @player-action="handlePlayerAction"
          />
        </div>

        <!-- Позиция 5: Нижний центр -->
        <div class="player-position pos-5" :class="getPositionClass(5)">
          <PlayerSpot 
            :player="getPlayer(5)"
            :cards="playerCards[5]"
            :is-current-turn="currentPlayerPosition === getPlayerPosition(5)"
            :is-dealer="dealerPosition === 5"
            @player-action="handlePlayerAction"
          />
        </div>

        <!-- Позиция 6: Нижний левый -->
        <div class="player-position pos-6" :class="getPositionClass(6)">
          <PlayerSpot 
            :player="getPlayer(6)"
            :cards="playerCards[6]"
            :is-current-turn="currentPlayerPosition === getPlayerPosition(6)"
            :is-dealer="dealerPosition === 6"
            @player-action="handlePlayerAction"
          />
        </div>
      </div>
    </div>

    <!-- Мобильная панель действий -->
    <MobileActionPanel 
      v-if="isMobile && isMyTurn"
      :player="currentPlayer"
      @action="handleGlobalAction"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import PlayerSpot from './PlayerSpot.vue'
import MobileActionPanel from './MobileActionPanel.vue'

const props = defineProps({
  players: {
    type: Array,
    default: () => []
  },
  playerCards: {
    type: Object,
    default: () => ({})
  },
  currentPlayerPosition: {
    type: Number,
    default: 0
  },
  bank: {
    type: Number,
    default: 0
  },
  currentRound: {
    type: Number,
    default: 1
  },
  gameStatus: {
    type: String,
    default: 'waiting'
  },
  dealerPosition: {
    type: Number,
    default: 1
  }
})

const emit = defineEmits(['deal-cards', 'player-action'])

// 🎯 РЕАКТИВНОЕ СОСТОЯНИЕ
const isMobile = ref(false)
const showDeck = ref(true)

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
const dealerName = computed(() => {
  const dealer = props.players.find(p => p.position === props.dealerPosition)
  return dealer?.name || 'Нет дилера'
})

const currentPlayer = computed(() => {
  return props.players.find(p => p.position === props.currentPlayerPosition)
})

const isMyTurn = computed(() => {
  return currentPlayer.value?.id === 1 // Предполагаем что ID 1 - это текущий пользователь
})

// 🎯 МЕТОДЫ
const getPlayer = (position) => {
  return props.players.find(p => p.position === position) || {
    id: null,
    name: 'Свободно',
    position: position,
    balance: 0,
    currentBet: 0,
    isFolded: true,
    isDark: false,
    lastAction: ''
  }
}

const getPlayerPosition = (seatNumber) => {
  return seatNumber // Позиция соответствует номеру места
}

const getPositionClass = (position) => ({
  'occupied': getPlayer(position).name !== 'Свободно',
  'empty': getPlayer(position).name === 'Свободно',
  'current': props.currentPlayerPosition === position,
  'dealer': props.dealerPosition === position
})

const handlePlayerAction = (action) => {
  emit('player-action', action)
}

const handleGlobalAction = (action) => {
  if (isMyTurn.value) {
    emit('player-action', action)
  }
}

const dealCards = () => {
  if (props.gameStatus === 'waiting') {
    emit('deal-cards')
    showDeck.value = false
  }
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
.game-table {
  background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
  color: white;
  min-height: 100vh;
  padding: 20px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Заголовок */
.table-header {
  text-align: center;
  margin-bottom: 30px;
  padding: 20px;
  background: rgba(45, 55, 72, 0.8);
  border-radius: 10px;
  border: 1px solid #4a5568;
}

.table-header h2 {
  margin: 0 0 15px 0;
  font-size: 2.5rem;
  color: #68d391;
}

.table-stats {
  display: flex;
  justify-content: center;
  gap: 30px;
  flex-wrap: wrap;
  font-size: 1.1rem;
}

.table-stats span {
  padding: 8px 16px;
  background: rgba(74, 85, 104, 0.6);
  border-radius: 8px;
  border: 1px solid #718096;
}

/* Игровой стол */
.poker-table {
  position: relative;
  max-width: 1000px;
  height: 600px;
  margin: 0 auto;
  background: #2d5016;
  border-radius: 50%;
  border: 15px solid #8b4513;
  box-shadow: 
    0 0 50px rgba(0, 0, 0, 0.5),
    inset 0 0 50px rgba(0, 0, 0, 0.3);
}

.table-surface {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 200px;
  height: 120px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

/* Банк */
.pot-display {
  text-align: center;
  background: rgba(214, 158, 46, 0.9);
  padding: 12px 20px;
  border-radius: 10px;
  border: 2px solid #f6e05e;
  min-width: 120px;
}

.pot-amount {
  font-size: 1.5rem;
  font-weight: bold;
  color: #1a202c;
  margin-bottom: 2px;
}

.pot-label {
  font-size: 0.8rem;
  color: #1a202c;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: bold;
}

/* Колода */
.deck-spot {
  position: absolute;
  top: -40px;
  left: 50%;
  transform: translateX(-50%);
}

.deck {
  width: 60px;
  height: 80px;
  background: linear-gradient(45deg, #1a202c, #4a5568);
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.deck:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
}

/* Позиции игроков */
.player-positions {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.player-position {
  position: absolute;
  transition: all 0.3s ease;
  min-width: 180px;
}

.player-position.occupied {
  opacity: 1;
}

.player-position.empty {
  opacity: 0.4;
}

.player-position.empty::before {
  content: "+";
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 3rem;
  color: #718096;
  z-index: 1;
}

.player-position.current {
  transform: scale(1.1);
  z-index: 10;
}

.player-position.dealer::after {
  content: "🎫";
  position: absolute;
  top: -10px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 1.5rem;
  z-index: 5;
}

/* Фиксированные позиции вокруг овала */
.pos-1 { /* Верхний левый */
  top: 10%;
  left: 10%;
  transform: translate(-50%, 0);
}

.pos-2 { /* Верхний центр */
  top: 5%;
  left: 50%;
  transform: translate(-50%, 0);
}

.pos-3 { /* Верхний правый */
  top: 10%;
  right: 10%;
  transform: translate(50%, 0);
}

.pos-4 { /* Нижний правый */
  bottom: 10%;
  right: 10%;
  transform: translate(50%, 0);
}

.pos-5 { /* Нижний центр */
  bottom: 5%;
  left: 50%;
  transform: translate(-50%, 0);
}

.pos-6 { /* Нижний левый */
  bottom: 10%;
  left: 10%;
  transform: translate(-50%, 0);
}

/* Мобильная версия */
.game-table.mobile .poker-table {
  height: auto;
  border-radius: 20px;
  min-height: 500px;
  background: #2d5016;
}

.game-table.mobile .player-positions {
  position: static;
  display: grid;
  grid-template-columns: 1fr;
  gap: 15px;
  padding: 20px;
}

.game-table.mobile .player-position {
  position: static;
  transform: none !important;
  min-width: auto;
}

.game-table.mobile .table-surface {
  position: static;
  transform: none;
  margin: 20px auto;
}

/* Адаптивность */
@media (max-width: 768px) {
  .game-table {
    padding: 10px;
  }
  
  .table-header {
    padding: 15px;
  }
  
  .table-header h2 {
    font-size: 2rem;
  }
  
  .table-stats {
    flex-direction: column;
    gap: 10px;
  }
  
  .poker-table {
    border-width: 8px;
  }
}

@media (max-width: 480px) {
  .table-stats span {
    font-size: 1rem;
    padding: 6px 12px;
  }
  
  .pot-display {
    padding: 10px 15px;
  }
  
  .pot-amount {
    font-size: 1.2rem;
  }
}
</style>