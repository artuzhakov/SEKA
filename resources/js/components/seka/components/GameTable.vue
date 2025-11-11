<template>
  <div class="game-table" :class="{ 'mobile': isMobile, 'desktop': !isMobile }">
    <!-- Десктопная версия -->
    <div v-if="!isMobile" class="desktop-table">
      <div class="poker-table-container">
        <div class="poker-table">
          <div class="table-outer-ring"></div>
          <div class="table-inner-surface">
            
            <!-- Центр стола -->
            <div class="table-center">
              <div class="bank-display">
                <div class="bank-icon">💰</div>
                <div class="bank-amount">{{ bank }}</div>
                <div class="bank-label">БАНК</div>
              </div>

              <!-- Колода карт -->
              <div class="deck-display" v-if="gameStatus === 'active'">
                <div class="deck-cards">
                  <div class="card-back" v-for="n in 3" :key="n" 
                      :style="{ transform: `translate(${n * 2}px, ${n * 1}px)` }"></div>
                </div>
                <div class="deck-count">36</div>
              </div>
            </div>

            <!-- Игроки вокруг стола -->
            <div v-for="position in 6" :key="position" 
                 class="player-position" 
                 :class="[`pos-${position}`, getPositionClass(position)]">
                <CompactPlayerSlot 
                  :player="getPlayer(position)"
                  :cards="getPlayerCards(position)"
                  :is-current-turn="isCurrentTurn(position)"
                  :is-dealer="isDealer(position)"
                  :show-ready="gameStatus === 'waiting'"
                  :show-actions="isCurrentTurn(position) && gameStatus === 'active'"
                  :current-round="currentRound"
                  :dealer-position="getDealerPosition()"
                  :current-bet="getCurrentBet()"
                  :players="players"
                  @player-action="(action) => {
                    console.log('🎯 [GameTable] Player action received:', action, 'from player:', getPlayer(position).name)
                    emit('player-action', action)
                  }"
                  @player-ready="(playerId) => {
                    console.log('2. GameTable: event received', playerId)
                    emit('player-ready', playerId)
                  }"
                />
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- Мобильная версия -->
    <div v-else class="mobile-table">
      <!-- Верхняя панель -->
      <div class="mobile-header">
        <div class="mobile-title">
          <h1>🎴 SEKA</h1>
          <div class="mobile-status" :class="gameStatus">
            {{ getGameStatusText() }}
          </div>
        </div>
        <div class="mobile-game-info">
          <div class="info-item">
            <span class="label">Банк:</span>
            <span class="value">{{ bank }}🪙</span>
          </div>
          <div class="info-item">
            <span class="label">Раунд:</span>
            <span class="value">{{ currentRound }}/3</span>
          </div>
        </div>
      </div>

      <!-- Игровой стол -->
      <div class="mobile-players-ring">
        
        <!-- Игроки по кругу -->
        <div v-for="position in 6" :key="position" 
             class="mobile-player-spot" 
             :class="[`spot-${position}`, getMobilePositionClass(position)]">
          <div class="mobile-player-avatar">
            {{ getPlayerInitials(position) }}
            <div v-if="isDealer(position)" class="dealer-indicator">🎫</div>
            <div v-if="isCurrentTurn(position) && gameStatus === 'active'" 
                class="turn-indicator">🎯</div>
          </div>
          <div class="mobile-player-info" v-if="position === 1 || position === 5">
            <div class="player-name">{{ getPlayer(position).name }}</div>
            <div class="player-balance">{{ getPlayer(position).balance }}🪙</div>
          </div>
        </div>

        <!-- Центр стола -->
        <div class="mobile-table-center">
          <div class="mobile-bank">
            <div class="bank-icon">💰</div>
            <div class="bank-amount">{{ bank }}</div>
            <div class="bank-label">БАНК</div>
          </div>
          
          <div class="mobile-deck" v-if="gameStatus === 'active'">
            <div class="deck-stack">
              <div class="card-back"></div>
              <div class="card-back"></div>
            </div>
            <div class="deck-count">36</div>
          </div>
        </div>

      </div>

      <!-- Нижняя панель действий -->
      <div class="mobile-action-panel">
        
        <!-- Карты текущего игрока -->
        <div class="player-cards-section" v-if="gameStatus === 'active' && getPlayer(1).id">
          <div class="section-title">Ваши карты:</div>
          <div class="mobile-cards-container">
            <div v-for="(card, index) in getPlayerCards(1)" :key="index" 
                class="mobile-card">
              <div class="card-front" v-if="card.isVisible">
                <div class="card-rank">{{ card.rank }}</div>
                <div class="card-suit">{{ card.suit }}</div>
              </div>
              <div v-else class="card-back-mobile"></div>
            </div>
          </div>
        </div>

        <!-- Кнопки действий -->
        <div class="actions-section" v-if="isMyTurn && gameStatus === 'active'">
          <div class="section-title">Ваш ход:</div>
          <div class="mobile-actions-grid">
            <button v-for="action in getAvailableActions()" 
                    :key="action"
                    class="mobile-action-btn"
                    :class="action"
                    @click="handleAction(action)">
              <span class="action-icon">{{ getActionIcon(action) }}</span>
              <span class="action-text">{{ getActionText(action) }}</span>
            </button>
          </div>
        </div>

        <!-- Информация о ходе -->
        <div class="turn-info" v-else-if="gameStatus === 'active'">
          <div class="current-turn">
            <div class="turn-label">Сейчас ходит:</div>
            <div class="current-player">
              {{ getCurrentPlayer().name }}
              <span class="turn-badge">🎯</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import CompactPlayerSlot from './CompactPlayerSlot.vue'

