<template>
  <div class="compact-player-slot" :class="playerClasses">
        <!-- 🎯 ДОБАВЬ ДИАГНОСТИКУ В ШАБЛОН -->
    <div v-if="uiState.showPlayer" class="player-info">
      <div class="player-name">{{ uiState.playerName }}</div>
      <div v-if="uiState.isReady" class="ready-indicator">✅</div>
    </div>
    <div v-else class="empty-slot">
      Свободно
    </div>
    <div v-if="!player.id" class="empty-slot">
      <div class="empty-avatar">+</div>
      <div class="empty-text">Свободно</div>
    </div>
    
    <div class="player-wrap" v-else>
      <!-- Аватар с индикаторами -->
      <div class="player-avatar" :class="avatarClasses">
        <div class="avatar-placeholder">{{ playerInitials }}</div>
        
        <!-- 🎯 ИНДИКАТОРЫ РЕЖИМОВ -->
        <div v-if="isDealer" class="dealer-indicator" title="Дилер">D</div>
        <div v-if="isCurrentTurn" class="turn-indicator" title="Ваш ход">🎯</div>
        <div v-if="player.mode === 'dark'" class="dark-indicator" title="Играет в темную">🌑</div>
        <div v-if="player.mode === 'open'" class="open-indicator" title="Карты открыты">🎴</div>
        <div v-if="player.status === 'folded'" class="folded-indicator" title="Выбыл">🏳️</div>
        
        <!-- 🎯 ГРАДИЕНТ ДЛЯ DARK РЕЖИМА -->
        <div v-if="player.mode === 'dark'" class="dark-gradient"></div>
      </div>

      <!-- Информация игрока -->
      <div class="player-info">
        <div class="player-name">{{ player.name }}</div>
        <div class="player-balance">{{ player.balance }}🪙</div>
        
        <!-- 🎯 СТАВКА С УЧЕТОМ DARK СКИДКИ -->
        <div v-if="player.current_bet > 0" class="player-bet" :class="{ 'dark-bet': player.mode === 'dark' }">
          Ставка: {{ player.current_bet }}🪙
          <span v-if="player.mode === 'dark'" class="dark-discount-badge">-50%</span>
        </div>

        <!-- Статус готовности -->
        <div v-if="showReady" class="ready-status">
          <span v-if="player.is_ready" class="ready-text">✅ Готов</span>
          <span v-else class="not-ready-text">⏳ Ожидание</span>
        </div>

        <!-- 🎯 РЕЖИМ ИГРЫ С ИКОНКАМИ -->
        <div v-if="player.mode" class="player-mode">
          <span v-if="player.mode === 'dark'" class="mode-dark">
            <span class="mode-icon">🌑</span>
            Темная
          </span>
          <span v-if="player.mode === 'open'" class="mode-open">
            <span class="mode-icon">🎴</span>
            Открытая
          </span>
          <span v-if="player.mode === 'none'" class="mode-none">
            <span class="mode-icon">❓</span>
            Не выбрано
          </span>
        </div>
      </div>

      <!-- 🎯 КАРТЫ ИГРОКА С РАЗНЫМИ СТИЛЯМИ ДЛЯ РЕЖИМОВ -->
      <div v-if="showCards && player.cards && player.cards.length > 0" class="player-cards" :class="cardsContainerClasses">
        <div v-for="(card, index) in player.cards" :key="index" class="card-slot" :class="getCardClasses(card, index)">
          <!-- 🎯 РАЗНЫЕ СТИЛИ КАРТ ДЛЯ РЕЖИМОВ -->
          <div v-if="shouldShowCard(card, index)" class="card-front" :class="cardFrontClasses">
            <div class="card-rank">{{ card.rank }}</div>
            <div class="card-suit">{{ getSuitSymbol(card.suit) }}</div>
            
            <!-- 🎯 ИНДИКАТОР ДЖОКЕРА -->
            <div v-if="isJoker(card)" class="joker-indicator">🃏</div>
          </div>
          <div v-else class="card-back" :class="cardBackClasses">
            <!-- 🎯 СПЕЦИАЛЬНЫЙ БЭК ДЛЯ DARK РЕЖИМА -->
            <div v-if="player.mode === 'dark'" class="dark-card-pattern"></div>
          </div>
        </div>
        
        <!-- 🎯 ИНФОРМАЦИЯ О КОМБИНАЦИИ ЕСЛИ КАРТЫ ОТКРЫТЫ -->
        <div v-if="shouldShowCombinationInfo" class="combination-info">
          <div class="combination-points">{{ player.points || '?' }} очков</div>
          <div v-if="player.combination" class="combination-name">{{ player.combination }}</div>
        </div>
      </div>

      <!-- 🎯 СПЕЦИАЛЬНЫЕ ДЕЙСТВИЯ ДЛЯ РЕЖИМОВ -->
      <div v-if="isCurrentTurn && showActions" class="player-actions">
        <button 
          v-for="action in availableActions" 
          :key="action"
          class="action-btn"
          :class="[action, { 
            loading: isActionLoading,
            'mode-action': isModeAction(action),
            'recommended': isRecommendedAction(action)
          }]"
          @click="handleAction(action)"
          :disabled="isActionLoading || isModeDisabled(action)"
          :title="getActionDescription(action)"
        >
          <span v-if="isActionLoading">⏳</span>
          <span v-else>
            <span class="action-icon">{{ getActionIcon(action) }}</span>
            {{ getActionText(action) }}
            <span v-if="isModeAction(action)" class="mode-badge">{{ getModeBadge(action) }}</span>
          </span>
        </button>
      </div>
      
      <!-- Готовность -->
      <div v-if="showReady" class="ready-controls">
        <button 
          v-if="!player.is_ready && player.id" 
          class="ready-btn"
          @click="handleReady"
          :disabled="isActionLoading"
        >
          ✅ Готов
        </button>
        <div v-else-if="player.is_ready" class="ready-badge">✓ Готов</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, watch, onMounted } from 'vue'

