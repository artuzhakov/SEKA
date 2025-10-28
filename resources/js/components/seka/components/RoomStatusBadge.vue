<!-- resources/js/components/seka/components/RoomStatusBadge.vue -->
<template>
  <span 
    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
    :class="badgeClasses"
  >
    <span>{{ statusIcon }}</span>
    {{ statusText }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: true,
    validator: (value) => ['available', 'full'].includes(value)
  }
})

const statusIcon = computed(() => {
  return props.status === 'available' ? '🟢' : '🔴'
})

const statusText = computed(() => {
  return props.status === 'available' ? 'Свободен' : 'Заполнен'
})

const badgeClasses = computed(() => {
  if (props.status === 'available') {
    return 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
  } else {
    return 'bg-red-500/20 text-red-300 border border-red-500/30'
  }
})
</script>