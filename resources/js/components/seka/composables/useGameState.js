import { ref, computed, watch } from 'vue'
import { useWebSocket } from './useWebSocket'

export function useGameState(gameId) {
  const gameState = ref(null)
  const isLoading = ref(false)
  const error = ref(null)
  
  // WebSocket соединение
  const { socket, connect, disconnect } = useWebSocket()

  // Вычисляемые свойства
  const currentPlayer = computed(() => {
    if (!gameState.value?.players) return null
    return gameState.value.players.find(p => p.is_current_player) || null
  })

  const isCurrentPlayerTurn = computed(() => {
    return currentPlayer.value !== null
  })

  const activePlayers = computed(() => {
    if (!gameState.value?.players) return []
    return gameState.value.players.filter(p => 
      p.status !== 'folded' && p.status !== 'out'
    )
  })

  const readyPlayersCount = computed(() => {
    if (!gameState.value?.players) return 0
    return gameState.value.players.filter(p => p.is_ready).length
  })

  const gameStatus = computed(() => {
    return gameState.value?.status || 'waiting'
  })

  // Методы
  const loadGameState = async () => {
    isLoading.value = true
    error.value = null
    
    try {
      // 🎯 ИСПРАВЛЕНО: используем правильный endpoint
      const response = await fetch(`/api/seka/games/${gameId}/state`)
      
      if (response.status === 404) {
        throw new Error('Игра не найдена')
      }
      
      if (response.status === 403) {
        throw new Error('Вы не участвуете в этой игре')
      }
      
      if (!response.ok) {
        throw new Error('Ошибка загрузки игры')
      }
      
      const data = await response.json()
      gameState.value = data
      console.log('✅ Game state loaded:', data)
      
    } catch (err) {
      error.value = err.message
      console.error('❌ Failed to load game state:', err)
      
      // 🎯 Если ошибка 403 или 404 - редирект в лобби
      if (err.message.includes('не участвуете') || err.message.includes('не найдена')) {
        setTimeout(() => {
          window.location.href = '/lobby'
        }, 2000)
      }
    } finally {
      isLoading.value = false
    }
  }

  const joinGame = async () => {
    try {
      const response = await fetch(`/api/seka/games/${gameId}/join`, {
        method: 'POST'
      })
      
      if (!response.ok) throw new Error('Failed to join game')
      
      await loadGameState()
    } catch (err) {
      error.value = err.message
      console.error('❌ Failed to join game:', err)
    }
  }

  // WebSocket события
  const setupWebSocketListeners = () => {
    if (!socket.value) return

    socket.value.on('game_state_updated', (newState) => {
      console.log('🔄 Game state updated via WebSocket')
      gameState.value = newState
    })

    socket.value.on('player_action_taken', (data) => {
      console.log('🎯 Player action:', data)
      // Можно обновить конкретные части состояния
    })

    socket.value.on('bidding_round_started', (data) => {
      console.log('📈 Bidding round started:', data)
    })

    socket.value.on('cards_distributed', (data) => {
      console.log('🃏 Cards distributed:', data)
    })
  }

  // Инициализация
  watch(socket, (newSocket) => {
    if (newSocket) {
      setupWebSocketListeners()
    }
  })

  // Загружаем состояние при создании
  loadGameState()

  return {
    gameState,
    isLoading,
    error,
    currentPlayer,
    isCurrentPlayerTurn,
    activePlayers,
    readyPlayersCount,
    gameStatus,
    loadGameState,
    joinGame
  }
}