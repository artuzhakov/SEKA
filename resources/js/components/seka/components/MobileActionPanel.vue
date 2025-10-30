<template>
  <div class="mobile-action-panel" :class="{ 'visible': isVisible }">
    <!-- Заголовок -->
    <div class="panel-header">
      <h3 class="panel-title">🎯 Ваш ход</h3>
      <button class="close-btn" @click="closePanel">✕</button>
    </div>

    <!-- Действия -->
    <div class="actions-grid">
      <ActionButton
        v-for="action in availableActions"
        :key="action"
        :action="action"
        :amount="getActionAmount(action)"
        :disabled="!isActionEnabled(action)"
        @action-clicked="handleAction"
      />
    </div>

    <!-- Информация -->
    <div class="mobile-info">
      <div class="info-item">
        <span>Ваша ставка:</span>
        <strong>{{ player.currentBet }}🪙</strong>
      </div>
      <div class="info-item">
        <span>Баланс:</span>
        <strong>{{ player.balance }}🪙</strong>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import ActionButton from './ActionButton.vue'

const props = defineProps({
  player: {
    type: Object,
    required: true
  },
  isVisible: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['action', 'close'])

// 🎯 Временные данные для тестирования
const availableActions = computed(() => [
  'check', 'call', 'raise', 'fold', 'dark', 'reveal', 'open'
])

// 🎯 МЕТОДЫ
const getActionAmount = (action) => {
  const amounts = {
    call: 50,
    dark: 25,
    reveal: 100
  }
  return amounts[action] || null
}

const isActionEnabled = (action) => {
  // Временная логика - все действия доступны
  return true
}

const handleAction = (action) => {
  emit('action', action)
  closePanel()
}

const closePanel = () => {
  emit('close')
}
</script>

<style scoped>
.mobile-action-panel {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(26, 32, 44, 0.95);
  backdrop-filter: blur(20px);
  border-top: 2px solid #4a5568;
  border-radius: 20px 20px 0 0;
  padding: 20px;
  transform: translateY(100%);
  transition: transform 0.3s ease;
  z-index: 1000;
  max-height: 70vh;
  overflow-y: auto;
}

.mobile-action-panel.visible {
  transform: translateY(0);
}

/* Заголовок */
.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #4a5568;
}

.panel-title {
  margin: 0;
  font-size: 1.3rem;
  color: #68d391;
  font-weight: bold;
}

.close-btn {
  background: none;
  border: none;
  color: #a0aec0;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 5px;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

/* Сетка действий */
.actions-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  margin-bottom: 20px;
}

/* Информация */
.mobile-info {
  background: rgba(74, 85, 104, 0.3);
  border-radius: 10px;
  padding: 15px;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.info-item:last-child {
  margin-bottom: 0;
}

.info-item span {
  color: #a0aec0;
  font-size: 0.9rem;
}

.info-item strong {
  color: #e2e8f0;
  font-size: 1rem;
}

/* Адаптивность */
@media (max-width: 480px) {
  .mobile-action-panel {
    padding: 15px;
  }
  
  .actions-grid {
    grid-template-columns: 1fr;
  }
  
  .panel-title {
    font-size: 1.1rem;
  }
}

/* Анимация появления */
@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

.mobile-action-panel.visible {
  animation: slideUp 0.3s ease;
}
</style>