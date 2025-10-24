import { ref } from 'vue'
import axios from 'axios'

export function useGameActions(gameId, { currentPlayerId, players, updateGameState }) {
  const showRaiseModal = ref(false)
  const raiseAmount = ref(10)

  const takeAction = async (action, betAmount = null) => {
    try {
      const requestData = {
        player_id: currentPlayerId.value,
        action: action
      }
      
      if (action === 'raise' && betAmount !== null) {
        requestData.bet_amount = betAmount
      }

      const response = await axios.post(`/api/seka/${gameId}/action`, requestData)
      
      if (response.data.success) {
        if (response.data.current_player_position) {
          // Handle turn switching in parent component if needed
        }
        
        setTimeout(() => {
          updateGameState()
        }, 500)
      } else {
        throw new Error(response.data.message || 'Unknown error')
      }
    } catch (error) {
      throw new Error(error.response?.data?.message || error.message)
    }
  }

  const executeRaise = (amount) => {
    takeAction('raise', amount)
    showRaiseModal.value = false
    raiseAmount.value = 10
  }

  const cancelRaise = () => {
    showRaiseModal.value = false
    raiseAmount.value = 10
  }

  // Quick actions for testing
  const quickStart = async () => {
    await axios.post('/api/seka/start', {
      room_id: 1,
      players: [1, 2, 3]
    })
  }

  const markAllReady = async () => {
    for (let playerId of [1, 2, 3]) {
      await axios.post(`/api/seka/${gameId}/ready`, {
        game_id: gameId,
        player_id: playerId
      })
      await new Promise(resolve => setTimeout(resolve, 300))
    }
  }

  const distributeCards = async () => {
    await axios.post(`/api/seka/${gameId}/distribute`)
  }

  return {
    takeAction,
    executeRaise,
    showRaiseModal,
    raiseAmount,
    cancelRaise,
    quickStart,
    markAllReady,
    distributeCards
  }
}