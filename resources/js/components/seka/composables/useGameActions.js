// useGameActions.js - ДОБАВЛЯЕМ МЕТОД LEAVE
import { ref } from 'vue'

export function useGameActions(gameId) {
  const isActionLoading = ref(false)
  const lastError = ref(null)
  const lastSuccess = ref(null)

  const clearError = () => { lastError.value = null }
  const clearSuccess = () => { lastSuccess.value = null }

  const performAction = async (action, amount = null) => {
    isActionLoading.value = true
    lastError.value = null
    
    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
      
      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken
      }
      
      const body = { action }
      if (amount !== null) {
        body.amount = amount
      }
      
      const response = await fetch(`/api/seka/games/${gameId}/leave`, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify(body)
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Ошибка выполнения действия')
      }
      
      const data = await response.json()
      lastSuccess.value = data.message || 'Действие выполнено'
      return data
    } catch (error) {
      lastError.value = error.message
      throw error
    } finally {
      isActionLoading.value = false
    }
  }

  // 🎯 НОВЫЙ МЕТОД ДЛЯ ВЫХОДА ИЗ ИГРЫ
  const leaveGame = async () => {
    isActionLoading.value = true
    lastError.value = null
    
    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
      
      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken
      }
      
      const response = await fetch(`/api/seka/games/${gameId}/leave`, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify({
          user_id: 1 // 🎯 ЗАГЛУШКА - нужно получить из auth
        })
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Ошибка выхода из игры')
      }
      
      const data = await response.json()
      lastSuccess.value = 'Вы вышли из игры'
      return data
    } catch (error) {
      lastError.value = error.message
      throw error
    } finally {
      isActionLoading.value = false
    }
  }

  return {
    performAction,
    leaveGame, // 🎯 ЭКСПОРТИРУЕМ НОВЫЙ МЕТОД
    isActionLoading,
    lastError,
    lastSuccess,
    clearError,
    clearSuccess
  }
}