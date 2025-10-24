import { ref, computed } from 'vue'
import axios from 'axios'

export function useGameState(gameId) {

    const { user, checkAuth } = useAuth()
    
    // State
    const gameStatus = ref('waiting')
    const players = ref([])
    const currentPlayerPosition = ref(1)
    const currentPlayerId = ref(null) // Будет устанавливаться из user data
    const bank = ref(0)
    const currentRound = ref(1)
    const playerCards = ref({})
    const showAllCards = ref(false)
    const authError = ref(null)

    // Устанавливаем ID текущего игрока из аутентификации
    if (user.value) {
        currentPlayerId.value = user.value.id
    }

    // Methods
    const initializeGame = async () => {
        try {
            // Проверяем аутентификацию
            const isAuthenticated = await checkAuth()
            if (!isAuthenticated) {
                authError.value = 'User not authenticated'
                throw new Error('Authentication required')
            }

            let response
            try {
                response = await axios.get(`/api/seka/${gameId}/game-info`)
            } catch (error) {
                if (error.response?.status === 401) {
                    authError.value = 'Session expired'
                    throw new Error('Authentication expired')
                }
                response = await axios.get(`/api/seka/${gameId}/status`)
            }
            
            updateGameState(response.data)
            await fetchPlayerCards()
            authError.value = null
            
        } catch (error) {
            console.error('❌ INITIALIZATION FAILED:', error)
            if (error.response?.status === 401) {
                authError.value = 'Authentication required'
            }
            createMockPlayers()
        }
    }

    const updateGameState = (data) => {
        if (data.game && data.game.players) {
        players.value = data.game.players
        gameStatus.value = data.game.status || 'waiting'
        currentPlayerPosition.value = data.game.current_player_position || 1
        bank.value = data.game.bank || 0
        currentRound.value = data.game.round || 1
        } else if (data.players) {
        players.value = data.players
        gameStatus.value = data.status || 'waiting'
        currentPlayerPosition.value = data.current_player_position || 1
        bank.value = data.bank || 0
        currentRound.value = data.round || 1
        }
    }

    const fetchPlayerCards = async () => {
        try {
        const response = await axios.get(`/api/seka/${gameId}/cards`)
        if (response.data.success) {
            playerCards.value = response.data.player_cards
        }
        } catch (error) {
        playerCards.value = {
            1: ['A♥', 'K♠', '10♦'],
            2: ['Q♣', '9♥', '7♠'], 
            3: ['J♦', '8♣', '6♥']
        }
        }
    }

    const switchPlayer = (playerId) => {
        currentPlayerId.value = playerId
    }

    const createMockPlayers = () => {
        players.value = [
        { id: 1, position: 1, status: 'active', balance: 1000, current_bet: 0, is_ready: true },
        { id: 2, position: 2, status: 'active', balance: 1000, current_bet: 0, is_ready: true },
        { id: 3, position: 3, status: 'active', balance: 1000, current_bet: 0, is_ready: false }
        ]
    }

    const resetGameState = () => {
        players.value = []
        gameStatus.value = 'waiting'
        currentPlayerPosition.value = 1
        bank.value = 0
        currentRound.value = 1
        playerCards.value = {}
    }

  return {
    // State
    gameStatus,
    players,
    currentPlayerPosition,
    currentPlayerId,
    bank,
    currentRound,
    playerCards,
    showAllCards,
    authError,
    
    // Methods
    initializeGame,
    updateGameState,
    switchPlayer,
    resetGameState
  }
}