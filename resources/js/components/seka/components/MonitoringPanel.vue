<template>
  <div class="monitoring-panel">
    <h3>📊 Мониторинг игры</h3>
    
    <div class="stats-grid">
      <div class="stat-item">
        <span class="stat-label">Статус игры:</span>
        <span class="stat-value" :class="gameStatus">{{ gameStatus }}</span>
      </div>
      
      <div class="stat-item">
        <span class="stat-label">Текущий раунд:</span>
        <span class="stat-value">{{ currentRound }}/3</span>
      </div>
      
      <div class="stat-item">
        <span class="stat-label">Активные игроки:</span>
        <span class="stat-value">{{ activePlayersCount }}</span>
      </div>
      
      <div class="stat-item">
        <span class="stat-label">Банк:</span>
        <span class="stat-value">{{ bank }}₽</span>
      </div>
      
      <div class="stat-item">
        <span class="stat-label">Макс. ставка:</span>
        <span class="stat-value">{{ currentMaxBet }}₽</span>
      </div>
    </div>
    
    <div class="health-indicators">
      <div class="health-item" :class="{ healthy: isGameHealthy }">
        <span class="health-dot"></span>
        <span>Состояние игры</span>
      </div>
      <div class="health-item" :class="{ healthy: arePlayersHealthy }">
        <span class="health-dot"></span>
        <span>Данные игроков</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  gameStatus: String,
  activePlayersCount: Number,
  currentRound: Number,
  bank: Number,
  currentMaxBet: Number,
  players: Array
})

const isGameHealthy = computed(() => {
  return props.gameStatus && props.currentRound > 0 && props.currentRound <= 3
})

const arePlayersHealthy = computed(() => {
  return props.players && props.players.length > 0 && 
         props.players.every(p => p.balance >= 0)
})
</script>

<style scoped>
.monitoring-panel {
  background: #1f2937;
  color: white;
  padding: 20px;
  border-radius: 10px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.stat-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.stat-label {
  font-size: 12px;
  opacity: 0.7;
}

.stat-value {
  font-weight: bold;
  font-size: 16px;
}

.health-indicators {
  display: flex;
  gap: 15px;
}

.health-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  opacity: 0.7;
}

.health-item.healthy {
  opacity: 1;
}

.health-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #ef4444;
}

.health-item.healthy .health-dot {
  background: #10b981;
}
</style>