const props = defineProps({
  player: {
    type: Object,
    default: () => ({})
  },
  cards: {
    type: Array,
    default: () => []
  },
  isCurrentTurn: Boolean,
  isDealer: Boolean,
  showReady: Boolean,
  showCards: {
    type: Boolean,
    default: true
  },
  showActions: {
    type: Boolean,
    default: false
  },
  currentRound: Number, 
  dealerPosition: Number, 
  currentBet: Number,
  players: {
    type: Array,
    default: () => []
  },
  baseBet: {
    type: Number,
    default: 50
  },
  isActionLoading: {
    type: Boolean,
    default: false
  },
  currentPlayerId: Number
})

const emit = defineEmits(['player-action', 'player-ready'])

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ДЛЯ ВИЗУАЛЬНЫХ РЕЖИМОВ
const playerClasses = computed(() => ({
  'current-turn': props.isCurrentTurn,
  'dealer': props.isDealer,
  'empty': !props.player.id,
  'ready': props.player.is_ready,
  'folded': props.player.status === 'folded',
  'dark-mode': props.player.mode === 'dark',
  'open-mode': props.player.mode === 'open',
  'none-mode': !props.player.mode || props.player.mode === 'none',
  'active': props.player.status === 'active'
}))

const avatarClasses = computed(() => ({
  'dark-avatar': props.player.mode === 'dark',
  'open-avatar': props.player.mode === 'open'
}))

const cardsContainerClasses = computed(() => ({
  'dark-cards': props.player.mode === 'dark',
  'open-cards': props.player.mode === 'open',
  'folded-cards': props.player.status === 'folded'
}))

const cardFrontClasses = computed(() => ({
  'dark-card-front': props.player.mode === 'dark',
  'open-card-front': props.player.mode === 'open'
}))

const cardBackClasses = computed(() => ({
  'dark-card-back': props.player.mode === 'dark',
  'open-card-back': props.player.mode === 'open'
}))

const shouldShowCombinationInfo = computed(() => {
  return props.player.mode === 'open' && 
         props.player.cards && 
         props.player.cards.every(card => card.is_visible) &&
         props.player.points > 0
})

