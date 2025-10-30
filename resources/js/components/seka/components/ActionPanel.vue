<template>
  <div class="floating-action-panel" :class="{ 'visible': isVisible, 'mobile': isMobile }">
    <!-- Заголовок панели -->
    <div class="panel-header">
      <h3 class="panel-title">🎯 Ваш ход</h3>
      <button class="close-btn" @click="closePanel" v-if="isMobile">✕</button>
    </div>

    <!-- Информация о ставках -->
    <div class="betting-info">
      <div class="info-row">
        <span class="info-label">Ваша ставка:</span>
        <span class="info-value bet-value">{{ currentPlayerInfo?.currentBet || 0 }}🪙</span>
      </div>
      <div class="info-row">
        <span class="info-label">Макс. ставка:</span>
        <span class="info-value max-bet">{{ currentMaxBet }}🪙</span>
      </div>
      <div class="info-row" v-if="needsCall">
        <span class="info-label">Поддержать:</span>
        <span class="info-value call-amount">{{ callAmount }}🪙</span>
      </div>
    </div>

    <!-- Сообщение об ошибке -->
    <div v-if="errorMessage" class="error-message">
      <div class="error-icon">⚠️</div>
      <div class="error-text">{{ errorMessage }}</div>
    </div>

    <!-- Кнопки действий -->
    <div class="actions-container">
      <div class="actions-grid">
        <!-- Основные действия -->
        <ActionButton
          v-for="action in primaryActions"
          :key="action"
          :action="action"
          :amount="getActionAmount(action)"
          :disabled="!isActionEnabled(action)"
          :is-highlight="isActionHighlighted(action)"
          @click="handleAction(action)"
        />

        <!-- Дополнительные действия -->
        <ActionButton
          v-for="action in secondaryActions"
          :key="action"
          :action="action"
          :amount="getActionAmount(action)"
          :disabled="!isActionEnabled(action)"
          :is-highlight="isActionHighlighted(action)"
          @click="handleAction(action)"
          class="secondary-action"
        />
      </div>
    </div>

    <!-- Состояние загрузки -->
    <div v-if="isActionLoading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <div class="loading-text">Выполняется действие...</div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
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
  },
  isActionLoading: {
    type: Boolean,
    default: false
  },
  isMobile: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['take-action', 'show-raise-modal', 'close-panel'])

// 🎯 РЕАКТИВНОЕ СОСТОЯНИЕ
const errorMessage = ref('')
const isVisible = ref(true)

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
const needsCall = computed(() => {
  if (!props.currentPlayerInfo) return false
  return props.currentMaxBet > (props.currentPlayerInfo.currentBet || 0)
})

const callAmount = computed(() => {
  if (!needsCall.value) return 0
  return props.currentMaxBet - (props.currentPlayerInfo.currentBet || 0)
})

// Разделяем действия на основные и дополнительные
const primaryActions = computed(() => {
  return props.availableActions.filter(action => 
    ['check', 'call', 'raise', 'fold'].includes(action)
  )
})

const secondaryActions = computed(() => {
  return props.availableActions.filter(action => 
    ['dark', 'reveal', 'open'].includes(action)
  )
})

// 🎯 МЕТОДЫ
const isActionEnabled = (action) => {
  if (!props.currentPlayerInfo) return false
  
  // Специфические проверки доступности
  switch(action) {
    case 'check':
      return !needsCall.value
    case 'call':
      return needsCall.value
    case 'dark':
      return props.currentPlayerInfo && !props.currentPlayerInfo.isDark
    default:
      return true
  }
}

const isActionHighlighted = (action) => {
  // Подсвечиваем рекомендуемые действия
  if (action === 'call' && needsCall.value) return true
  if (action === 'check' && !needsCall.value) return true
  return false
}

const getActionAmount = (action) => {
  switch(action) {
    case 'call':
      return callAmount.value
    case 'dark':
      return Math.floor((props.currentMaxBet || 50) * 0.5)
    case 'reveal':
      return (props.currentPlayerInfo?.currentBet || 0) * 2
    default:
      return null
  }
}

