<!-- components/GameTimers.vue -->
<template>
  <div class="game-timers" :class="{ 'mobile': isMobile }">
    <!-- Таймер хода -->
    <div v-if="showTurnTimer" class="timer-container turn-timer" :class="{ critical: isTurnCritical }">
      <div class="timer-header">
        <span class="timer-icon">⏰</span>
        <span class="timer-label">Ход игрока:</span>
        <span class="player-name">{{ currentPlayerName }}</span>
      </div>
      <div class="timer-display">
        <div class="time-left">{{ formatTime(turnTimeLeft) }}</div>
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: `${turnProgress}%` }"></div>
        </div>
      </div>
    </div>

    <!-- Таймер готовности -->
    <div v-if="showReadyTimer" class="timer-container ready-timer" :class="{ critical: isReadyCritical }">
      <div class="timer-header">
        <span class="timer-icon">✅</span>
        <span class="timer-label">Подтверждение готовности</span>
      </div>
      <div class="timer-display">
        <div class="time-left">{{ formatTime(readyTimeLeft) }}</div>
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: `${readyProgress}%` }"></div>
        </div>
      </div>
    </div>

    <!-- Таймер Reveal -->
    <div v-if="showRevealTimer" class="timer-container reveal-timer">
      <div class="timer-header">
        <span class="timer-icon">🎴</span>
        <span class="timer-label">Сравнение карт</span>
      </div>
      <div class="timer-display">
        <div class="time-left">{{ formatTime(revealTimeLeft) }}</div>
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: `${revealProgress}%` }"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  turnTimeLeft: Number,
  readyTimeLeft: Number,
  revealTimeLeft: Number,
  turnProgress: Number,
  readyProgress: Number,
  revealProgress: Number,
  isTurnCritical: Boolean,
  isReadyCritical: Boolean,
  isRevealCritical: Boolean,
  gameStatus: String,
  currentPlayerName: String
})

const isMobile = ref(false)

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ДЛЯ ОТОБРАЖЕНИЯ
const showTurnTimer = computed(() => {
  return props.turnTimeLeft > 0 && props.gameStatus === 'active'
})

const showReadyTimer = computed(() => {
  return props.readyTimeLeft > 0 && props.gameStatus === 'waiting'
})

const showRevealTimer = computed(() => {
  return props.revealTimeLeft > 0
})

// 🎯 ФОРМАТИРОВАНИЕ ВРЕМЕНИ
const formatTime = (seconds) => {
  if (seconds <= 0) return '0:00'
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

// 🎯 ОПРЕДЕЛЕНИЕ УСТРОЙСТВА
const checkDevice = () => {
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  checkDevice()
  window.addEventListener('resize', checkDevice)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkDevice)
})
</script>

<style scoped>
.game-timers {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 250px;
}

.game-timers.mobile {
  position: relative;
  top: auto;
  right: auto;
  width: 100%;
  padding: 10px;
}

.timer-container {
  background: rgba(0, 0, 0, 0.9);
  border: 2px solid;
  border-radius: 12px;
  padding: 12px;
  color: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
}

.turn-timer {
  border-color: #3b82f6;
}

.turn-timer.critical {
  border-color: #ef4444;
  animation: pulse 1s infinite;
}

.ready-timer {
  border-color: #10b981;
}

.ready-timer.critical {
  border-color: #f59e0b;
  animation: pulse 1s infinite;
}

.reveal-timer {
  border-color: #8b5cf6;
}

.timer-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 0.9rem;
}

.timer-icon {
  font-size: 1rem;
}

.timer-label {
  flex: 1;
  font-weight: 500;
}

.player-name {
  font-weight: bold;
  color: #fbbf24;
}

.timer-display {
  display: flex;
  align-items: center;
  gap: 10px;
}

.time-left {
  font-size: 1.2rem;
  font-weight: bold;
  min-width: 45px;
  text-align: center;
}

.turn-timer .time-left {
  color: #3b82f6;
}

.turn-timer.critical .time-left {
  color: #ef4444;
}

.ready-timer .time-left {
  color: #10b981;
}

.ready-timer.critical .time-left {
  color: #f59e0b;
}

.reveal-timer .time-left {
  color: #8b5cf6;
}

.progress-bar {
  flex: 1;
  height: 6px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 1s linear;
}

.turn-timer .progress-fill {
  background: #3b82f6;
}

.turn-timer.critical .progress-fill {
  background: #ef4444;
}

.ready-timer .progress-fill {
  background: #10b981;
}

.ready-timer.critical .progress-fill {
  background: #f59e0b;
}

.reveal-timer .progress-fill {
  background: #8b5cf6;
}

@keyframes pulse {
  0%, 100% { 
    opacity: 1;
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
  }
  50% { 
    opacity: 0.8;
    box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
  }
}

/* Мобильная адаптация */
@media (max-width: 768px) {
  .game-timers:not(.mobile) {
    display: none;
  }
  
  .game-timers.mobile {
    gap: 8px;
  }
  
  .timer-container {
    padding: 10px;
  }
  
  .timer-header {
    font-size: 0.8rem;
  }
  
  .time-left {
    font-size: 1rem;
  }
}
</style>