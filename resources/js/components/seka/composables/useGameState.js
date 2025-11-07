// resources/js/components/seka/composables/useGameState.js

import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

export function useGameState(gameId) {
    const gameState = ref(null)
    const isLoading = ref(false)
    const error = ref(null)

    // REAL-TIME: Слушатели WebSocket
    const eventListeners = ref([])

    // 🔄 РЕАЛЬНАЯ ЗАГРУЗКА СОСТОЯНИЯ ИГРЫ
    const loadGameState = async () => {
        isLoading.value = true
        try {
            const response = await axios.get(`/api/seka/${gameId}/state`)
            gameState.value = response.data
            error.value = null
            console.log('✅ Game state loaded:', gameState.value)
        } catch (err) {
            // Если новый маршрут не работает, пробуем старый
            try {
                const response = await axios.get(`/api/seka/${gameId}/full-state`)
                gameState.value = response.data.game || response.data
                error.value = null
                console.log('✅ Game state loaded (fallback):', gameState.value)
            } catch (fallbackErr) {
                error.value = 'Ошибка загрузки игры'
                console.error('❌ Error loading game state:', fallbackErr)
            }
        } finally {
            isLoading.value = false
        }
    }

    // 🔄 REAL-TIME ОБНОВЛЕНИЯ ЧЕРЕЗ PUSHER
    const setupRealTimeUpdates = () => {
        if (!window.Echo) {
            console.warn('⚠️ Echo not available')
            return
        }

        console.log('🔌 Setting up WebSocket listeners for game:', gameId)

        // Очищаем предыдущие слушатели
        cleanupRealTimeUpdates()

        const channel = window.Echo.private(`game.${gameId}`)

        // 🎯 Слушаем все игровые события
        const listeners = [
            channel.listen('.player.action.taken', (e) => {
                console.log('🎯 Player action received:', e)
                loadGameState()
            }),
            channel.listen('.CardsDistributed', (e) => {
                console.log('🎴 Cards distributed:', e)
                loadGameState()
            }),
            channel.listen('.TurnChanged', (e) => {
                console.log('🔄 Turn changed:', e)
                loadGameState()
            }),
            channel.listen('.PlayerJoined', (e) => {
                console.log('👤 Player joined:', e)
                loadGameState()
            }),
            channel.listen('.player.ready', (e) => {
                console.log('✅ Player ready:', e)
                loadGameState()
            }),
            channel.listen('.GameFinished', (e) => {
                console.log('🏁 Game finished:', e)
                loadGameState()
            }),
            channel.listen('.GameStarted', (e) => {
                console.log('🎮 Game started:', e)
                loadGameState()
            }),
            channel.listen('.bidding.round.started', (e) => {
                console.log('🎯 Bidding round started:', e)
                loadGameState()
            })
        ]

        eventListeners.value = listeners
    }

    // 🔄 ОЧИСТКА WebSocket СЛУШАТЕЛЕЙ
    const cleanupRealTimeUpdates = () => {
        if (window.Echo) {
            window.Echo.leave(`game.${gameId}`)
        }
        eventListeners.value = []
    }

    // 🔄 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
    const currentPlayer = computed(() => {
        if (!gameState.value || !gameState.value.players) return null
        const user = usePage().props.auth.user
        return gameState.value.players.find(p => p.user_id === user.id)
    })

    const isCurrentPlayerTurn = computed(() => {
        return currentPlayer.value && 
               gameState.value && 
               gameState.value.current_player_id === currentPlayer.value.user_id
    })

    const activePlayers = computed(() => {
        return gameState.value?.players?.filter(p => 
            p.status === 'active' || p.status === 'dark' || p.status === 'ready'
        ) || []
    })

    // 🔄 ИНИЦИАЛИЗАЦИЯ И ОЧИСТКА
    onMounted(() => {
        loadGameState().then(() => {
            setupRealTimeUpdates()
        })
    })

    onUnmounted(() => {
        cleanupRealTimeUpdates()
    })

    return {
        // Состояние
        gameState,
        isLoading,
        error,
        
        // Вычисляемые свойства
        currentPlayer,
        isCurrentPlayerTurn,
        activePlayers,
        
        // Методы
        loadGameState,
        setupRealTimeUpdates,
        cleanupRealTimeUpdates
    }
}