<template>
  <div class="debug-item">
    <span class="debug-label">{{ label }}:</span>
    <span class="debug-value">{{ value }}</span>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: [String, Number, Boolean]
})
</script>

<style scoped>
.debug-item {
  display: flex;
  justify-content: space-between;
  padding: 5px;
  background: rgba(255,255,255,0.1);
  border-radius: 4px;
}

.debug-label {
  color: #9ca3af;
}

.debug-value {
  font-weight: bold;
  color: white;
}
</style>