const props = defineProps({
  players: Array,
  playerCards: Object,
  currentPlayerId: Number,
  bank: Number,
  currentRound: Number,
  gameStatus: String,
  dealerId: Number,
  isMobile: Boolean
})

const emit = defineEmits(['player-action', 'player-ready', 'deal-cards'])

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
const isMyTurn = computed(() => {
  // Для мобильной версии считаем что игрок 1 - это текущий пользователь
  const myPlayer = props.players.find(p => p.position === 1)
  return myPlayer && myPlayer.id === props.currentPlayerId
})

// 🎯 МЕТОДЫ
const getPlayer = (position) => {
  const player = props.players.find(p => p.position === position)
  return player || { 
    id: null, 
    name: 'Свободно', 
    balance: 0, 
    position: position
  }
}

const getDealerPosition = () => {
  const dealer = props.players.find(p => p.id === props.dealerId)
  const position = dealer?.position || 1
  
  console.log('🎯 [getDealerPosition] Расчет:', {
    dealerId: props.dealerId,
    dealerName: dealer?.name,
    dealerPosition: position
  })
  
  return position
}

const getCurrentBet = () => {
  return Math.max(...props.players.map(p => p.currentBet), 50)
}

const getPlayerCards = (position) => {
  const player = getPlayer(position)
  return props.playerCards[player.id] || []
}

const getCurrentPlayer = () => getPlayer(props.players.findIndex(p => p.id === props.currentPlayerId) + 1)

// В GameTable.vue - метод isCurrentTurn
const isCurrentTurn = (position) => {
  const player = getPlayer(position)
  const result = player.id === props.currentPlayerId && player.id !== null
  
  console.log(`🎯 [GameTable] isCurrentTurn(${position}):`, result, 
    'player:', player.name, 
    'playerId:', player.id, 
    'currentPlayerId:', props.currentPlayerId,
    'props.currentPlayerId:', props.currentPlayerId)
    
  return result
}

const isDealer = (position) => {
  const player = getPlayer(position)
  return player.id === props.dealerId
}

const getPositionClass = (position) => ({
  'occupied': getPlayer(position).name !== 'Свободно',
  'empty': getPlayer(position).name === 'Свободно',
  'current': isCurrentTurn(position),
  'dealer': isDealer(position)
})

const getMobilePositionClass = (position) => ({
  'occupied': getPlayer(position).name !== 'Свободно',
  'empty': getPlayer(position).name === 'Свободно',
  'current': isCurrentTurn(position),
  'dealer': isDealer(position)
})

const getPlayerInitials = (position) => {
  const player = getPlayer(position)
  if (player.name === 'Свободно') return '+'
  return player.name.charAt(0)
}

