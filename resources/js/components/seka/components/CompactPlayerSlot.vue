<template>
  <div class="compact-player-slot" :class="playerClasses">
    <div v-if="!player.id" class="empty-slot">
      <div class="empty-avatar">+</div>
      <div class="empty-text">Свободно</div>
    </div>
    <div v-else>
      <!-- Аватар -->
      <div class="player-avatar">
        <div class="avatar-placeholder">{{ playerInitials }}</div>
        <div v-if="isDealer" class="dealer-indicator">D</div>
        <div v-if="isCurrentTurn" class="turn-indicator">🎯</div>
      </div>

      <!-- Информация игрока -->
      <div class="player-info">
        <div class="player-name">{{ player.name }}</div>
        <div class="player-balance">{{ player.balance }}₽</div>
          <!-- Статус готовности -->
        <div v-if="showReady" class="ready-status">
          <span v-if="player.isReady" class="ready-text">✅ Готов</span>
          <span v-else class="not-ready-text">⏳ Ожидание</span>
        </div>
      </div>

      <!-- Карты игрока -->
      <div v-if="showCards && cards.length > 0" class="player-cards">
        <div v-for="(card, index) in cards" :key="index" class="card-slot">
          <div v-if="card.isVisible" class="card-front">
            {{ card.rank }}{{ card.suit }}
          </div>
          <div v-else class="card-back"></div>
        </div>
      </div>

      <!-- 🎯 ОТОБРАЖЕНИЕ ОЧКОВ КОМБИНАЦИИ SEKA -->
      <div v-if="showCombinationPoints" class="combination-points">
        <div class="points-badge">
          {{ combinationPoints }} очков
        </div>
        <div class="combination-name">
          {{ combinationDisplayName }}
        </div>
      </div>

      <!-- Действия игрока -->
      <div v-if="isCurrentTurn && showActions" class="player-actions">
        <button v-for="action in availableActions" 
                :key="action"
                class="action-btn"
                @click="handleAction(action)">
          {{ getActionText(action) }}
        </button>
      </div>
      
      <!-- Готовность -->
      <div v-if="showReady" class="ready-controls">
        <button v-if="!player.isReady && player.id" 
                class="ready-btn"
                @click="handleReady">
          ✅ Готов
        </button>
        <div v-else-if="player.isReady" class="ready-badge">✓ Готов</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'

const props = defineProps({
  player: Object,
  cards: Array,
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
  currentBet: Number 
})

const emit = defineEmits(['player-action', 'player-ready'])

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
const playerClasses = computed(() => ({
  'current-turn': props.isCurrentTurn,
  'dealer': props.isDealer,
  'empty': !props.player.id,
  'ready': props.player.isReady
}))

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ДЛЯ ОЧКОВ SEKA
const showCombinationPoints = computed(() => {
  return props.cards.length > 0 && props.cards.every(card => card.isVisible)
})

const combinationResult = computed(() => {
  if (!showCombinationPoints.value) return null
  // TODO: Получить от родителя реальный расчет
  // Временная заглушка для демонстрации
  const points = 30 + Math.floor(Math.random() * 8) // 30-37 для демо
  return {
    points: points,
    combination: getCombinationDisplayName(points)
  }
})

const combinationPoints = computed(() => {
  return combinationResult.value?.points || 0
})

const combinationDisplayName = computed(() => {
  return combinationResult.value?.combination || 'Расчет...'
})

// 🎯 ВРЕМЕННАЯ ФУНКЦИЯ ДЛЯ ДЕМО
const getCombinationDisplayName = (points) => {
  const names = {
    33: '🎯 Три десятки', 34: '🎯 Три вальта', 35: '🎯 Три дамы',
    36: '🎯 Три короля', 37: '🎯 Три туза', 32: '🌟 Джокер + Туз + масть',
    31: '✨ Три масти + Туз', 30: '💎 Три масти'
  }
  return names[points] || `Комбинация (${points})`
}

const testReady = () => {
  console.log('1. CompactPlayerSlot: click')
  emit('player-ready')
}

const handleReady = () => {
  console.log('1. CompactPlayerSlot: click for player', props.player.id)
  emit('player-ready', props.player.id)  // ← передаем ID игрока
}

const playerInitials = computed(() => {
  if (!props.player.id) return '+'
  return props.player.name.split(' ').map(n => n[0]).join('').toUpperCase()
})

// 🎯 МЕТОДЫ
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

const handleAction = (action) => {
  emit('player-action', action)
}

