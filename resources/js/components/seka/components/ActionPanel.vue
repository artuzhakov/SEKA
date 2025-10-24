<template>
  <div class="action-panel">
    <h3>🎯 Доступные действия</h3>
    
    <div class="actions-grid">
      <ActionButton
        v-for="action in availableActions"
        :key="action"
        :action="action"
        :current-player-info="currentPlayerInfo"
        :current-max-bet="currentMaxBet"
        @click="handleAction(action)"
      />
    </div>
  </div>
</template>

<script setup>
import ActionButton from './ActionButton.vue'

const props = defineProps({
  availableActions: {
    type: Array,
    default: () => []
  },
  currentPlayerInfo: {
    type: Object,
    default: null
  },
  currentMaxBet: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['take-action', 'show-raise-modal'])

const handleAction = (action) => {
  if (action === 'raise') {
    emit('show-raise-modal')
  } else {
    emit('take-action', action)
  }
}
</script>

<style scoped>
.action-panel {
  background: white;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 10px;
}
</style>