const getGameStatusText = () => {
  switch(props.gameStatus) {
    case 'waiting': return '⏳ Ожидание'
    case 'active': return '🎯 Игра идет'
    default: return '❓ Неизвестно'
  }
}

const getAvailableActions = () => {
  if (!isMyTurn.value) return []
  return ['check', 'call', 'raise', 'fold', 'dark']
}

const getActionText = (action) => {
  const actions = {
    'check': 'Пропуск',
    'call': 'Поддержать', 
    'raise': 'Повысить',
    'fold': 'Пас',
    'dark': 'Темная',
    'open': 'Открыть',
    'reveal': 'Вскрыться'
  }
  return actions[action] || action
}

const getActionIcon = (action) => {
  const icons = {
    'check': '➡️',
    'call': '✅', 
    'raise': '📈',
    'fold': '🏳️',
    'dark': '🕶️'
  }
  return icons[action] || '🎯'
}

const handleAction = (action) => {
  console.log('📱 [Mobile] Action clicked:', action)
  console.log('📱 [Mobile] isMyTurn:', isMyTurn.value)
  console.log('📱 [Mobile] gameStatus:', props.gameStatus)
  
  if (isMyTurn.value && props.gameStatus === 'active') {
    console.log('📱 [Mobile] Emitting action:', action)
    emit('player-action', action)
  } else {
    console.log('📱 [Mobile] Action ignored - not your turn or game not active')
  }
}

const handlePlayerAction = (action) => {
  emit('player-action', action)
}
</script>

<style scoped>
/* 🎴 ДЕСКТОПНАЯ ВЕРСИЯ */
.desktop-table {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
}

.poker-table-container {
  position: relative;
  width: 100%;
  height: 70vh;
  min-height: 600px;
}

