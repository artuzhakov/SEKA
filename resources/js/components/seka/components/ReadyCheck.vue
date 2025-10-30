<template>
  <div class="ready-check-overlay" v-if="isActive">
    <div class="ready-check-panel">
      <!-- Заголовок -->
      <div class="panel-header">
        <h2>🎯 Подготовка к игре SEKA</h2>
        <div class="timer-display">
          <div class="timer-circle">
            <span class="timer-value">{{ timeRemaining }}</span>
            <span class="timer-label">секунд</span>
          </div>
        </div>
      </div>

      <!-- Статус игроков -->
      <div class="players-status">
        <h3>Статус игроков:</h3>
        <div class="players-list">
          <div 
            v-for="player in players" 
            :key="player.id"
            class="player-status-item"
            :class="{ 'ready': player.isReady, 'current': player.isCurrent }"
          >
            <div class="player-avatar">
              {{ getPlayerEmoji(player) }}
            </div>
            <div class="player-info">
              <span class="player-name">{{ player.name }}</span>
              <span class="player-balance">{{ player.balance }}🪙</span>
            </div>
            <div class="status-indicator">
              <span v-if="player.isReady" class="status ready">✅ Готов</span>
              <span v-else class="status waiting">⏳ Ожидание...</span>
              <div v-if="player.readyTimeRemaining" class="time-remaining">
                {{ player.readyTimeRemaining }}с
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Прогресс и информация -->
      <div class="progress-section">
        <div class="progress-bar">
          <div 
            class="progress-fill" 
            :style="{ width: progressPercentage + '%' }"
          ></div>
        </div>
        <div class="progress-info">
          <span class="ready-count">Готовы: {{ readyCount }}/{{ totalPlayers }}</span>
          <span class="min-players">(минимум 2 игрока)</span>
        </div>
        
        <div v-if="canStart" class="start-notification">
          🚀 Игра начнется автоматически...
        </div>
      </div>

      <!-- Кнопка действий -->
      <div class="action-section">
        <button 
          @click="toggleReady"
          :class="['ready-btn', { 'ready': currentPlayer.isReady }]"
          :disabled="!currentPlayer.id"
        >
          <span class="btn-icon">{{ currentPlayer.isReady ? '✅' : '🎯' }}</span>
          <span class="btn-text">
            {{ currentPlayer.isReady ? 'Вы готовы!' : 'Отметить готовность' }}
          </span>
        </button>

        <button 
          v-if="currentPlayer.isReady"
          @click="toggleReady"
          class="cancel-btn"
        >
          ❌ Отменить готовность
        </button>
      </div>

      <!-- Информация о таймаутах -->
      <div class="timeout-info">
        <div class="info-item">
          <span class="info-icon">⏰</span>
          <span class="info-text">Таймаут готовности: 30 секунд</span>
        </div>
        <div class="info-item">
          <span class="info-icon">⚡</span>
          <span class="info-text">Игра начнется при 2+ готовых игроках</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  players: {
    type: Array,
    default: () => []
  },
  isActive: {
    type: Boolean,
    default: false
  },
  timeRemaining: {
    type: Number,
    default: 30
  }
})

const emit = defineEmits(['player-ready', 'player-cancel-ready', 'timeout'])

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
const currentPlayer = computed(() => {
  return props.players.find(p => p.id === 1) || {} // ID 1 - текущий пользователь
})

const readyCount = computed(() => {
  return props.players.filter(p => p.isReady && p.id).length
})

const totalPlayers = computed(() => {
  return props.players.filter(p => p.id).length // Только занятые места
})

const canStart = computed(() => {
  return readyCount.value >= 2 && totalPlayers.value >= 2
})

const progressPercentage = computed(() => {
  return (readyCount.value / Math.max(totalPlayers.value, 2)) * 100
})

// 🎯 МЕТОДЫ
const getPlayerEmoji = (player) => {
  const emojis = ['👤', '👨', '👩', '🧔', '👱', '🧑']
  return emojis[player.position % emojis.length] || '🎯'
}

const toggleReady = () => {
  if (currentPlayer.value.isReady) {
    emit('player-cancel-ready', currentPlayer.value.id)
  } else {
    emit('player-ready', currentPlayer.value.id)
  }
}

// 🎯 ТАЙМЕР
const timer = ref(null)

onMounted(() => {
  // Запускаем таймер если активен
  if (props.isActive) {
    timer.value = setInterval(() => {
      if (props.timeRemaining <= 0) {
        emit('timeout')
        clearInterval(timer.value)
      }
    }, 1000)
  }
})

onUnmounted(() => {
  if (timer.value) {
    clearInterval(timer.value)
  }
})
</script>

