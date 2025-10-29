<template>
  <div class="debug-panel">
    <div class="debug-header">
      <h3>🔧 Отладочная информация</h3>
      <button @click="toggleExpanded" class="toggle-btn">
        {{ isExpanded ? 'Свернуть' : 'Развернуть' }}
      </button>
    </div>
    
    <div v-if="isExpanded" class="debug-content">
      <div class="debug-section">
        <h4>Состояние игры</h4>
        <div class="debug-grid">
          <DebugItem label="Статус" :value="gameStatus" />
          <DebugItem label="Раунд" :value="currentRound" />
          <DebugItem label="Позиция хода" :value="currentPlayerPosition" />
          <DebugItem label="ID игрока" :value="currentPlayerId" />
          <DebugItem label="Мой ход?" :value="isMyTurn ? 'Да' : 'Нет'" />
          <DebugItem label="Активных игроков" :value="activePlayersCount" />
        </div>
      </div>
      
      <div class="debug-section">
        <h4>Доступные действия</h4>
        <div class="actions-list">
          <span 
            v-for="action in availableActions" 
            :key="action"
            class="action-tag"
          >
            {{ action }}
          </span>
          <span v-if="availableActions.length === 0" class="no-actions">
            Нет доступных действий
          </span>
        </div>
      </div>
      
      <div class="debug-section">
        <h4>Данные игроков</h4>
        <div class="players-debug">
          <div 
            v-for="player in players"
            :key="player.id"
            class="player-debug"
          >
            <strong>Player {{ player.position }}</strong>
            <div>ID: {{ player.id }}</div>
            <div>Баланс: {{ player.balance }}</div>
            <div>Ставка: {{ player.current_bet }}</div>
            <div>Статус: {{ player.status }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import DebugItem from './DebugItem.vue'

const props = defineProps({
  gameStatus: String,
  currentPlayerPosition: Number,
  currentPlayerId: Number,
  isMyTurn: Boolean,
  activePlayersCount: Number,
  currentRound: Number,
  availableActions: Array,
  players: Array
})

const isExpanded = ref(true)

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
}
</script>

<style scoped>
.debug-panel {
  background: #1e293b;
  color: white;
  padding: 20px;
  border-radius: 10px;
  font-family: 'Courier New', monospace;
}

.debug-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.toggle-btn {
  background: #374151;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.debug-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.debug-section h4 {
  margin-bottom: 10px;
  color: #9ca3af;
}

.debug-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 10px;
}

.actions-list {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.action-tag {
  background: #4b5563;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.no-actions {
  color: #9ca3af;
  font-style: italic;
}

.players-debug {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 10px;
}

.player-debug {
  background: #374151;
  padding: 10px;
  border-radius: 6px;
  font-size: 12px;
}
</style>