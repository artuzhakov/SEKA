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
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: linear-gradient(135deg, #1a5a1a 0%, #0a2f0a 100%);
  padding: 2rem;
  border-radius: 15px;
  border: 2px solid #38a169;
  color: white;
  min-width: 400px;
  max-width: 90vw;
}

.raise-info {
  background: rgba(255, 255, 255, 0.1);
  padding: 1rem;
  border-radius: 10px;
  margin: 1rem 0;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.raise-info p {
  margin: 0.5rem 0;
  font-size: 1rem;
}

/* Стили для ползунка */
.slider-container {
  margin: 1.5rem 0;
}

.slider {
  width: 100%;
  height: 8px;
  border-radius: 4px;
  background: #2d3748;
  outline: none;
  margin: 1rem 0;
}

.slider-labels {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.5rem;
  font-size: 0.9rem;
}

.current-bet {
  font-size: 1.2rem;
  font-weight: bold;
  color: #68d391;
}

/* Стили для цифрового ввода */
.number-input-container {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 1rem 0;
  background: rgba(255, 255, 255, 0.1);
  padding: 1rem;
  border-radius: 10px;
}

.number-input-container label {
  font-weight: bold;
}

.number-input {
  flex: 1;
  padding: 0.75rem;
  border: 2px solid #38a169;
  border-radius: 8px;
  background: #1a202c;
  color: white;
  font-size: 1.1rem;
  text-align: center;
}

.currency {
  font-size: 1.2rem;
  font-weight: bold;
}

/* Кнопки модального окна */
.modal-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.confirm-btn {
  background: linear-gradient(135deg, #38a169, #2f855a);
  color: white;
  padding: 1rem 1.5rem;
  border: none;
  border-radius: 10px;
  font-weight: bold;
  cursor: pointer;
  flex: 2;
  font-size: 1.1rem;
  transition: all 0.3s;
}

.confirm-btn:hover {
  background: linear-gradient(135deg, #48bb78, #38a169);
  transform: translateY(-2px);
}

.cancel-btn {
  background: linear-gradient(135deg, #e53e3e, #c53030);
  color: white;
  padding: 1rem 1.5rem;
  border: none;
  border-radius: 10px;
  font-weight: bold;
  cursor: pointer;
  flex: 1;
  transition: all 0.3s;
}

.cancel-btn:hover {
  background: linear-gradient(135deg, #f56565, #e53e3e);
  transform: translateY(-2px);
}

/* Адаптивность */
@media (max-width: 768px) {
  .modal-content {
    min-width: 90vw;
    padding: 1.5rem;
  }
  
  .modal-actions {
    flex-direction: column;
  }
  
  .number-input-container {
    flex-direction: column;
    align-items: stretch;
  }
}

.raise-input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #d1d5db;
  border-radius: 5px;
  font-size: 1.125rem;
  margin: 1rem 0;
}

</style>