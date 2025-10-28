<template>
  <div class="modal-overlay">
    <div class="modal-content">
      <h3>Raise Amount</h3>
      <div class="raise-info">
        <p>Min Raise: {{ minRaise }}₽</p>
        <p>Max Raise: {{ maxRaise }}₽</p>
        <p>Your Balance: {{ currentPlayerInfo?.balance }}₽</p>
      </div>
      
      <input 
        v-model.number="localRaiseAmount"
        type="number"
        :min="minRaise"
        :max="maxRaise"
        class="raise-input"
      >
      
      <div class="modal-actions">
        <button @click="$emit('execute-raise', localRaiseAmount)" class="confirm-btn">
          Confirm Raise
        </button>
        <button @click="$emit('cancel')" class="cancel-btn">
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  minRaise: Number,
  maxRaise: Number,
  currentPlayerInfo: Object,
  currentMaxBet: Number
})

defineEmits(['execute-raise', 'cancel'])

const localRaiseAmount = ref(props.minRaise)

watch(() => props.minRaise, (newVal) => {
  localRaiseAmount.value = newVal
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 10px;
  min-width: 400px;
}

.raise-info {
  background: #f3f4f6;
  padding: 1rem;
  border-radius: 5px;
  margin: 1rem 0;
}

.raise-input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #d1d5db;
  border-radius: 5px;
  font-size: 1.125rem;
  margin: 1rem 0;
}

.modal-actions {
  display: flex;
  gap: 1rem;
}

.confirm-btn {
  background: #10b981;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 5px;
  font-weight: bold;
  cursor: pointer;
  flex: 1;
}

.cancel-btn {
  background: #6b7280;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 5px;
  font-weight: bold;
  cursor: pointer;
  flex: 1;
}
</style>