// 🎯 ПРАВИЛЬНАЯ ЛОГИКА ДОСТУПНЫХ ДЕЙСТВИЙ
const availableActions = computed(() => {
  const actions = ['call', 'raise', 'fold']
  
  if (!props.isCurrentTurn) return []
  
  const isDealer = props.player.position === props.dealerPosition
  const isFirstRound = props.currentRound === 1
  const hasNoBet = props.currentBet === 0
  
  // 🎯 ПРАВИЛО: Пропускать и темнить может только следующий после дилера
  const isAfterDealer = props.player.position === (props.dealerPosition % 6) + 1
  
  // CHECK: только следующий после дилера в 1 раунде при отсутствии ставок
  if (isAfterDealer && isFirstRound && hasNoBet) {
    actions.unshift('check')
  }
  
  // DARK: только следующий после дилера в 1 раунде
  if (isAfterDealer && isFirstRound && !props.player.isDark) {
    actions.push('dark')
  }
  
  // 🎯 OPEN: только если карты еще не открыты
  const allCardsOpen = props.cards.length > 0 && props.cards.every(card => card.isVisible)
  if (!allCardsOpen) {
    actions.push('open')
  }
  
  // 🎯 ПРАВИЛО: Дилер НЕ может темнить и пропускать
  if (isDealer) {
    // Убираем check и dark если они добавились
    const dealerActions = actions.filter(action => action !== 'check' && action !== 'dark')
    return dealerActions
  }
  
  // REVEAL: только со 2-го раунда (для любого игрока)
  if (props.currentRound >= 2) {
    actions.push('reveal')
  }
  
  return actions
})

// Отладочный вотчер
watch(() => props.player.isReady, (newVal, oldVal) => {
  console.log('👀 [CompactPlayerSlot] Player ready state changed:', 
    props.player.name, oldVal, '→', newVal)
}, { immediate: true })

// Логируем при монтировании
console.log('🎴 [CompactPlayerSlot] Mounted:', props.player.name, 
  'isReady:', props.player.isReady)

</script>

<style scoped>
.compact-player-slot {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px; /* ← УВЕЛИЧИВАЕМ отступы */
  padding: 12px; /* ← УВЕЛИЧИВАЕМ padding */
  border-radius: 12px;
  background: rgba(0, 0, 0, 0.7);
  border: 2px solid transparent;
  transition: all 0.3s ease;
  min-width: 160px; /* ← УВЕЛИЧИВАЕМ минимальную ширину */
  min-height: 140px; /* ← ДОБАВЛЯЕМ минимальную высоту */
  position: relative;
  box-sizing: border-box;
}

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

/* СТИЛИ ДЛЯ СВОБОДНЫХ МЕСТ - ОДИН РАЗ! */
.compact-player-slot.empty {
  opacity: 0.8;
  background: rgba(0, 0, 0, 0.4);
  border: 2px dashed rgba(255, 255, 255, 0.3);
}

/* Аватар */
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
  margin: 0 auto;
}

.avatar-placeholder {
  font-size: 1.5rem;
}

.dealer-indicator {
  position: absolute;
  top: -8px; /* ← КОРРЕКТИРУЕМ позицию */
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
  top: -8px; /* ← КОРРЕКТИРУЕМ позицию */
  left: -8px;
  font-size: 1rem;
  z-index: 5;
}

/* Информация */
.player-info {
  text-align: center;
  margin: 4px 0; /* ← ДОБАВЛЯЕМ отступы */
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
}

/* Карты */
.player-cards {
  display: flex;
  gap: 8px;
  margin: 8px 0;
  justify-content: center;
}

.card-slot {
  width: 60px;
  height: 84px;
  border-radius: 6px;
  overflow: hidden;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.card-front {
  width: 100%;
  height: 100%;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: bold;
  border: 2px solid #333;
  color: black;
  opacity: 1 !important;
}

.card-rank {
  font-size: 1.4rem; /* было 0.8rem */
  margin-bottom: 4px;
  color: #1a202c;
}

.card-suit {
  font-size: 2rem; /* было 1.5rem */
  color: #1a202c;
}

.card-back {
  width: 100%;
  height: 100%;
  background: linear-gradient(45deg, #1e40af, #3b82f6);
  border: 2px solid #fff;
  opacity: 1 !important;
}

/* Действия */
.player-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  justify-content: center;
  margin-top: 8px; /* ← ДОБАВЛЯЕМ отступ сверху */
}

.action-btn {
  background: #4b5563;
  color: white;
  border: none;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 0.7rem;
  cursor: pointer;
  transition: background 0.2s;
}

.action-btn:hover {
  background: #6b7280;
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

/* СТИЛИ ДЛЯ СВОБОДНЫХ МЕСТ */
.empty-slot {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  opacity: 1; /* Полная непрозрачность внутри */
}

.empty-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: #4b5563; /* Темнее серый */
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2rem;
  font-weight: bold;
  border: 2px dashed #6b7280; /* Пунктирная граница */
}

.empty-text {
  color: #d1d5db; /* Светлее текст */
  font-size: 0.8rem;
  font-weight: bold;
}
</style>