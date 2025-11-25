// useGameState.js - ВЕРСИЯ С ДИАГНОСТИКОЙ
import { ref, computed } from 'vue'
import { useWebSocket } from './useWebSocket'
import { useGameTimers } from './useGameTimers'

const globalGameState = ref(null)

export function useGameState(gameId) {
  const isLoading = ref(false)
  const error = ref(null)
  const gameState = globalGameState

  console.log('🎯 useGameState initialized with gameId:', gameId)

  // Таймеры
  const {
    syncTimersFromBackend,
    turnTimeLeft,
    readyTimeLeft,
    revealTimeLeft,
    turnProgress,
    readyProgress,
    revealProgress,
    isTurnTimeCritical,
    isReadyTimeCritical,
    isRevealTimeCritical
  } = useGameTimers()

  const { subscribeToGame, isConnected } = useWebSocket()

  // 🎯 ДИАГНОСТИКА: метод обновления
  const applyGameSnapshot = (newState) => {
    console.log('🔄 applyGameSnapshot called with:', newState)
    console.log('📊 Game State Structure:', {
      id: newState?.id,
      status: newState?.status,
      game_phase: newState?.game_phase,
      players_list: newState?.players_list,
      bank: newState?.bank,
      round: newState?.round,
      current_player_id: newState?.current_player_id,
      max_bet: newState?.max_bet
    })
    
    globalGameState.value = newState

    // Синхронизируем таймеры
    const phase = newState.game_phase || newState.status || null
    if (newState.timers) {
      syncTimersFromBackend(newState.timers, phase)
    }
  }

  // WebSocket
  const setupWebSocket = () => {
    if (!gameId) return
    console.log('🔌 Setting up WebSocket for game:', gameId)

    subscribeToGame(gameId, {
      onGameStateUpdated: (data) => {
        console.log('🔄 WS: GameStateUpdated', data)
        updateFromWebSocket(data)
      },
      onPlayerAction: (data) => {
        console.log('🎯 WS: PlayerActionTaken', data)
        loadGameState()
      }
    })
  }

  // 🎯 ДИАГНОСТИКА: загрузка состояния
  const loadGameState = async () => {
    if (!gameId) return

    console.log('🎯 loadGameState called for gameId:', gameId)
    isLoading.value = true
    error.value = null

    try {
      const url = `/api/seka/games/${gameId}/state`
      console.log('🌐 Fetching from:', url)
      
      const response = await fetch(url)
      console.log('📡 Response status:', response.status, response.ok)
      
      if (!response.ok) throw new Error('Ошибка загрузки игры')

      const data = await response.json()
      console.log('🎯 RAW API Response:', data)
      
      // 🎯 АНАЛИЗ СТРУКТУРЫ ОТВЕТА
      console.log('🔍 Response structure analysis:', {
        hasGame: !!data.game,
        hasSuccess: !!data.success,
        gamesArray: Array.isArray(data.games),
        playersList: !!data.players_list,
        directState: !data.game && !data.success
      })
      
      const state = data.game || data
      console.log('🎯 Final state to apply:', state)
      
      applyGameSnapshot(state)
      setupWebSocket()
    } catch (err) {
      console.error('❌ loadGameState error:', err)
      error.value = err.message
    } finally {
      isLoading.value = false
    }
  }

  // 🎯 ДИАГНОСТИКА: вычисляемые свойства
  const currentPlayer = computed(() => {
    const currentPlayerId = gameState.value?.current_player_id
    console.log('🎯 currentPlayer computed - current_player_id:', currentPlayerId)
    console.log('👥 Available players:', gameState.value?.players_list)
    
    if (!currentPlayerId) {
      console.log('⚠️ No current_player_id, returning null')
      return null
    }
    
    const player = gameState.value?.players_list?.find(p => p.id === currentPlayerId) || null
    console.log('🎯 Found current player:', player)
    return player
  })

  const isCurrentPlayerTurn = computed(() => {
    const result = currentPlayer.value?.id === gameState.value?.current_player_id
    console.log('🎯 isCurrentPlayerTurn:', result)
    return result
  })

  const activePlayers = computed(() => {
    const players = gameState.value?.players_list?.filter(p => 
      p.status === 'active' || p.status === 'in_game' || p.status === 'waiting'
    ) || []
    console.log('🎯 activePlayers:', players)
    return players
  })

  const readyPlayersCount = computed(() => {
    const count = gameState.value?.players_list?.filter(p => p.is_ready).length || 0
    console.log('🎯 readyPlayersCount:', count)
    return count
  })

  const gameStatus = computed(() => {
    const status = gameState.value?.game_phase || gameState.value?.status || 'waiting'
    console.log('🎯 gameStatus:', status)
    return status
  })

  // 🎯 ДИАГНОСТИКА: новые свойства
  const pot = computed(() => {
    const bank = gameState.value?.bank || 0
    console.log('💰 pot computed:', bank)
    return bank
  })

  const currentRound = computed(() => {
    const round = gameState.value?.round || 1
    console.log('🎯 currentRound computed:', round)
    return round
  })

  const currentPlayerId = computed(() => {
    const id = gameState.value?.current_player_id
    console.log('🎯 currentPlayerId computed:', id)
    return id
  })

  const dealerId = computed(() => {
    const id = gameState.value?.dealer_id || 1
    console.log('🎯 dealerId computed:', id)
    return id
  })

  const currentMaxBet = computed(() => {
    const bet = gameState.value?.max_bet || 0
    console.log('💰 currentMaxBet computed:', bet)
    return bet
  })

  const updateFromWebSocket = (data) => {
    console.log('🔌 updateFromWebSocket called with:', data)
    if (data.game) {
      applyGameSnapshot(data.game)
    } else if (data.state) {
      applyGameSnapshot(data.state)
    } else {
      applyGameSnapshot(data)
    }
  }

  const joinGame = async () => {
    console.log('🎯 joinGame called')
    try {
      const response = await fetch(`/api/seka/games/${gameId}/join`, {
        method: 'POST'
      })
      if (!response.ok) throw new Error('Ошибка входа в игру')
      await loadGameState()
    } catch (err) {
      error.value = err.message
    }
  }

  // Автозагрузка
  if (gameId) {
    console.log('🚀 useGameState auto-loading for gameId:', gameId)
    loadGameState()
  }

  return {
    // Состояние
    gameState,
    isLoading,
    error,
    
    // Игроки
    currentPlayer,
    isCurrentPlayerTurn,
    activePlayers,
    readyPlayersCount,
    gameStatus,
    
    // 🎯 НОВЫЕ СВОЙСТВА
    pot,
    currentRound,
    currentPlayerId,
    dealerId,
    currentMaxBet,
    
    // Таймеры
    turnTimeLeft,
    readyTimeLeft,
    revealTimeLeft,
    turnProgress,
    readyProgress,
    revealProgress,
    isTurnTimeCritical,
    isReadyTimeCritical,
    isRevealTimeCritical,
    
    // Методы
    loadGameState,
    joinGame,
    isWebSocketConnected: isConnected
  }
}