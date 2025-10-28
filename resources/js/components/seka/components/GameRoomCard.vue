<!-- resources/js/Components/seka/components/GameRoomCard.vue -->
<template>
  <div 
    class="game-room-card rounded-lg border-2 p-4 transition-all duration-200 hover:shadow-lg"
    :class="cardClasses"
  >
    <!-- Header -->
    <div class="flex justify-between items-start mb-3">
      <div>
        <h3 class="font-bold text-lg mb-1" :class="titleClasses">{{ table.name }}</h3>
        <div class="text-xs text-gray-300">{{ table.players }} игрока</div>
      </div>
      <RoomStatusBadge :status="table.status" />
    </div>

    <!-- Bets Info -->
    <div class="space-y-2 mb-3">
      <div class="flex justify-between items-center text-sm">
        <span class="text-gray-300">Ставки:</span>
        <span class="font-semibold text-white">{{ table.minBet }} - {{ table.maxBet }}</span>
      </div>
      <div class="flex justify-between items-center text-sm">
        <span class="text-gray-300">Вход:</span>
        <span class="font-semibold text-emerald-400">{{ table.buyIn }}</span>
      </div>
    </div>

    <!-- Action Button -->
    <button
      v-if="table.status === 'available'"
      @click="$emit('join', table.id)"
      class="w-full py-2 px-3 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded transition-colors duration-200 flex items-center justify-center gap-1"
    >
      <span>🎴</span>
      Присоединиться
    </button>
    
    <div
      v-else
      class="w-full py-2 px-3 bg-gray-600 text-gray-400 text-sm font-medium rounded flex items-center justify-center gap-1"
    >
      <span>🔴</span>
      Стол заполнен
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import RoomStatusBadge from '@/components/seka/components/RoomStatusBadge.vue'

const props = defineProps({
  table: {
    type: Object,
    required: true
  }
})

defineEmits(['join'])

const cardClasses = computed(() => {
  const base = 'border-2'
  if (props.table.status === 'full') {
    return `${base} border-gray-600 bg-gray-800/50`
  }
  
  const colorMap = {
    green: 'border-emerald-500 bg-gradient-to-br from-gray-800 to-emerald-900/20 hover:border-emerald-400',
    blue: 'border-blue-500 bg-gradient-to-br from-gray-800 to-blue-900/20 hover:border-blue-400',
    purple: 'border-purple-500 bg-gradient-to-br from-gray-800 to-purple-900/20 hover:border-purple-400',
    gold: 'border-yellow-500 bg-gradient-to-br from-gray-800 to-yellow-900/20 hover:border-yellow-400'
  }
  
  return colorMap[props.table.color] || colorMap.green
})

const titleClasses = computed(() => {
  if (props.table.status === 'full') {
    return 'text-gray-400'
  }
  
  const colorMap = {
    green: 'text-emerald-400',
    blue: 'text-blue-400',
    purple: 'text-purple-400',
    gold: 'text-yellow-400'
  }
  
  return colorMap[props.table.color] || colorMap.green
})
</script>

<style scoped>
.game-room-card {
  backdrop-filter: blur(8px);
  min-height: 140px;
}

.game-room-card:hover {
  transform: translateY(-1px);
}
</style>