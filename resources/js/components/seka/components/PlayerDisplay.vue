<template>
  <div class="player-display" :class="playerClasses">
    <!-- Основная информация -->
    <div class="player-main">
      <span class="player-name">{{ player.name }}</span>
      <span class="player-balance">{{ player.balance }}🪙</span>
    </div>
    
    <!-- Статусы -->
    <div class="player-status">
      <span v-if="isCurrent" class="status current">🎯 ХОДИТ</span>
      <span v-if="isDealer" class="status dealer">🎫 ДИЛЕР</span>
      <span v-if="player.isFolded" class="status folded">❌ ПАС</span>
      <span v-if="player.isDark" class="status dark">🌙 ТЕМНАЯ</span>
    </div>
    
    <!-- Действие (для тестирования) -->
    <div class="test-actions" v-if="showDebug">
      <button @click="$emit('player-action', 'check')">⏭️</button>
      <button @click="$emit('player-action', 'call')">📞</button>
      <button @click="$emit('player-action', 'fold')">❌</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  player: {
    type: Object,
    required: true
  },
  isCurrent: {
    type: Boolean,
    default: false
  },
  isDealer: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['player-action'])

const showDebug = true

const playerClasses = computed(() => ({
  'current': props.isCurrent,
  'dealer': props.isDealer,
  'folded': props.player.isFolded,
  'dark': props.player.isDark
}))
</script>

<style scoped>
.player-display {
  background: rgba(45, 55, 72, 0.9);
  border: 2px solid #4a5568;
  border-radius: 10px;
  padding: 15px;
  color: white;
  transition: all 0.3s ease;
}

.player-display.current {
  border-color: #48bb78;
  background: rgba(72, 187, 120, 0.2);
}

.player-display.dealer {
  border-color: #d69e2e;
}

.player-display.folded {
  opacity: 0.6;
}

.player-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.player-name {
  font-weight: bold;
  font-size: 14px;
}

.player-balance {
  font-size: 12px;
  color: #f6e05e;
}

.player-status {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}

.status {
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 8px;
  font-weight: bold;
}

.status.current {
  background: #48bb78;
  color: white;
}

.status.dealer {
  background: #d69e2e;
  color: white;
}

.status.folded {
  background: #e53e3e;
  color: white;
}

.status.dark {
  background: #805ad5;
  color: white;
}

.test-actions {
  display: flex;
  gap: 5px;
  margin-top: 8px;
  justify-content: center;
}

.test-actions button {
  padding: 4px 8px;
  background: #4a5568;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}
</style>