const handleAction = async (action) => {
  try {
    errorMessage.value = ''
    console.log('🎯 ActionPanel: Handling action', action)

    // Валидация
    if (!isActionEnabled(action)) {
      errorMessage.value = getActionErrorMessage(action)
      return
    }

    if (action === 'raise') {
      emit('show-raise-modal')
    } else {
      emit('take-action', action)
    }

    // Автоматически закрываем на мобильных после действия
    if (props.isMobile && action !== 'raise') {
      setTimeout(() => closePanel(), 300)
    }

  } catch (error) {
    errorMessage.value = `Ошибка: ${error.message}`
    console.error('Action error:', error)
  }
}

const getActionErrorMessage = (action) => {
  const messages = {
    check: 'Нельзя пропустить ход при активной ставке',
    call: 'Нет активной ставки для поддержания',
    dark: 'Вы уже играете в темную',
    reveal: 'Вскрытие доступно только в раундах 2-3'
  }
  return messages[action] || `Действие "${action}" недоступно`
}

const closePanel = () => {
  emit('close-panel')
}

const handleKeyPress = (event) => {
  if (event.key === 'Escape') {
    closePanel()
  }
}

// 🎯 LIFECYCLE
onMounted(() => {
  document.addEventListener('keydown', handleKeyPress)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeyPress)
})
</script>

<style scoped>
.floating-action-panel {
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%) translateY(-10px);
  min-width: 320px;
  background: rgba(26, 32, 44, 0.95);
  backdrop-filter: blur(20px);
  border: 2px solid #4a5568;
  border-radius: 15px;
  padding: 20px;
  box-shadow: 
    0 10px 40px rgba(0, 0, 0, 0.5),
    0 0 0 1px rgba(255, 255, 255, 0.1);
  z-index: 1000;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
}

.floating-action-panel.visible {
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) translateY(-20px);
}

/* Заголовок */
.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #4a5568;
}

.panel-title {
  margin: 0;
  font-size: 1.2rem;
  color: #68d391;
  font-weight: bold;
}

.close-btn {
  background: none;
  border: none;
  color: #a0aec0;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 5px;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

/* Информация о ставках */
.betting-info {
  background: rgba(74, 85, 104, 0.3);
  border-radius: 10px;
  padding: 12px;
  margin-bottom: 15px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 5px;
}

.info-row:last-child {
  margin-bottom: 0;
}

.info-label {
  font-size: 0.9rem;
  color: #a0aec0;
}

.info-value {
  font-size: 0.9rem;
  font-weight: bold;
}

.bet-value {
  color: #68d391;
}

.max-bet {
  color: #f6e05e;
}

.call-amount {
  color: #fc8181;
}

/* Сообщение об ошибке */
.error-message {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(229, 62, 62, 0.2);
  border: 1px solid #fc8181;
  border-radius: 8px;
  padding: 10px;
  margin-bottom: 15px;
}

.error-icon {
  font-size: 1.2rem;
}

.error-text {
  font-size: 0.9rem;
  color: #fc8181;
  flex: 1;
}

/* Контейнер действий */
.actions-container {
  margin-top: 10px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.secondary-action {
  grid-column: 1 / -1;
}

/* Состояние загрузки */
.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(26, 32, 44, 0.9);
  border-radius: 15px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.loading-spinner {
  width: 30px;
  height: 30px;
  border: 3px solid #4a5568;
  border-top: 3px solid #68d391;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.loading-text {
  color: #a0aec0;
  font-size: 0.9rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Мобильная версия */
.floating-action-panel.mobile {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  top: auto;
  transform: translateY(100%);
  min-width: auto;
  border-radius: 20px 20px 0 0;
  margin: 0;
  max-height: 80vh;
  overflow-y: auto;
}

.floating-action-panel.mobile.visible {
  transform: translateY(0);
}

.floating-action-panel.mobile .actions-grid {
  grid-template-columns: 1fr;
}

/* Анимации */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(-20px);
  }
}

.floating-action-panel.visible {
  animation: slideIn 0.3s ease;
}

/* Адаптивность */
@media (max-width: 480px) {
  .floating-action-panel {
    min-width: 280px;
    padding: 15px;
  }
  
  .actions-grid {
    grid-template-columns: 1fr;
  }
  
  .panel-title {
    font-size: 1.1rem;
  }
}
</style>