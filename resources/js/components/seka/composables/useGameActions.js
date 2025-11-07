// resources/js/components/seka/composables/useGameActions.js

import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

export function useGameActions(gameId) {
    const isActionLoading = ref(false)
    const lastError = ref(null)

    // 🔄 РЕАЛЬНЫЙ ВЫЗОВ API ДЛЯ ДЕЙСТВИЙ
    // 🔄 ИСПОЛЬЗУЕМ СУЩЕСТВУЮЩИЙ МАРШРУТ /api/seka/{gameId}/action
    const performAction = async (action, betAmount = null) => {
        isActionLoading.value = true
        lastError.value = null
        
        try {
            console.log(`🎯 Performing action: ${action}`, { gameId, betAmount })

            const response = await axios.post(`/api/seka/${gameId}/action`, {
                action: action,
                bet_amount: betAmount
            })

            console.log(`✅ Action ${action} completed:`, response.data)

            if (response.data.success) {
                return response.data
            } else {
                throw new Error(response.data.error || 'Unknown error from server')
            }

        } catch (error) {
            console.error('❌ Action failed:', error)
            const errorMessage = error.response?.data?.message || 
                               error.response?.data?.error || 
                               error.message || 
                               'Unknown error occurred'
            
            lastError.value = errorMessage
            throw new Error(errorMessage)
        } finally {
            isActionLoading.value = false
        }
    }

    // 🔄 СПЕЦИФИЧЕСКИЕ МЕТОДЫ ДЛЯ КАЖДОГО ДЕЙСТВИЯ SEKA
    const check = () => performAction('check')
    
    const call = () => performAction('call')
    
    const raise = (amount) => {
        if (!amount || amount <= 0) {
            throw new Error('Amount is required for raise')
        }
        return performAction('raise', amount)
    }
    
    const fold = () => performAction('fold')
    
    const playDark = () => performAction('dark')
    
    const playOpen = () => performAction('open')
    
    const reveal = () => performAction('reveal')

    // 🔄 ДЕЙСТВИЯ ДЛЯ ЛОББИ
    // 🔄 ИСПОЛЬЗУЕМ СУЩЕСТВУЮЩИЙ МАРШРУТ /api/seka/{gameId}/ready
    const markPlayerReady = async () => {
        try {
            const response = await axios.post(`/api/seka/${gameId}/ready`)
            return response.data
        } catch (error) {
            console.error('Ready action failed:', error)
            lastError.value = error.response?.data?.error || error.message
            throw error
        }
    }

    const joinGame = async () => {
        try {
            const response = await axios.post(`/api/seka/${gameId}/join`)
            return response.data
        } catch (error) {
            console.error('Join game failed:', error)
            lastError.value = error.response?.data?.error || error.message
            throw error
        }
    }

    // 🔄 ВАЛИДАЦИЯ ДЕЙСТВИЙ (КЛИЕНТСКАЯ)
    const validateAction = (action, gameState, betAmount = null) => {
        if (!gameState || !gameState.current_player_id) {
            return { isValid: false, error: 'Game state not available' }
        }

        const user = usePage().props.auth.user
        const currentPlayer = gameState.players?.find(p => p.user_id === user.id)
        
        if (!currentPlayer) {
            return { isValid: false, error: 'Player not found in game' }
        }

        if (gameState.current_player_id !== user.id) {
            return { isValid: false, error: 'Not your turn' }
        }

        // Проверки для конкретных действий
        switch (action) {
            case 'raise':
                if (!betAmount || betAmount <= 0) {
                    return { isValid: false, error: 'Bet amount required for raise' }
                }
                if (betAmount > currentPlayer.balance) {
                    return { isValid: false, error: 'Insufficient balance' }
                }
                break;

            case 'call':
                const callAmount = gameState.current_max_bet - (currentPlayer.current_bet || 0)
                if (callAmount > currentPlayer.balance) {
                    return { isValid: false, error: 'Insufficient balance for call' }
                }
                break;

            case 'reveal':
                const revealAmount = gameState.current_max_bet * 2
                if (revealAmount > currentPlayer.balance) {
                    return { isValid: false, error: 'Insufficient balance for reveal' }
                }
                break;
        }

        return { isValid: true }
    }

    // 🔄 СБРОС ОШИБКИ
    const clearError = () => {
        lastError.value = null
    }

    return {
        // Состояние
        isActionLoading,
        lastError,
        
        // Действия игры
        check,
        call,
        raise,
        fold,
        playDark,
        playOpen,
        reveal,
        
        // Действия лобби
        markPlayerReady,
        joinGame,
        
        // Вспомогательные методы
        validateAction,
        clearError
    }
}