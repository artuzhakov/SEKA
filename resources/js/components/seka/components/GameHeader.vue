<template>
  <div class="game-header">
    <div class="header-content">
      <h1>🎴 SEKA Game #{{ gameId }}</h1>
      <div class="game-info">
        <span class="status-badge" :class="gameStatus">{{ gameStatus }}</span>
        <span class="round">Round: {{ currentRound }}</span>
        <span class="user">Player: {{ user?.name || 'Guest' }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  gameId: Number,
  gameStatus: String,
  currentRound: Number,
  user: Object
})
</script>

<style scoped>
.game-header {
  background: white;
  padding: 1rem;
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.game-info {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
}

.status-badge.waiting {
  background: #e2e8f0;
  color: #475569;
}

.status-badge.bidding {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}
</style>