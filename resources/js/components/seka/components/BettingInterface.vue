<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 p-6 rounded-lg shadow-xl max-w-md w-full border border-green-500">
      <h3 class="text-xl font-bold mb-4 text-white">🎯 Повышение ставки</h3>
      
      <!-- Информация о ставках -->
      <div class="mb-4 p-3 bg-gray-700 rounded-lg">
        <div class="grid grid-cols-2 gap-2 text-sm">
          <div class="text-gray-300">Текущая ставка:</div>
          <div class="text-green-400 font-bold">{{ currentMaxBet }}</div>
          
          <div class="text-gray-300">Минимальное повышение:</div>
          <div class="text-yellow-400 font-bold">{{ minRaise }}</div>
          
          <div class="text-gray-300">Ваш баланс:</div>
          <div class="text-blue-400 font-bold">{{ playerBalance }}</div>
          
          <div class="text-gray-300">Ваша ставка:</div>
          <div class="text-white font-bold">{{ localAmount }}</div>
        </div>
      </div>
      
      <!-- Управление суммой -->
      <div class="flex items-center gap-4 mb-4">
        <button 
          @click="decreaseAmount"
          :disabled="localAmount <= minRaise"
          class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-lg font-bold text-white hover:bg-gray-500 disabled:bg-gray-800 disabled:text-gray-500"
        >-</button>
        
        <input 
          v-model.number="localAmount"
          type="number"
          :min="minRaise"
          :max="playerBalance"
          class="flex-1 border border-gray-600 bg-gray-700 rounded px-3 py-2 text-center text-lg font-semibold text-white"
          @change="validateInput"
        />
        
        <button 
          @click="increaseAmount"
          :disabled="localAmount >= playerBalance"
          class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-lg font-bold text-white hover:bg-gray-500 disabled:bg-gray-800 disabled:text-gray-500"
        >+</button>
      </div>
      
      <!-- Быстрые ставки -->
      <div class="mb-4">
        <p class="text-sm text-gray-400 mb-2">Быстрые ставки:</p>
        <div class="grid grid-cols-4 gap-2">
          <button 
            v-for="quickBet in quickBets"
            :key="quickBet"
            @click="setQuickBet(quickBet)"
            :class="quickBet === localAmount ? 'bg-green-600 text-white' : 'bg-gray-600 text-gray-300 hover:bg-gray-500'"
            class="py-2 rounded text-sm font-medium transition-colors"
          >
            {{ quickBet }}
          </button>
        </div>
      </div>
      
      <!-- Сообщение об ошибке -->
      <div v-if="error" class="text-red-400 text-sm mb-4 p-2 bg-red-900 rounded">
        ⚠️ {{ error }}
      </div>
      
      <!-- Кнопки действий -->
      <div class="flex gap-2">
        <button 
          @click="cancel"
          class="flex-1 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-500 transition-colors font-semibold"
        >
          Отмена
        </button>
        <button 
          @click="confirm"
          :disabled="!isValidAmount"
          class="flex-1 py-3 bg-green-600 text-white rounded-lg hover:bg-green-500 disabled:bg-gray-700 disabled:text-gray-500 transition-colors font-semibold"
        >
          Подтвердить {{ localAmount }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
  currentMaxBet: {
    type: Number,
    required: true,
    default: 0
  },
  playerBalance: {
    type: Number,
    required: true,
    default: 100
  },
  playerCurrentBet: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['confirm', 'cancel'])

const localAmount = ref(props.minRaise)
const error = ref('')

// Вычисляемые свойства
const minRaise = computed(() => {
  return props.currentMaxBet + 1
})

const quickBets = computed(() => {
  const bets = new Set()
  
  // Минимальное повышение
  bets.add(minRaise.value)
  
  // 25%, 50%, 75% от баланса
  const quarter = Math.floor(props.playerBalance * 0.25)
  const half = Math.floor(props.playerBalance * 0.5)
  const threeQuarters = Math.floor(props.playerBalance * 0.75)
  
  if (quarter >= minRaise.value) bets.add(quarter)
  if (half >= minRaise.value) bets.add(half)
  if (threeQuarters >= minRaise.value) bets.add(threeQuarters)
  
  // Все-in
  if (props.playerBalance >= minRaise.value) {
    bets.add(props.playerBalance)
  }
  
  return Array.from(bets).sort((a, b) => a - b).slice(0, 4)
})

const isValidAmount = computed(() => {
  return localAmount.value >= minRaise.value && 
         localAmount.value <= props.playerBalance &&
         !error.value
})

// Методы
const validateInput = () => {
  if (localAmount.value < minRaise.value) {
    error.value = `Минимальная ставка: ${minRaise.value}`
  } else if (localAmount.value > props.playerBalance) {
    error.value = `Максимальная ставка: ${props.playerBalance}`
  } else {
    error.value = ''
  }
}

const decreaseAmount = () => {
  if (localAmount.value > minRaise.value) {
    localAmount.value--
    validateInput()
  }
}

const increaseAmount = () => {
  if (localAmount.value < props.playerBalance) {
    localAmount.value++
    validateInput()
  }
}

const setQuickBet = (amount) => {
  localAmount.value = amount
  validateInput()
}

const confirm = () => {
  if (isValidAmount.value) {
    emit('confirm', localAmount.value)
  }
}

const cancel = () => {
  emit('cancel')
}

// Инициализация
onMounted(() => {
  localAmount.value = minRaise.value
})

// Watchers
watch(localAmount, validateInput)
watch(() => props.currentMaxBet, () => {
  localAmount.value = minRaise.value
  validateInput()
})
</script>

<style scoped>
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  -moz-appearance: textfield;
}
</style>