<template>
  <div class="action-panel">
    <h3 class="text-lg font-bold mb-4 text-white">🎯 Доступные действия</h3>
    
    <!-- Отображение ошибок -->
    <div v-if="errorMessage" class="error-message mb-4 p-3 bg-red-600 text-white rounded-lg">
      {{ errorMessage }}
    </div>
    
    <!-- Информация о ставках -->
    <div v-if="currentPlayerInfo" class="bet-info mb-4 p-3 bg-gray-700 rounded-lg">
      <div class="text-sm text-gray-300">
        Ваша ставка: <span class="text-green-400 font-bold">{{ currentPlayerInfo.currentBet || 0 }}</span>
      </div>
      <div class="text-sm text-gray-300">
        Макс. ставка: <span class="text-yellow-400 font-bold">{{ currentMaxBet }}</span>
      </div>
      <div v-if="needsCall" class="text-sm text-red-300 mt-1">
        Нужно поддержать: <span class="font-bold">{{ callAmount }}</span>
      </div>
    </div>
    
    <div class="actions-grid">
      <button
        v-for="action in filteredActions"
        :key="action"
        @click="handleAction(action)"
        :disabled="!isActionEnabled(action)"
        :class="getButtonClass(action)"
        class="action-btn py-3 px-4 rounded-lg font-semibold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ getActionLabel(action) }}
        <span v-if="action === 'call' && needsCall" class="text-xs ml-1">({{ callAmount }})</span>
      </button>
    </div>
    
    <!-- Состояние загрузки -->
    <div v-if="isActionLoading" class="mt-4 text-center text-yellow-400">
      ⏳ Выполняется действие...
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

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
  }
})

const emit = defineEmits(['take-action', 'show-raise-modal'])

const errorMessage = ref('')

// Вычисляемые свойства
const needsCall = computed(() => {
  if (!props.currentPlayerInfo) return false
  return props.currentMaxBet > (props.currentPlayerInfo.currentBet || 0)
})

const callAmount = computed(() => {
  if (!needsCall.value) return 0
  return props.currentMaxBet - (props.currentPlayerInfo.currentBet || 0)
})

const filteredActions = computed(() => {
  return props.availableActions.filter(action => {
    // Скрыть check если нужно поддержать ставку
    if (action === 'check' && needsCall.value) return false
    return true
  })
})

// Методы
const isActionEnabled = (action) => {
  if (!props.currentPlayerInfo) return false
  
  // Дополнительные проверки
  if (action === 'call' && !needsCall.value) return false
  if (action === 'check' && needsCall.value) return false
  
  return true
}

const getButtonClass = (action) => {
  const baseClasses = 'border-2 '
  
  const actionColors = {
    check: 'bg-blue-600 hover:bg-blue-700 border-blue-500 text-white',
    call: 'bg-yellow-600 hover:bg-yellow-700 border-yellow-500 text-white',
    raise: 'bg-red-600 hover:bg-red-700 border-red-500 text-white',
    fold: 'bg-gray-600 hover:bg-gray-700 border-gray-500 text-white',
    dark: 'bg-purple-600 hover:bg-purple-700 border-purple-500 text-white',
    reveal: 'bg-orange-600 hover:bg-orange-700 border-orange-500 text-white',
    open: 'bg-green-600 hover:bg-green-700 border-green-500 text-white'
  }
  
  return baseClasses + (actionColors[action] || 'bg-gray-600 text-white')
}

const getActionLabel = (action) => {
  const labels = {
    check: '✅ Проверка',
    call: '📞 Поддержать',
    raise: '📈 Повысить',
    fold: '❌ Сбросить',
    dark: '🌙 Темная',
    reveal: '🔓 Вскрытие',
    open: '👀 Открыть'
  }
  return labels[action] || action
}

const handleAction = async (action) => {
  try {
    errorMessage.value = ''
    console.log('🎯 ActionPanel: Handling action', action)
    
    // Валидация
    if (!isActionEnabled(action)) {
      errorMessage.value = `Действие "${getActionLabel(action)}" сейчас недоступно`
      return
    }
    
    if (action === 'raise') {
      emit('show-raise-modal')
    } else {
      emit('take-action', action)
    }
    
  } catch (error) {
    errorMessage.value = `Ошибка: ${error.message}`
    console.error('Action error:', error)
  }
}
</script>

<style scoped>
.action-panel {
  background: #1a202c;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  color: white;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 10px;
}

.action-btn {
  min-height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.error-message {
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>