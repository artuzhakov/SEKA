// resources/js/components/seka/composables/useGameState.js

import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

export function useGameState(gameId) {
    const gameState = ref(null)
    const isLoading = ref(false)
    const error = ref(null)

    // 🔄 ЗАМЕНИТЬ МОК-ДАННЫЕ НА РЕАЛЬНЫЙ API
    const loadGameState = async () => {
        isLoading.value = true
        try {
            const response = await axios.get(`/api/seka/${gameId}/full-state`)
            gameState.value = response.data.game
            error.value = null
        } catch (err) {
            error.value = err.response?.data?.message || 'Ошибка загрузки игры'
            console.error('Error loading game state:', err)
        } finally {
            isLoading.value = false
        }
    }

    // 🔄 РЕАЛЬНОЕ ДЕЙСТВИЕ ИГРОКА
    const makePlayerAction = async (action, betAmount = null) => {
        try {
            const user = usePage().props.auth.user
            const response = await axios.post(`/api/seka/${gameId}/action`, {
                player_id: user.id,
                action: action,
                bet_amount: betAmount
            })
            
            // Обновляем состояние после действия
            await loadGameState()
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Ошибка выполнения действия'
            throw err
        }
    }

    // 🔄 REAL-TIME ОБНОВЛЕНИЯ Через PUSHER
    const setupRealTimeUpdates = () => {
        if (window.Echo) {
            window.Echo.private(`game.${gameId}`)
                .listen('PlayerActionTaken', (e) => {
                    // Обновляем состояние при действиях других игроков
                    loadGameState()
                })
                .listen('GameStarted', (e) => {
                    gameState.value = { ...gameState.value, status: 'active' }
                })
                .listen('CardsDistributed', (e) => {
                    // Обновляем карты
                    loadGameState()
                })
        }
    }

    return {
        gameState,
        isLoading,
        error,
        loadGameState,
        makePlayerAction,
        setupRealTimeUpdates
    }
}