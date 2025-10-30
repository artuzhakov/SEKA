import { ref } from 'vue'
import axios from 'axios'

export function useGameActions(gameId) {
    const isActionLoading = ref(false)
    const lastError = ref(null)

    // 🔄 ВАЛИДАЦИЯ ДЕЙСТВИЙ
    const validateAction = (action, currentPlayerInfo, currentMaxBet, gameRound) => {
        if (!currentPlayerInfo) {
            return { isValid: false, error: 'Информация об игроке не найдена' }
        }

        // Проверки по раундам
        const roundValidations = {
            1: ['check', 'dark', 'fold', 'call', 'raise'],
            2: ['reveal', 'fold', 'call', 'raise', 'open'],
            3: ['fold', 'call', 'raise', 'open']
        }

        if (!roundValidations[gameRound]?.includes(action)) {
            return { isValid: false, error: `Действие ${action} недоступно в раунде ${gameRound}` }
        }

        // Специфические проверки
        const playerBet = currentPlayerInfo.currentBet || 0
        const needsCall = currentMaxBet > playerBet

        if (action === 'check' && needsCall) {
            return { isValid: false, error: 'Нельзя проверить при активной ставке' }
        }

        if (action === 'call' && !needsCall) {
            return { isValid: false, error: 'Нет активной ставки для поддержания' }
        }

        if (action === 'dark' && currentPlayerInfo.isDark) {
            return { isValid: false, error: 'Вы уже играете в темную' }
        }

        if (action === 'reveal' && gameRound === 1) {
            return { isValid: false, error: 'Вскрытие доступно только в раундах 2 и 3' }
        }

        if (action === 'open' && !currentPlayerInfo.isDark) {
            return { isValid: false, error: 'Открытие доступно только после темной игры' }
        }

        return { isValid: true }
    }

    // 🔄 ВЫПОЛНЕНИЕ ДЕЙСТВИЯ С ВАЛИДАЦИЕЙ
    const performAction = async (action, betAmount = null, gameState = null) => {
        isActionLoading.value = true
        lastError.value = null
        
        try {
            const user = usePage().props.auth.user
            
            // Валидация на клиенте
            if (gameState) {
                const validation = validateAction(
                    action, 
                    gameState.currentPlayerInfo, 
                    gameState.currentMaxBet,
                    gameState.currentRound
                )
                
                if (!validation.isValid) {
                    throw new Error(validation.error)
                }
            }

            // Подготовка данных для API
            const requestData = {
                player_id: user.id,
                action: action
            }

            // Добавляем сумму ставки если нужно
            if (action === 'raise' && betAmount !== null) {
                requestData.bet_amount = betAmount
            } else if (action === 'call') {
                // Для call вычисляем сумму автоматически
                const callAmount = gameState ? (gameState.currentMaxBet - (gameState.currentPlayerInfo?.currentBet || 0)) : 0
                requestData.bet_amount = callAmount
            } else if (action === 'dark') {
                // Для dark - 50% от текущей ставки
                const darkAmount = gameState ? Math.floor(gameState.currentMaxBet * 0.5) : 0
                requestData.bet_amount = darkAmount
            } else if (action === 'reveal') {
                // Для reveal - удвоение ставки
                const revealAmount = gameState ? (gameState.currentMaxBet * 2) : 0
                requestData.bet_amount = revealAmount
            }

            console.log(`🎯 Performing action: ${action}`, requestData)

            // 🔄 РЕАЛЬНЫЙ ЗАПРОС К API
            const response = await axios.post(`/api/seka/${gameId}/action`, requestData)

            if (response.data.success) {
                console.log(`✅ Action ${action} completed successfully`)
                return response.data
            } else {
                throw new Error(response.data.error || 'Unknown error from server')
            }

        } catch (error) {
            console.error('❌ Action failed:', error)
            lastError.value = error.response?.data?.error || error.message || 'Unknown error'
            throw error
        } finally {
            isActionLoading.value = false
        }
    }

    // 🔄 ОТМЕТИТЬСЯ КАК ГОТОВЫЙ
    const markPlayerReady = async () => {
        try {
            const user = usePage().props.auth.user
            const response = await axios.post(`/api/seka/${gameId}/ready`, {
                game_id: gameId,
                player_id: user.id
            })
            return response.data
        } catch (error) {
            console.error('Ready action failed:', error)
            lastError.value = error.response?.data?.error || error.message
            throw error
        }
    }

    // 🔄 ПОЛУЧИТЬ ИСТОРИЮ ДЕЙСТВИЙ
    const getGameHistory = async () => {
        try {
            const response = await axios.get(`/api/seka/${gameId}/history`)
            return response.data
        } catch (error) {
            console.error('Failed to get game history:', error)
            throw error
        }
    }

    // 🔄 СБРОСИТЬ ОШИБКУ
    const clearError = () => {
        lastError.value = null
    }

    return {
        isActionLoading,
        lastError,
        performAction,
        markPlayerReady,
        getGameHistory,
        clearError,
        validateAction
    }
}