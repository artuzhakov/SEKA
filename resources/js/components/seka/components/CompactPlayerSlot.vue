<template>
  <div class="compact-player-slot" :class="playerClasses">
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
      <button v-if="!player.isReady" 
              class="ready-btn"
              @click="handleReady">
        Готов
      </button>
      <div v-else class="ready-badge">✓</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

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
  }
})

const emit = defineEmits(['player-action', 'player-ready'])

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
const playerClasses = computed(() => ({
  'current-turn': props.isCurrentTurn,
  'dealer': props.isDealer,
  'empty': !props.player.id,
  'ready': props.player.isReady
}))

const playerInitials = computed(() => {
  if (!props.player.id) return '+'
  return props.player.name.split(' ').map(n => n[0]).join('').toUpperCase()
})

const availableActions = computed(() => [
  'check', 'call', 'raise', 'fold', 'dark'
])

// 🎯 МЕТОДЫ
const getActionText = (action) => {
  const actions = {
    'check': 'Пропуск',
    'call': 'Поддержать', 
    'raise': 'Повысить',
    'fold': 'Пас',
    'dark': 'Темная'
  }
  return actions[action] || action
}

const handleAction = (action) => {
  emit('player-action', action)
}

const handleReady = () => {
  emit('player-ready')
}
</script>

<style scoped>
.compact-player-slot {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 10px;
  border-radius: 12px;
  background: rgba(0, 0, 0, 0.7);
  border: 2px solid transparent;
  transition: all 0.3s ease;
  min-width: 120px;
}

.compact-player-slot.current-turn {
  border-color: #fbbf24;
  box-shadow: 0 0 15px rgba(251, 191, 36, 0.4);
}

.compact-player-slot.dealer {
  border-color: #3b82f6;
}

.compact-player-slot.empty {
  opacity: 0.5;
}

.compact-player-slot.ready {
  border-color: #10b981;
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
}

.avatar-placeholder {
  font-size: 1.5rem;
}

.dealer-indicator {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #3b82f6;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  font-size: 0.7rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.turn-indicator {
  position: absolute;
  top: -5px;
  left: -5px;
  font-size: 1rem;
}

/* Информация */
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
}

/* Карты */
.player-cards {
  display: flex;
  gap: 4px;
}

.card-slot {
  width: 30px;
  height: 42px;
  border-radius: 4px;
  overflow: hidden;
}

.card-front {
  width: 100%;
  height: 100%;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: bold;
  border: 1px solid #ccc;
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
</style>