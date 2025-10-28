<template>
  <div class="min-h-screen bg-gray-900">
    <SekaGame :game-id="gameId" />
  </div>
</template>

<script setup>
import { defineProps } from 'vue'
import SekaGame from '@/сomponents/seka/SekaGame.vue'

const props = defineProps({
  gameId: Number,
  auth: Object
})
</script>