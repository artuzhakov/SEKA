<template>
  <div class="player-spot" :class="{ 'current-turn': isCurrentTurn }">
    <div class="spot-header">
      <span class="player-name">{{ player.name || `Player ${player.position}` }}</span>
      <span class="player-bet" v-if="player.current_bet > 0">
        {{ player.current_bet }}₽
      </span>
    </div>
    
    <div class="cards-container">
      <div 
        v-for="(card, index) in cards" 
        :key="index"
        class="card"
        :class="{ 'hidden': !showAllCards && player.id !== currentPlayerId }"
      >
        {{ showAllCards || player.id === currentPlayerId ? card : '🂠' }}
      </div>
      
      <div v-if="!cards || cards.length === 0" class="no-cards">
        Карты не разданы
      </div>
    </div>
    
    <div class="spot-status">
      <span v-if="player.status === 'folded'" class="folded">📭 Сбросил</span>
      <span v-else-if="player.status === 'dark'" class="dark">🌑 Темная</span>
      <span v-else-if="isCurrentTurn" class="thinking">🤔 Думает...</span>
    </div>
  </div>
</template>

<script setup>
import { useGameState } from '@/components/seka/composables/useGameState'

const { currentPlayerId } = useGameState()

defineProps({
  player: {
    type: Object,
    required: true
  },
  cards: {
    type: Array,
    default: () => []
  },
  isCurrentTurn: {
    type: Boolean,
    default: false
  },
  showAllCards: {
    type: Boolean,
    default: false
  }
})
</script>

<style scoped>
.player-spot {
  background: rgba(255, 255, 255, 0.1);
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 15px;
  padding: 20px;
  text-align: center;
  color: white;
  transition: all 0.3s ease;
}

.player-spot.current-turn {
  border-color: #fbbf24;
  background: rgba(251, 191, 36, 0.2);
  box-shadow: 0 0 15px rgba(251, 191, 36, 0.4);
}

.spot-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.player-name {
  font-weight: bold;
  font-size: 16px;
}

.player-bet {
  background: #f59e0b;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: bold;
}

.cards-container {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-bottom: 15px;
  min-height: 80px;
  align-items: center;
}

.card {
  width: 60px;
  height: 80px;
  background: white;
  color: black;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: bold;
  border: 2px solid #d1d5db;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.card.hidden {
  background: #1f2937;
  color: white;
  border-color: #374151;
}

.no-cards {
  color: rgba(255, 255, 255, 0.6);
  font-style: italic;
}

.spot-status {
  font-size: 14px;
}

.folded {
  color: #ef4444;
}

.dark {
  color: #fbbf24;
}

.thinking {
  color: #60a5fa;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>