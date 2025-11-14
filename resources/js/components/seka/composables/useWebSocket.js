import { ref, onUnmounted } from 'vue'
import Echo from 'laravel-echo'

export function useWebSocket() {
  const socket = ref(null)
  const isConnected = ref(false)

  const connect = () => {
    if (socket.value) return

    try {
      socket.value = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: true
      })

      isConnected.value = true
      console.log('🔌 WebSocket connected')
    } catch (error) {
      console.error('❌ WebSocket connection failed:', error)
    }
  }

  const disconnect = () => {
    if (socket.value) {
      socket.value.disconnect()
      socket.value = null
      isConnected.value = false
      console.log('🔌 WebSocket disconnected')
    }
  }

  // Автоматическое подключение при создании
  connect()

  // Автоматическое отключение при уничтожении компонента
  onUnmounted(() => {
    disconnect()
  })

  return {
    socket,
    isConnected,
    connect,
    disconnect
  }
}