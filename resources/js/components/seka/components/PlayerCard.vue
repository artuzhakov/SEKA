<template>
  <div class="player-card" :class="{ 'current-turn': isCurrentTurn }">
    <div class="player-header">
      <span class="player-name">{{ player.name || `Player ${player.position}` }}</span>
      <span class="player-status" :class="player.status">{{ player.status }}</span>
    </div>
    
    <div class="player-stats">
      <div class="stat">
        <span class="label">Баланс:</span>
        <span class="value">{{ player.balance }}₽</span>
      </div>
      <div class="stat" v-if="player.current_bet > 0">
        <span class="label">Ставка:</span>
        <span class="value bet">{{ player.current_bet }}₽</span>
      </div>
    </div>
    
    <div class="player-ready" v-if="player.is_ready !== undefined">
      <span :class="{ ready: player.is_ready }">
        {{ player.is_ready ? '✅ Готов' : '⏳ Ожидание' }}
      </span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  player: {
    type: Object,
    required: true
  },
  isCurrentTurn: {
    type: Boolean,
    default: false
  }
})
</script>

<style scoped>
.player-card {
  background: white;
  padding: 15px;
  border-radius: 10px;
  border: 2px solid #e2e8f0;
  transition: all 0.3s ease;
}

.player-card.current-turn {
  border-color: #f59e0b;
  background: #fffbeb;
  box-shadow: 0 0 10px rgba(245, 158, 11, 0.3);
}

.player-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.player-name {
  font-weight: bold;
  color: #1f2937;
}

.player-status {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 12px;
  text-transform: uppercase;
}

.player-status.active {
  background: #d1fae5;
  color: #065f46;
}

.player-status.folded {
  background: #fef2f2;
  color: #dc2626;
}

.player-status.dark {
  background: #1f2937;
  color: white;
}

.player-stats {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.stat {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
}

.label {
  color: #6b7280;
}

.value {
  font-weight: bold;
  color: #1f2937;
}

.value.bet {
  color: #f59e0b;
}

.player-ready {
  margin-top: 8px;
  text-align: center;
}

.ready {
  color: #059669;
  font-weight: bold;
}
</style>