const playerInitials = computed(() => {
  if (!props.player.id) return '+'
  return props.player.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

// 🎯 ЛОГИКА ОТОБРАЖЕНИЯ КАРТ С УЧЕТОМ РЕЖИМОВ
const shouldShowCard = (card, index) => {
  // Если карта помечена как видимая
  if (card.is_visible) return true
  
  // Если это текущий игрок и он в режиме 'open'
  if (props.player.id === props.currentPlayerId && props.player.mode === 'open') {
    return true
  }
  
  // Если игрок выбыл - показываем все карты
  if (props.player.status === 'folded') {
    return true
  }
  
  // 🎯 ОСОБЫЕ ПРАВИЛА ДЛЯ DARK РЕЖИМА
  if (props.player.mode === 'dark') {
    // В dark режиме показываем только если это специальное правило
    return false
  }
  
  return false
}

const getSuitSymbol = (suit) => {
  const symbols = {
    'hearts': '♥',
    'diamonds': '♦', 
    'clubs': '♣',
    'spades': '♠'
  }
  return symbols[suit] || suit
}

// 🎯 МЕТОДЫ ДЕЙСТВИЙ
const handleAction = async (action) => {
  console.log('🎯 [CompactPlayerSlot] Action requested:', action, 'player:', props.player.name)
  
  if (props.isActionLoading) {
    console.log('⏳ Action already in progress, skipping...')
    return
  }

  try {
    emit('player-action', action)
  } catch (error) {
    console.error('❌ Action handling failed:', error)
  }
}

const handleReady = () => {
  console.log('✅ Marking player ready:', props.player.id)
  emit('player-ready', props.player.id)
}

const getCardClasses = (card, index) => ({
  'visible': card.is_visible,
  'hidden': !card.is_visible,
  'joker': isJoker(card),
  'ace': card.rank === 'A',
  'royal': ['K', 'Q', 'J'].includes(card.rank)
})

const isJoker = (card) => {
  return card.rank === '6' && card.suit === '♣'
}

// 🎯 ЛОГИКА ДЕЙСТВИЙ С УЧЕТОМ РЕЖИМОВ
const availableActions = computed(() => {
  if (!props.isCurrentTurn || props.isActionLoading) return []
  
  const actions = ['call', 'raise', 'fold']
  const player = props.player
  
  console.log('🎯 [availableActions] Checking for player:', {
    name: player.name,
    position: player.position,
    mode: player.mode,
    status: player.status,
    currentRound: props.currentRound,
    currentBet: props.currentBet
  })

  // 🎯 CHECK: только следующий после дилера в 1 раунде при базовой ставке
  const isAfterDealer = isPlayerAfterDealer()
  if (isAfterDealer && props.currentRound === 1 && props.currentBet <= props.baseBet) {
    actions.unshift('check')
  }

  // 🎯 DARK: только следующий после дилера в 1-2 раундах, если еще не выбрал режим
  if (isAfterDealer && props.currentRound <= 2 && (!player.mode || player.mode === 'none')) {
    actions.push('dark')
  }

  // 🎯 OPEN: всегда доступно если режим еще не выбран
  if (!player.mode || player.mode === 'none') {
    actions.push('open')
  }

  // 🎯 REVEAL: со 2 раунда
  if (props.currentRound >= 2) {
    actions.push('reveal')
  }

  console.log('🎯 Final available actions:', actions)
  return actions
})

const isModeAction = (action) => {
  return ['dark', 'open'].includes(action)
}

const isRecommendedAction = (action) => {
  const player = props.player
  
  // OPEN рекомендуется если много очков
  if (action === 'open' && player.points > 25) {
    return true
  }
  
  // DARK рекомендуется в начале игры
  if (action === 'dark' && props.currentRound === 1 && !player.mode) {
    return true
  }
  
  return false
}

const isModeDisabled = (action) => {
  const player = props.player
  
  // Нельзя выбрать режим если уже выбран
  if (action === 'dark' && player.mode) {
    return true
  }
  
  if (action === 'open' && player.mode) {
    return true
  }
  
  return false
}

const getModeBadge = (action) => {
  const badges = {
    'dark': '50%',
    'open': '👁️'
  }
  return badges[action] || ''
}

// 🎯 ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
const isPlayerAfterDealer = () => {
  if (!props.players || !props.dealerPosition) return false
  
  const activePlayers = props.players
    .filter(p => p.id && p.status !== 'folded')
    .sort((a, b) => a.position - b.position)
  
  if (activePlayers.length === 0) return false
  
  const dealerIndex = activePlayers.findIndex(p => p.position === props.dealerPosition)
  if (dealerIndex === -1) return false
  
  const nextPlayerIndex = (dealerIndex + 1) % activePlayers.length
  return activePlayers[nextPlayerIndex]?.id === props.player.id
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
    'dark': '🌑',
    'open': '🎴',
    'reveal': '🔍'
  }
  return icons[action] || '🎯'
}

const getActionDescription = (action) => {
  const descriptions = {
    'check': 'Пропустить ход без ставки',
    'call': 'Поддержать текущую ставку',
    'raise': 'Повысить ставку',
    'fold': 'Сбросить карты и выйти из раунда',
    'dark': 'Играть в темную (ставка ×0.5)',
    'open': 'Открыть свои карты',
    'reveal': 'Вскрыться против оппонента'
  }
  return descriptions[action] || action
}

console.log('🎯 [CompactPlayerSlot] Props received:', {
  player: props.player,
  position: props.position,
  isCurrentPlayer: props.isCurrentPlayer,
  isDealer: props.isDealer,
  gameStatus: props.gameStatus
})

console.log('🎯 [CompactPlayerSlot] Player data analysis:', {
  name: props.player?.name,
  mode: props.player?.mode,
  status: props.player?.status,
  is_ready: props.player?.isReady, // 🎯 ОБРАТИ ВНИМАНИЕ - isReady а не is_ready
  current_bet: props.player?.currentBet
})

// 🎯 ДИАГНОСТИКА ВЫЧИСЛЯЕМЫХ СВОЙСТВ
const displayData = computed(() => {
  console.log('🎯 [CompactPlayerSlot] displayData computed:', {
    player: props.player,
    isEmpty: !props.player?.name || props.player?.name === 'Свободно',
    isRealPlayer: props.player?.id && props.player?.name !== 'Свободно',
    name: props.player?.name,
    isReady: props.player?.isReady
  })
  
  return {
    isEmpty: !props.player?.name || props.player?.name === 'Свободно',
    isRealPlayer: props.player?.id && props.player?.name !== 'Свободно',
    name: props.player?.name,
    isReady: props.player?.isReady
  }
})

// 🎯 ДИАГНОСТИКА UI СОСТОЯНИЯ
const uiState = computed(() => {
  const state = {
    showPlayer: props.player?.name && props.player?.name !== 'Свободно',
    playerName: props.player?.name || 'Свободно',
    isReady: props.player?.isReady || false
  }
  
  console.log('🎯 [CompactPlayerSlot] UI State:', state)
  return state
})

onMounted(() => {
  console.log('🎯 [CompactPlayerSlot] MOUNTED with player:', {
    name: props.player?.name,
    isReady: props.player?.isReady,
    status: props.player?.status,
    position: props.position
  })
})

watch(() => props.player, (newPlayer) => {
  console.log('🔄 [CompactPlayerSlot] Player UPDATED:', {
    name: newPlayer?.name,
    isReady: newPlayer?.isReady,
    status: newPlayer?.status
  })
}, { deep: true })

</script>

<style scoped>
.compact-player-slot {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-radius: 12px;
  background: rgba(0, 0, 0, 0.7);
  border: 2px solid transparent;
  transition: all 0.3s ease;
  min-width: 160px;
  min-height: 140px;
  position: relative;
}

/* 🎯 СТИЛИ ДЛЯ РАЗЛИЧНЫХ СОСТОЯНИЙ ИГРОКА */
.compact-player-slot.current-turn {
  border-color: #fbbf24;
  box-shadow: 0 0 15px rgba(251, 191, 36, 0.4);
}

.compact-player-slot.dealer {
  border-color: #3b82f6;
}

.compact-player-slot.ready {
  border-color: #10b981;
}

.compact-player-slot.folded {
  opacity: 0.6;
  border-color: #6b7280;
}

.compact-player-slot.dark-mode {
  border-color: #8b5cf6;
}

.compact-player-slot.open-mode {
  border-color: #10b981;
}

/* Аватар с индикаторами */
.player-avatar {
  position: relative;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 1.2rem;
}

/* 🎯 ИНДИКАТОРЫ */
.dealer-indicator {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #3b82f6;
  color: white;
  border-radius: 50%;
  width: 22px;
  height: 22px;
  font-size: 0.7rem;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 5;
}

.turn-indicator {
  position: absolute;
  top: -8px;
  left: -8px;
  font-size: 1rem;
  z-index: 5;
}

.dark-indicator {
  position: absolute;
  bottom: -5px;
  right: -5px;
  background: #8b5cf6;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.folded-indicator {
  position: absolute;
  bottom: -5px;
  left: -5px;
  background: #6b7280;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Информация игрока */
.player-info {
  text-align: center;
}

.player-name {
  font-size: 0.9rem;
  font-weight: bold;
  color: white;
  margin-bottom: 2px;
}

.player-balance {
  font-size: 0.8rem;
  color: #fbbf24;
  margin-bottom: 4px;
}

.player-bet {
  font-size: 0.7rem;
  color: #10b981;
  font-weight: bold;
}

.player-mode {
  font-size: 0.7rem;
  margin-top: 4px;
}

.mode-dark {
  color: #8b5cf6;
}

.mode-open {
  color: #10b981;
}

.mode-none {
  color: #6b7280;
}

/* Карты */
.player-cards {
  display: flex;
  gap: 4px;
  margin: 8px auto;
  justify-content: center;
}

.card-slot {
  width: 40px;
  height: 56px;
  border-radius: 4px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.card-front {
  width: 100%;
  height: 100%;
  background: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  border: 1px solid #333;
}

.card-rank {
  font-size: 0.9rem;
  margin-bottom: 2px;
  color: #1a202c;
}

.card-suit {
  font-size: 1.2rem;
  color: #1a202c;
}

.card-back {
  width: 100%;
  height: 100%;
  background: linear-gradient(45deg, #1e40af, #3b82f6);
  border: 1px solid #fff;
}

/* Действия */
.player-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  justify-content: center;
  margin-top: 8px;
}

.action-btn {
  background: #4b5563;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 0.7rem;
  cursor: pointer;
  transition: background 0.2s;
  display: flex;
  align-items: center;
  gap: 4px;
  min-width: 60px;
  justify-content: center;
}

.action-btn:hover:not(:disabled) {
  background: #6b7280;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Стили для конкретных действий */
.action-btn.check { background: #6b7280; }
.action-btn.call { background: #10b981; }
.action-btn.raise { background: #f59e0b; }
.action-btn.fold { background: #ef4444; }
.action-btn.dark { background: #8b5cf6; }
.action-btn.open { background: #059669; }
.action-btn.reveal { background: #dc2626; }

.action-icon {
  font-size: 0.8rem;
}

/* Готовность */
.ready-controls {
  margin-top: 5px;
}

.ready-btn {
  background: #10b981;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.8rem;
  cursor: pointer;
}

.ready-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.ready-badge {
  width: 24px;
  height: 24px;
  background: #10b981;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.8rem;
}

.ready-status {
  margin-top: 4px;
}

.ready-text {
  color: #10b981;
  font-size: 0.7rem;
  font-weight: bold;
}

.not-ready-text {
  color: #6b7280;
  font-size: 0.7rem;
}

/* Очки комбинации */
.combination-points {
  margin-top: 4px;
}

.points-badge {
  background: rgba(251, 191, 36, 0.2);
  color: #fbbf24;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: bold;
  border: 1px solid #fbbf24;
}

/* Свободные места */
.empty-slot {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.empty-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: #4b5563;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2rem;
  font-weight: bold;
  border: 2px dashed #6b7280;
}

.empty-text {
  color: #d1d5db;
  font-size: 0.8rem;
  font-weight: bold;
}

/* 🎯 ОСНОВНЫЕ СТИЛИ СЛОТА */
.compact-player-slot {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-radius: 12px;
  background: rgba(0, 0, 0, 0.7);
  border: 2px solid transparent;
  transition: all 0.3s ease;
  min-width: 160px;
  min-height: 140px;
  position: relative;
  overflow: hidden;
}

/* 🎯 СТИЛИ ДЛЯ РАЗЛИЧНЫХ РЕЖИМОВ */
.compact-player-slot.dark-mode {
  border-color: #8b5cf6;
  background: rgba(139, 92, 246, 0.1);
  box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
}

.compact-player-slot.open-mode {
  border-color: #10b981;
  background: rgba(16, 185, 129, 0.1);
  box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
}

.compact-player-slot.current-turn {
  border-color: #fbbf24;
  box-shadow: 0 0 15px rgba(251, 191, 36, 0.4);
}

.compact-player-slot.folded {
  opacity: 0.6;
  border-color: #6b7280;
}

/* 🎯 АВАТАР С РЕЖИМАМИ */
.player-avatar {
  position: relative;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 1.2rem;
  overflow: hidden;
}

.player-avatar.dark-avatar {
  background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
}

.player-avatar.open-avatar {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

/* 🎯 ГРАДИЕНТ ДЛЯ DARK РЕЖИМА */
.dark-gradient {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(45deg, 
    rgba(139, 92, 246, 0.3) 0%, 
    rgba(124, 58, 237, 0.5) 50%, 
    rgba(139, 92, 246, 0.3) 100%);
  animation: darkPulse 2s ease-in-out infinite;
}

@keyframes darkPulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 0.8; }
}

/* 🎯 ИНДИКАТОРЫ */
.open-indicator {
  position: absolute;
  bottom: -5px;
  right: -5px;
  background: #10b981;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ... другие индикаторы (dealer, turn, dark, folded) ... */

/* 🎯 СТАВКА С DARK СКИДКОЙ */
.player-bet.dark-bet {
  color: #8b5cf6;
  font-weight: bold;
}

.dark-discount-badge {
  background: #8b5cf6;
  color: white;
  padding: 1px 4px;
  border-radius: 8px;
  font-size: 0.6rem;
  margin-left: 4px;
}

/* 🎯 РЕЖИМЫ С ИКОНКАМИ */
.player-mode {
  font-size: 0.7rem;
  margin-top: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.mode-icon {
  font-size: 0.8rem;
}

.mode-dark {
  color: #8b5cf6;
  font-weight: bold;
}

.mode-open {
  color: #10b981;
  font-weight: bold;
}

.mode-none {
  color: #6b7280;
}

/* 🎯 КАРТЫ С РЕЖИМАМИ */
.player-cards.dark-cards {
  opacity: 0.8;
}

.player-cards.open-cards {
  opacity: 1;
}

.card-slot {
  width: 40px;
  height: 56px;
  border-radius: 4px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  position: relative;
}

/* 🎯 КАРТЫ В DARK РЕЖИМЕ */
.card-back.dark-card-back {
  background: linear-gradient(45deg, #4c1d95, #7c3aed);
  border: 2px solid #a78bfa;
}

.dark-card-pattern {
  width: 100%;
  height: 100%;
  background: 
    radial-gradient(circle at 20% 20%, rgba(255,255,255,0.1) 2px, transparent 2px),
    radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 2px, transparent 2px);
  background-size: 10px 10px;
}

/* 🎯 КАРТЫ В OPEN РЕЖИМЕ */
.card-front.open-card-front {
  border: 2px solid #10b981;
  box-shadow: 0 0 8px rgba(16, 185, 129, 0.3);
}

/* 🎯 ДЖОКЕР И ОСОБЫЕ КАРТЫ */
.joker-indicator {
  position: absolute;
  bottom: 2px;
  right: 2px;
  font-size: 0.6rem;
}

.card-slot.joker .card-front {
  background: linear-gradient(45deg, #fbbf24, #f59e0b);
}

.card-slot.ace .card-front {
  background: linear-gradient(45deg, #f8fafc, #e2e8f0);
}

.card-slot.royal .card-front {
  background: linear-gradient(45deg, #fef3c7, #fde68a);
}

/* 🎯 ИНФОРМАЦИЯ О КОМБИНАЦИИ */
.combination-info {
  margin-top: 4px;
  text-align: center;
}

.combination-points {
  font-size: 0.8rem;
  font-weight: bold;
  color: #fbbf24;
}

.combination-name {
  font-size: 0.7rem;
  color: #d1d5db;
}

/* 🎯 ДЕЙСТВИЯ С РЕЖИМАМИ */
.action-btn.mode-action {
  border: 2px solid;
  font-weight: bold;
}

.action-btn.dark.mode-action {
  border-color: #8b5cf6;
  background: rgba(139, 92, 246, 0.2);
}

.action-btn.open.mode-action {
  border-color: #10b981;
  background: rgba(16, 185, 129, 0.2);
}

.action-btn.recommended {
  animation: glow 2s ease-in-out infinite;
}

@keyframes glow {
  0%, 100% { box-shadow: 0 0 5px currentColor; }
  50% { box-shadow: 0 0 15px currentColor; }
}

.mode-badge {
  font-size: 0.6rem;
  background: rgba(255, 255, 255, 0.2);
  padding: 1px 4px;
  border-radius: 6px;
  margin-left: 4px;
}

/* 🎯 АДАПТИВНОСТЬ */
@media (max-width: 768px) {
  .compact-player-slot {
    min-width: 140px;
    min-height: 120px;
    padding: 8px;
  }
  
  .player-avatar {
    width: 50px;
    height: 50px;
    font-size: 1rem;
  }
  
  .card-slot {
    width: 35px;
    height: 49px;
  }
}
</style>