.poker-table {
  position: relative;
  width: 100%;
  height: 100%;
  background: linear-gradient(145deg, #1a5a1a, #0f3d0f);
  border-radius: 46%;
  border: 20px solid #8B4513;
  box-shadow: 
    0 0 0 15px #654321,
    inset 0 0 80px rgba(0, 0, 0, 0.8),
    0 20px 40px rgba(0, 0, 0, 0.6);
}

.table-outer-ring {
  position: absolute;
  top: -30px;
  left: -30px;
  right: -30px;
  bottom: -30px;
  border: 8px solid #5D4037;
  border-radius: 50%;
  pointer-events: none;
}

.table-inner-surface {
  position: absolute;
  top: 60px;
  left: 60px;
  right: 60px;
  bottom: 60px;
  background: linear-gradient(145deg, #2d7a2d, #1a5a1a);
  border-radius: 50%;
  box-shadow: inset 0 0 50px rgba(0, 0, 0, 0.5);
}

/* Центр стола */
.table-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  align-items: center;
  gap: 15px;
  z-index: 5;
}

.bank-display {
  display: flex;
  align-items: center;
  background: rgba(0, 0, 0, 0.9);
  border: 4px solid #fbbf24;
  border-radius: 20px;
  padding: 15px 20px;
  text-align: center;
  min-width: 100px;
  box-shadow: 0 0 30px rgba(251, 191, 36, 0.4);
}

.bank-icon { 
  font-size: 2rem; 
  margin-bottom: 8px; 
  margin-right: 4px;
}

.bank-amount { 
  font-size: 1.5rem; 
  font-weight: bold; 
  color: #fbbf24; 
  margin-bottom: 5px;
  margin-right: 4px;
}

.bank-label { 
  font-size: 0.8rem; 
  color: #d1d5db; 
  text-transform: uppercase;
  letter-spacing: 1.5px;
}

.deck-display {
  display: flex;
  align-items: end;
  gap: 10px;
}

.deck-cards {
  position: relative;
  height: 60px;
  width: 48px;
}

.card-back {
  position: absolute;
  width: 40px;
  height: 56px;
  background: linear-gradient(45deg, #1e40af, #3b82f6);
  border: 2px solid #fff;
  border-radius: 6px;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
}

.deck-count {
  font-size: 0.8rem;
  color: #9ca3af;
  background: rgba(0, 0, 0, 0.7);
  padding: 4px 12px;
  border-radius: 12px;
}

/* Расположение игроков */
.player-position {
  position: absolute;
  z-index: 10;
  width: 220px;
  min-height: 160px;
  transition: all 0.3s ease;
  display: flex;
  justify-content: center;
  align-items: center;
}

.pos-1 { top: -15%; left: 50%; transform: translateX(-50%); }
.pos-2 { top: -5%; right: 10%; }
.pos-3 { bottom: -20%; right: 10%; transform: translateY(-50%); }
.pos-4 { bottom: -10%; left: 50%; transform: translateX(-50%); }
.pos-5 { bottom: -20%; left: 10%; transform: translateY(-50%); }
.pos-6 { top: -5%; left: 10%; }

.player-position.occupied { opacity: 1; }
.player-position.empty { opacity: 0.3; }
.player-position.current { transform: scale(1.15); z-index: 20; }
.player-position.current.pos-1,
.player-position.current.pos-4 { 
  transform: translateX(-50%) scale(1.15); 
}

.player-position.current.pos-3,
.player-position.current.pos-5 { 
  transform: translateY(-50%) scale(1.15); 
}

/* 🎴 МОБИЛЬНАЯ ВЕРСИЯ */
.mobile-table {
  height: 100vh;
  display: flex;
  flex-direction: column;
  background: linear-gradient(135deg, #0a2f0a 0%, #1a5a1a 100%);
}

/* Верхняя панель */
.mobile-header {
  background: rgba(0, 0, 0, 0.9);
  padding: 15px;
  border-bottom: 3px solid #16a34a;
  color: white;
}

.mobile-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.mobile-title h1 {
  margin: 0;
  font-size: 1.5rem;
}

.mobile-status {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: bold;
}

.mobile-status.waiting {
  background: rgba(246, 224, 94, 0.2);
  color: #f6e05e;
  border: 1px solid #f6e05e;
}

.mobile-status.active {
  background: rgba(104, 211, 145, 0.2);
  color: #68d391;
  border: 1px solid #68d391;
}

.mobile-game-info {
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

.info-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
}

.info-item .label {
  font-size: 0.7rem;
  color: #9ca3af;
  margin-bottom: 2px;
}

.info-item .value {
  font-size: 0.9rem;
  font-weight: bold;
  color: white;
}

/* Игровой стол */
.mobile-players-ring {
  flex: 1;
  position: relative;
  width: 100%;
  height: 370px;
  max-width: 400px;
  margin: 0 auto;
  border-radius: 46%;
  background: linear-gradient(145deg, #1a5a1a, #0f3d0f);
  border: 8px solid #8B4513;
  box-shadow: 0 0 0 15px #654321, inset 0 0 80px rgba(0, 0, 0, 0.8), 0 20px 40px rgba(0, 0, 0, 0.6);
}

/* Позиции игроков */
.mobile-player-spot {
  position: absolute;
  display: flex;
  align-items: center;
  gap: 8px;
  z-index: 10;
  transition: all 0.3s ease;
}

.mobile-player-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 1rem;
  border: 3px solid #374151;
  position: relative;
}

.mobile-player-spot.current .mobile-player-avatar {
  border-color: #fbbf24;
  box-shadow: 0 0 15px rgba(251, 191, 36, 0.5);
}

.dealer-indicator,
.turn-indicator {
  position: absolute;
  top: -5px;
  right: -5px;
  font-size: 0.8rem;
  background: rgba(0, 0, 0, 0.8);
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.turn-indicator {
  top: -5px;
  left: -5px;
  background: rgba(251, 191, 36, 0.9);
}

.mobile-player-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.player-name {
  font-size: 0.8rem;
  font-weight: bold;
  color: white;
}

.player-balance {
  font-size: 0.7rem;
  color: #fbbf24;
}

/* Расположение позиций */
.spot-1 { top: 5%; left: 50%; transform: translateX(-50%); flex-direction: column; text-align: center; }
.spot-2 { top: 20%; right: 5%; }
.spot-3 { bottom: 25%; right: 5%; }
.spot-4 { bottom: 5%; left: 50%; transform: translateX(-50%); flex-direction: column; text-align: center; }
.spot-5 { bottom: 25%; left: 5%; }
.spot-6 { top: 20%; left: 5%; }

/* Центр стола */
.mobile-table-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  align-items: center;
  gap: 10px;
}

.mobile-bank {
  background: rgba(0, 0, 0, 0.9);
  border: 3px solid #fbbf24;
  border-radius: 15px;
  padding: 10px 15px;
  text-align: center;
  min-width: 80px;
}

.bank-icon { font-size: 1.2rem; margin-bottom: 5px; }
.bank-amount { 
  font-size: 1.1rem; 
  font-weight: bold; 
  color: #fbbf24; 
  margin-bottom: 2px;
  margin-right: 8px;
}
.bank-label { 
  font-size: 0.6rem; 
  color: #d1d5db; 
  text-transform: uppercase;
}

.mobile-deck {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
}

.deck-stack {
  position: relative;
  height: 40px;
}

.deck-stack .card-back {
  position: absolute;
  width: 30px;
  height: 42px;
  background: linear-gradient(45deg, #1e40af, #3b82f6);
  border: 2px solid #fff;
  border-radius: 4px;
}

.deck-stack .card-back:nth-child(2) {
  transform: translate(3px, 3px);
}

.deck-count {
  font-size: 0.6rem;
  color: #9ca3af;
  background: rgba(0, 0, 0, 0.6);
  padding: 2px 6px;
  border-radius: 8px;
}

/* Нижняя панель действий */
.mobile-action-panel {
  background: rgba(0, 0, 0, 0.95);
  border-top: 3px solid #16a34a;
  padding: 15px;
}

/* Карты игрока */
.player-cards-section {
  margin-bottom: 15px;
}

.section-title {
  color: #d1d5db;
  font-size: 0.9rem;
  margin-bottom: 8px;
  font-weight: bold;
}

.mobile-cards-container {
  display: flex;
  gap: 8px;
  justify-content: center;
  margin-bottom: 10px;
}

.mobile-card {
  width: 80px;
  height: 112px;
  border-radius: 10px;
  overflow: hidden;
  border: 3px solid #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.mobile-card .card-front {
  width: 100%;
  height: 100%;
  background: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  opacity: 1 !important;
}

.card-rank {
  font-size: 1.6rem;
  margin-bottom: 4px;
  color: #1a202c;
}

.card-suit {
  font-size: 2.2rem;
  color: #1a202c;
}

.card-back-mobile {
  width: 100%;
  height: 100%;
  background: linear-gradient(45deg, #1e40af, #3b82f6);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2rem;
  opacity: 1 !important;
}

/* Кнопки действий */
.actions-section {
  margin-bottom: 15px;
}

.mobile-actions-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.mobile-action-btn {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 12px 8px;
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  min-height: 50px;
}

.mobile-action-btn:active {
  transform: scale(0.95);
}

.mobile-action-btn.fold { background: #ef4444; }
.mobile-action-btn.dark { background: #8b5cf6; }
.mobile-action-btn.raise { background: #f59e0b; }
.mobile-action-btn.check { background: #6b7280; }
.mobile-action-btn.call { background: #10b981; }

.action-icon {
  font-size: 1rem;
}

.action-text {
  font-size: 0.7rem;
}

/* Информация о ходе */
.turn-info {
  background: rgba(59, 130, 246, 0.1);
  padding: 12px;
  border-radius: 10px;
  border: 1px solid #3b82f6;
}

.current-turn {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.turn-label {
  color: #9ca3af;
  font-size: 0.8rem;
}

.current-player {
  display: flex;
  align-items: center;
  gap: 5px;
  color: white;
  font-weight: bold;
  font-size: 0.9rem;
}

.current-player {
  color: #fbbf24;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* Анимации */
@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

/* Адаптивность */
@media (max-width: 1024px) {
  .poker-table-container {
    height: 60vh;
    min-height: 500px;
  }
  
  .player-position {
    width: 120px;
  }
}

@media (max-height: 700px) {
  .mobile-player-avatar {
    width: 40px;
    height: 40px;
    font-size: 0.8rem;
  }
  
  .mobile-card {
    width: 50px;
    height: 70px;
  }
  
  .mobile-action-btn {
    padding: 8px 4px;
    min-height: 45px;
  }
}
</style>