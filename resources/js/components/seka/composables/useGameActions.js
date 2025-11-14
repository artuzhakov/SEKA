import { ref } from 'vue'

export function useGameActions(gameId) {
  const isActionLoading = ref(false)
  const lastError = ref(null)

  const performAction = async (action, betAmount = null) => {
    isActionLoading.value = true
    lastError.value = null

    try {
      const payload = { action }
      if (betAmount !== null) {
        payload.bet_amount = betAmount
      }

      const response = await fetch(`/api/seka/games/${gameId}/action`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Action failed')
      }

      const result = await response.json()
      console.log('✅ Action performed successfully:', action, betAmount)
      
      return result
    } catch (error) {
      lastError.value = error.message
      console.error('❌ Action failed:', error)
      throw error
    } finally {
      isActionLoading.value = false
    }
  }

  const markPlayerReady = async () => {
    return performAction('ready')
  }

  return {
    performAction,
    markPlayerReady,
    isActionLoading,
    lastError
  }
}