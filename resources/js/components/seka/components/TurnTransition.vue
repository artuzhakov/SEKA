<!-- components/TurnTransition.vue -->
<template>
  <div v-if="isVisible" class="turn-transition" :class="transitionClasses">
    <div class="transition-content">
      <!-- АНИМАЦИЯ ПЕРЕХОДА ХОДА -->
      <div class="turn-animation">
        <div class="player-turn previous" v-if="previousPlayer">
          <div class="player-avatar">
            {{ getInitials(previousPlayer.name) }}
          </div>
          <div class="player-name">{{ previousPlayer.name }}</div>
          <div class="turn-label">← Завершил ход</div>
        </div>

        <div class="turn-arrow">
          <div class="arrow-icon">🎯</div>
          <div class="arrow-line"></div>
        </div>

        <div class="player-turn current" v-if="currentPlayer">
          <div class="player-avatar current-avatar">
            {{ getInitials(currentPlayer.name) }}
          </div>
          <div class="player-name">{{ currentPlayer.name }}</div>
          <div class="turn-label">Ходит →</div>
        </div>
      </div>

      <!-- ИНФОРМАЦИЯ О ДОСТУПНЫХ ДЕЙСТВИЯХ -->
      <div v-if="showActions && currentPlayerActions.length > 0" class="available-actions">
        <div class="actions-title">Доступные действия:</div>
        <div class="actions-list">
          <span v-for="action in currentPlayerActions" :key="action" class="action-tag">
            {{ getActionText(action) }}
          </span>
        </div>
      </div>

      <!-- ТАЙМЕР ХОДА -->
      <div v-if="turnTimeLeft > 0" class="turn-timer-info">
        <div class="timer-label">Время на ход:</div>
        <div class="timer-value">{{ formatTime(turnTimeLeft) }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  isVisible: Boolean,
  previousPlayer: Object,
  currentPlayer: Object,
  currentPlayerActions: {
    type: Array,
    default: () => []
  },
  turnTimeLeft: Number,
  showActions: {
    type: Boolean,
    default: true
  }
})

// 🎯 АНИМАЦИЯ ПОЯВЛЕНИЯ
const isAnimating = ref(false)

const transitionClasses = computed(() => ({
  'animating': isAnimating.value,
  'with-actions': props.showActions && props.currentPlayerActions.length > 0
}))

// 🎯 ЗАПУСК АНИМАЦИИ ПРИ ПОЯВЛЕНИИ
watch(() => props.isVisible, (visible) => {
  if (visible) {
    isAnimating.value = true
    setTimeout(() => {
      isAnimating.value = false
    }, 800)
  }
})

// 🎯 ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
const getInitials = (name) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
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

const formatTime = (seconds) => {
  if (seconds <= 0) return '0:00'
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}
</script>

<style scoped>
.turn-transition {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 9999;
  background: rgba(0, 0, 0, 0.95);
  border: 3px solid #fbbf24;
  border-radius: 20px;
  padding: 30px;
  color: white;
  text-align: center;
  animation: fadeInScale 0.5s ease;
}

.turn-transition.animating {
  animation: pulseGlow 0.8s ease;
}

.transition-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
  align-items: center;
}

/* АНИМАЦИЯ ПЕРЕХОДА ХОДА */
.turn-animation {
  display: flex;
  align-items: center;
  gap: 40px;
  margin-bottom: 20px;
}

.player-turn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  min-width: 120px;
}

.player-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 1.2rem;
  color: white;
}

.current-avatar {
  background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%);
  border: 3px solid #fbbf24;
  box-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
  animation: avatarPulse 2s ease-in-out infinite;
}

.player-name {
  font-weight: bold;
  font-size: 1.1rem;
}

.turn-label {
  font-size: 0.9rem;
  color: #d1d5db;
}

/* СТРЕЛКА ПЕРЕХОДА */
.turn-arrow {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.arrow-icon {
  font-size: 2rem;
  animation: bounce 1s ease-in-out infinite;
}

.arrow-line {
  width: 2px;
  height: 40px;
  background: linear-gradient(to bottom, #fbbf24, transparent);
}

/* ДОСТУПНЫЕ ДЕЙСТВИЯ */
.available-actions {
  background: rgba(251, 191, 36, 0.1);
  border: 1px solid #fbbf24;
  border-radius: 10px;
  padding: 15px;
  margin-top: 10px;
}

.actions-title {
  font-weight: bold;
  margin-bottom: 8px;
  color: #fbbf24;
}

.actions-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  justify-content: center;
}

.action-tag {
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid #3b82f6;
  border-radius: 12px;
  padding: 4px 8px;
  font-size: 0.8rem;
  font-weight: bold;
}

/* ТАЙМЕР ХОДА */
.turn-timer-info {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid #10b981;
  border-radius: 10px;
  padding: 10px 15px;
}

.timer-label {
  font-weight: bold;
  color: #10b981;
}

.timer-value {
  font-size: 1.2rem;
  font-weight: bold;
  color: #fbbf24;
}

/* АНИМАЦИИ */
@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: translate(-50%, -50%) scale(0.8);
  }
  to {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
  }
}

@keyframes pulseGlow {
  0%, 100% {
    box-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
  }
  50% {
    box-shadow: 0 0 40px rgba(251, 191, 36, 0.8);
  }
}

@keyframes avatarPulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 0 30px rgba(251, 191, 36, 0.8);
  }
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

/* АДАПТИВНОСТЬ */
@media (max-width: 768px) {
  .turn-transition {
    padding: 20px;
    margin: 20px;
    width: calc(100% - 40px);
  }
  
  .turn-animation {
    gap: 20px;
  }
  
  .player-turn {
    min-width: 100px;
  }
  
  .player-avatar {
    width: 50px;
    height: 50px;
    font-size: 1rem;
  }
  
  .arrow-icon {
    font-size: 1.5rem;
  }
}
</style>