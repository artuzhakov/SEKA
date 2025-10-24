<template>
  <div class="game-table">
    <div class="table-header">
      <h2>🎴 Игровой стол</h2>
      <div class="table-stats">
        <span class="bank">🏦 Банк: {{ bank }}</span>
        <span class="round" v-if="currentRound">Раунд: {{ currentRound }}/3</span>
      </div>
    </div>

    <div class="players-grid" v-if="players.length > 0">
      <PlayerCard
        v-for="player in players"
        :key="player.id"
        :player="player"
        :is-current-turn="player.position === currentPlayerPosition"
      />
    </div>

    <div class="players-cards">
      <PlayerSpot
        v-for="player in players"
        :key="player.id"
        :player="player"
        :cards="playerCards[player.id]"
        :is-current-turn="player.position === currentPlayerPosition"
        :show-all-cards="showAllCards"
      />
    </div>
  </div>
</template>

<script setup>
import PlayerCard from './PlayerCard.vue'
import PlayerSpot from './PlayerSpot.vue'

defineProps({
  players: {
    type: Array,
    default: () => []
  },
  playerCards: {
    type: Object,
    default: () => ({})
  },
  currentPlayerPosition: {
    type: Number,
    default: 1
  },
  bank: {
    type: Number,
    default: 0
  },
  currentRound: {
    type: Number,
    default: 1
  },
  gameStatus: {
    type: String,
    default: 'waiting'
  },
  showAllCards: {
    type: Boolean,
    default: false
  }
})
</script>

<style scoped>
.game-table {
  background: #2d5016;
  color: white;
  padding: 30px;
  border-radius: 15px;
  text-align: center;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.table-stats {
  display: flex;
  gap: 20px;
  font-size: 18px;
}

.players-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 30px;
}

.players-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

@media (max-width: 768px) {
  .game-table {
    padding: 20px;
  }
  
  .table-header {
    flex-direction: column;
    gap: 15px;
  }
  
  .players-grid,
  .players-cards {
    grid-template-columns: 1fr;
  }
}
</style>