<style scoped>
.ready-check-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 20px;
}

.ready-check-panel {
  background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
  border: 3px solid #4a5568;
  border-radius: 20px;
  padding: 30px;
  max-width: 500px;
  width: 100%;
  color: white;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  animation: slideIn 0.3s ease;
}

/* Заголовок */
.panel-header {
  text-align: center;
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 2px solid #4a5568;
}

.panel-header h2 {
  margin: 0 0 15px 0;
  color: #68d391;
  font-size: 1.8rem;
}

.timer-display {
  display: flex;
  justify-content: center;
}

.timer-circle {
  background: rgba(214, 158, 46, 0.2);
  border: 3px solid #d69e2e;
  border-radius: 50%;
  width: 80px;
  height: 80px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  animation: pulse 2s infinite;
}

.timer-value {
  font-size: 1.5rem;
  font-weight: bold;
  color: #f6e05e;
}

.timer-label {
  font-size: 0.7rem;
  color: #a0aec0;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Список игроков */
.players-status h3 {
  margin: 0 0 15px 0;
  color: #e2e8f0;
  font-size: 1.2rem;
}

.players-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 25px;
}

.player-status-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 12px;
  background: rgba(74, 85, 104, 0.5);
  border-radius: 10px;
  border: 2px solid transparent;
  transition: all 0.3s ease;
}

.player-status-item.ready {
  border-color: #48bb78;
  background: rgba(72, 187, 120, 0.1);
}

.player-status-item.current {
  border-color: #d69e2e;
  background: rgba(214, 158, 46, 0.1);
}

.player-avatar {
  font-size: 1.5rem;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
}

.player-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.player-name {
  font-weight: bold;
  color: #e2e8f0;
}

.player-balance {
  font-size: 0.8rem;
  color: #f6e05e;
}

.status-indicator {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.status {
  font-size: 0.8rem;
  font-weight: bold;
  padding: 4px 8px;
  border-radius: 12px;
}

.status.ready {
  background: #48bb78;
  color: white;
}

.status.waiting {
  background: #e53e3e;
  color: white;
}

.time-remaining {
  font-size: 0.7rem;
  color: #a0aec0;
}

/* Прогресс бар */
.progress-section {
  margin-bottom: 25px;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #4a5568;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 10px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #48bb78, #68d391);
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.9rem;
}

.ready-count {
  color: #68d391;
  font-weight: bold;
}

.min-players {
  color: #a0aec0;
  font-size: 0.8rem;
}

.start-notification {
  text-align: center;
  margin-top: 10px;
  padding: 10px;
  background: rgba(72, 187, 120, 0.2);
  border: 1px solid #48bb78;
  border-radius: 8px;
  color: #68d391;
  font-weight: bold;
  animation: pulse 2s infinite;
}

/* Кнопки действий */
.action-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
}

.ready-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 15px 20px;
  background: linear-gradient(135deg, #48bb78, #68d391);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
}

.ready-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(72, 187, 120, 0.4);
}

.ready-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.ready-btn.ready {
  background: linear-gradient(135deg, #e53e3e, #fc8181);
}

.cancel-btn {
  padding: 10px 15px;
  background: rgba(229, 62, 62, 0.2);
  color: #fc8181;
  border: 1px solid #e53e3e;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.cancel-btn:hover {
  background: rgba(229, 62, 62, 0.3);
}

/* Информация о таймаутах */
.timeout-info {
  background: rgba(74, 85, 104, 0.3);
  border-radius: 10px;
  padding: 15px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.info-item:last-child {
  margin-bottom: 0;
}

.info-icon {
  font-size: 1.2rem;
}

.info-text {
  font-size: 0.9rem;
  color: #a0aec0;
}

/* Анимации */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@keyframes pulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(214, 158, 46, 0.4);
  }
  50% {
    box-shadow: 0 0 0 10px rgba(214, 158, 46, 0);
  }
}

/* Адаптивность */
@media (max-width: 768px) {
  .ready-check-panel {
    padding: 20px;
    margin: 10px;
  }
  
  .panel-header h2 {
    font-size: 1.5rem;
  }
  
  .player-status-item {
    padding: 10px;
    gap: 10px;
  }
  
  .player-avatar {
    font-size: 1.2rem;
    width: 35px;
    height: 35px;
  }
  
  .ready-btn {
    padding: 12px 16px;
    font-size: 1rem;
  }
}

@media (max-width: 480px) {
  .ready-check-panel {
    padding: 15px;
  }
  
  .players-list {
    gap: 8px;
  }
  
  .player-status-item {
    flex-direction: column;
    text-align: center;
    gap: 8px;
  }
  
  .status-indicator {
    align-items: center;
  }
}
</style>