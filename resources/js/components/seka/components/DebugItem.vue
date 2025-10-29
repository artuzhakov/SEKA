<template>
  <div class="debug-item">
    <span class="label">{{ label }}:</span>
    <span class="value">{{ value }}</span>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: [String, Number, Boolean, Array, Object]
})
</script>

<style scoped>
.debug-item {
  display: flex;
  justify-content: space-between;
  padding: 4px 0;
  border-bottom: 1px solid #e2e8f0;
}
.label {
  font-weight: bold;
  color: #4a5568;
}
.value {
  color: #2d3748;
  font-family: monospace;
}
</style>