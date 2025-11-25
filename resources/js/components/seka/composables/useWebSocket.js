// useWebSocket.js - ИСПРАВЛЕННАЯ ВЕРСИЯ
import { ref, onUnmounted } from 'vue'
import Echo from 'laravel-echo'

// 🎯 Singleton для Echo
let echoInstance = null

const getCsrfToken = () => {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
}

export function useWebSocket() {
  const socket = ref(null)
  const isConnected = ref(false)
  const currentGameId = ref(null)

  const connect = (gameId) => {
    if (currentGameId.value === gameId && isConnected.value) {
      console.log('🔌 WebSocket already connected to game', gameId)
      return
    }

    try {
      // 🎯 Создаем единственный экземпляр Echo
      if (!echoInstance) {
        echoInstance = new Echo({
          broadcaster: 'pusher',
          key: import.meta.env.VITE_PUSHER_APP_KEY || 'local',
          cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
          forceTLS: false, // 🎯 Для разработки
          wsHost: window.location.hostname,
          wsPort: 6001,
          wssPort: 6001,
          enabledTransports: ['ws', 'wss'],
          auth: {
            headers: {
              'X-CSRF-TOKEN': getCsrfToken(),
            }
          }
        })
      }

      socket.value = echoInstance
      currentGameId.value = gameId
      isConnected.value = true

      console.log('🔌 WebSocket connected to game', gameId)

    } catch (error) {
      console.error('❌ WebSocket connection failed:', error)
      isConnected.value = false
    }
  }

  const disconnect = () => {
    if (socket.value && currentGameId.value) {
      socket.value.leave(`game.${currentGameId.value}`)
      currentGameId.value = null
      console.log('🔌 WebSocket left game channel')
    }
    // 🎯 Не отключаем Echo полностью - он может использоваться другими компонентами
  }

  const subscribeToGame = (gameId, callbacks = {}) => {
    connect(gameId)
    
    if (!socket.value) return

    // 🎯 Отписываемся от предыдущей игры
    if (currentGameId.value && currentGameId.value !== gameId) {
      socket.value.leave(`game.${currentGameId.value}`)
    }

    const channel = socket.value.private(`game.${gameId}`)
    
    // 🎯 Базовые обработчики событий
    if (callbacks.onGameStateUpdated) {
      channel.listen('GameStateUpdated', callbacks.onGameStateUpdated)
    }
    
    if (callbacks.onPlayerAction) {
      channel.listen('PlayerActionTaken', callbacks.onPlayerAction)
    }
    
    if (callbacks.onCardsDistributed) {
      channel.listen('CardsDistributed', callbacks.onCardsDistributed)
    }
    
    if (callbacks.onRoundStarted) {
      channel.listen('RoundStarted', callbacks.onRoundStarted)
    }

    currentGameId.value = gameId
    console.log('🎯 Subscribed to game channel:', gameId)

    return () => {
      channel.stopListening('GameStateUpdated')
      channel.stopListening('PlayerActionTaken')
      console.log('🎯 Unsubscribed from game channel')
    }
  }

  onUnmounted(() => {
    disconnect()
  })

  return {
    socket,
    isConnected,
    connect,
    disconnect,
    subscribeToGame
  }
}