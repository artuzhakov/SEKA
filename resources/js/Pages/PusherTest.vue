<!-- resources/js/Pages/PusherTest.vue -->
<template>
  <div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
          <h1 class="text-2xl font-bold mb-6">🧪 Pusher Test Page</h1>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Test Form -->
            <div class="space-y-4">
              <h2 class="text-lg font-semibold">Отправить тестовое событие</h2>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Game ID</label>
                <input v-model="testGameId" type="number" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Сообщение</label>
                <input v-model="testMessage" type="text" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Тестовое сообщение">
              </div>
              
              <button 
                @click="sendTestEvent"
                :disabled="isSending"
                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:bg-gray-400"
              >
                Отправить событие
              </button>
            </div>

            <!-- Results -->
            <div>
              <h2 class="text-lg font-semibold mb-4">Результаты</h2>
              <div v-if="lastResult" class="bg-gray-50 p-4 rounded-md">
                <pre class="text-sm">{{ JSON.stringify(lastResult, null, 2) }}</pre>
              </div>
              <div v-else class="text-gray-500 text-center py-8">
                Отправьте тестовое событие...
              </div>
            </div>
          </div>

          <!-- Connection Status -->
          <div class="mt-8 p-4 border rounded-md" :class="connectionStatusClass">
            <h3 class="font-semibold mb-2">Статус подключения</h3>
            <p>{{ connectionStatus }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const testGameId = ref(1)
const testMessage = ref('Тестовое сообщение от фронтенда')
const isSending = ref(false)
const lastResult = ref(null)
const connectionStatus = ref('Проверка...')
const connectionStatusClass = ref('border-yellow-200 bg-yellow-50')

const sendTestEvent = async () => {
  try {
    isSending.value = true
    const response = await axios.post('/api/test/pusher/event', {
      game_id: testGameId.value,
      message: testMessage.value
    })
    
    lastResult.value = response.data
    connectionStatus.value = 'Событие отправлено успешно!'
    connectionStatusClass.value = 'border-green-200 bg-green-50'
  } catch (error) {
    lastResult.value = { error: error.message }
    connectionStatus.value = 'Ошибка отправки события'
    connectionStatusClass.value = 'border-red-200 bg-red-50'
  } finally {
    isSending.value = false
  }
}
</script>