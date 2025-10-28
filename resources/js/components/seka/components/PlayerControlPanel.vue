<template>
  <div class="player-control-panel">
    <h3>🎮 Управление игроками</h3>
    
    <div class="players-list">
      <div
        v-for="player in players"
        :key="player.id"
        class="player-item"
        :class="{ 
          active: player.id === currentPlayerId,
          'current-turn': player.position === currentPlayerPosition
        }"
        @click="$emit('switch-player', player.id)"
      >
        <div class="player-info">
          <span class="player-name">{{ player.name || `Player ${player.position}` }}</span>
          <span class="player-status">{{ player.status }}</span>
        </div>
        <div class="player-stats">
          <span class="balance">{{ player.balance }}₽</span>
          <span class="bet" v-if="player.current_bet > 0">{{ player.current_bet }}₽</span>
        </div>
        <div class="player-indicators">
          <span v-if="player.id === currentPlayerId" class="indicator current">Вы</span>
          <span v-if="player.position === currentPlayerPosition" class="indicator turn">Ход</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  currentPlayerId: Number,
  players: Array,
  currentPlayerPosition: Number
})

defineEmits(['switch-player'])
</script>

<style scoped>
.player-control-panel {
  background: white;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.players-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.player-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.player-item:hover {
  border-color: #cbd5e0;
  background: #f7fafc;
}

.player-item.active {
  border-color: #3b82f6;
  background: #eff6ff;
}

.player-item.current-turn {
  border-color: #f59e0b;
  background: #fffbeb;
}

.player-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.player-name {
  font-weight: bold;
  color: #1f2937;
}

.player-status {
  font-size: 12px;
  color: #6b7280;
  text-transform: uppercase;
}

.player-stats {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.balance {
  font-weight: bold;
  color: #059669;
}

.bet {
  font-size: 12px;
  color: #d97706;
}

.player-indicators {
  display: flex;
  gap: 5px;
}

.indicator {
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 10px;
  color: white;
  font-weight: bold;
}

.indicator.current {
  background: #3b82f6;
}

.indicator.turn {
  background: #f59e0b;
}
</style>