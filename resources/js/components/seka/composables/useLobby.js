// useLobby.js - ПРОСТОЙ РАБОЧИЙ ВАРИАНТ
import { ref, onMounted, onUnmounted } from 'vue'

export function useLobby() {
  const tables = ref([])
  const isLoading = ref(false)
  const error = ref(null)

  const loadTables = async () => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await fetch('/api/seka/lobby', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      
      if (!response.ok) throw new Error(`Ошибка загрузки: ${response.status}`)
      
      const data = await response.json()
      console.log('🎯 Lobby API response:', data)
      
      if (data.success && Array.isArray(data.games)) {
        tables.value = data.games.map(game => ({
          id: game.id,
          name: game.name,
          table_type: game.table_type,
          players_count: game.players_count,
          base_bet: game.base_bet,
          status: game.status,
          max_players: game.max_players
        }))
      } else {
        tables.value = []
      }
      
      console.log('🎯 Processed tables:', tables.value)
    } catch (err) {
      error.value = err.message
      console.error('❌ Failed to load lobby:', err)
    } finally {
      isLoading.value = false
    }
  }

  // 🎯 ВРЕМЕННО УБИРАЕМ WebSocket - добавим позже
  onMounted(() => {
    loadTables()
  })

  return {
    tables,
    isLoading,
    error,
    loadTables,
    refreshTables: loadTables
  }
}