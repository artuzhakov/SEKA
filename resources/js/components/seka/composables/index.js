export { useGameState } from './useGameState'
export { useAuth } from './useAuth'
export { useNotifications } from './useNotifications'

// Временные заглушки для остальных композаблов
export const useGameActions = (gameId, { currentPlayerId, players, updateGameState }) => {
  const showRaiseModal = ref(false)
  const raiseAmount = ref(0)
  
  const takeAction = async (action) => {
    console.log(`Player ${currentPlayerId.value} takes action:`, action)
    
    // Временная логика для тестирования
    if (action === 'fold') {
      const player = players.value.find(p => p.id === currentPlayerId.value)
      if (player) {
        player.status = 'folded'
      }
    } else if (action === 'check') {
      console.log('Player checks')
    } else if (action === 'call') {
      const player = players.value.find(p => p.id === currentPlayerId.value)
      const currentMaxBet = Math.max(...players.value.map(p => p.current_bet || 0))
      if (player && player.balance >= currentMaxBet) {
        player.current_bet = currentMaxBet
        player.balance -= currentMaxBet
      }
    }
    
    updateGameState({ players: players.value })
  }
  
  const executeRaise = async (amount) => {
    console.log(`Player ${currentPlayerId.value} raises to:`, amount)
    showRaiseModal.value = false
    
    const player = players.value.find(p => p.id === currentPlayerId.value)
    if (player && player.balance >= amount) {
      player.current_bet = amount
      player.balance -= amount
    }
    
    updateGameState({ players: players.value })
  }
  
  const cancelRaise = () => {
    showRaiseModal.value = false
  }
  
  return {
    takeAction,
    executeRaise,
    showRaiseModal,
    raiseAmount,
    cancelRaise
  }
}

export const useGameMonitoring = ({ gameStatus, players, bank, currentRound, currentPlayerPosition }) => {
  const startStateMonitoring = () => {
    console.log('Game monitoring started')
    setInterval(() => {
      console.log('Game State:', {
        status: gameStatus.value,
        players: players.value.length,
        bank: bank.value,
        round: currentRound.value,
        currentPosition: currentPlayerPosition.value
      })
    }, 10000)
  }
  
  return {
    startStateMonitoring
  }
}

export const useGameTesting = (gameId, { initializeGame, showNotification }) => {
  const runQuickTest = () => {
    console.log('Running quick test for game:', gameId)
    showNotification('Быстрый тест запущен', 'info')
    
    // Тестовая логика
    setTimeout(() => {
      showNotification('Быстрый тест завершен', 'success')
    }, 1000)
  }
  
  const runComprehensiveTest = () => {
    console.log('Running comprehensive test for game:', gameId)
    showNotification('Полный тест запущен', 'info')
    
    // Тестовая логика
    setTimeout(() => {
      showNotification('Полный тест завершен', 'success')
    }, 3000)
  }
  
  return {
    runQuickTest,
    runComprehensiveTest
  }
}