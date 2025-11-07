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

    // 🔄 РЕАЛЬНАЯ ЗАГРУЗКА СОСТОЯНИЯ ИГРЫ (ОБНОВЛЕННАЯ)
    const loadGameState = async () => {
        isLoading.value = true
        try {
            const response = await axios.get(`/api/seka/${gameId}/state`)
            if (response.data.success) {
                gameState.value = response.data.game
                error.value = null
                console.log('✅ Game state loaded:', gameState.value)
            } else {
                throw new Error(response.data.message || 'Failed to load game state')
            }
        } catch (err) {
            console.error('❌ Error loading game state:', err)
            error.value = err.response?.data?.message || err.message || 'Ошибка загрузки игры'
            
            // 🔄 Пробуем запасной endpoint
            try {
                const fallbackResponse = await axios.get(`/api/seka/${gameId}/full-state`)
                if (fallbackResponse.data.success) {
                    gameState.value = fallbackResponse.data.game
                    error.value = null
                    console.log('✅ Game state loaded (fallback):', gameState.value)
                }
            } catch (fallbackErr) {
                console.error('❌ Fallback also failed:', fallbackErr)
            }
        } finally {
            isLoading.value = false
        }
    }

    // 🔄 ПРИСОЕДИНИТЬСЯ К ИГРЕ (НОВЫЙ МЕТОД)
    const joinGame = async (userId, playerName = null) => {
        isLoading.value = true
        try {
            const response = await axios.post(`/api/seka/${gameId}/join`, {
                user_id: userId,
                player_name: playerName
            })
            
            if (response.data.success) {
                gameState.value = response.data.game
                error.value = null
                console.log('✅ Joined game successfully:', response.data)
                return response.data
            } else {
                throw new Error(response.data.message || 'Failed to join game')
            }
        } catch (err) {
            console.error('❌ Join game failed:', err)
            error.value = err.response?.data?.message || err.message || 'Ошибка присоединения к игре'
            throw err
        } finally {
            isLoading.value = false
        }
    }

    // 🔄 ПОКИНУТЬ ИГРУ (НОВЫЙ МЕТОД)
    const leaveGame = async (userId) => {
        try {
            const response = await axios.post(`/api/seka/${gameId}/leave`, {
                user_id: userId
            })
            
            if (response.data.success) {
                console.log('✅ Left game successfully')
                return response.data
            } else {
                throw new Error(response.data.message || 'Failed to leave game')
            }
        } catch (err) {
            console.error('❌ Leave game failed:', err)
            error.value = err.response?.data?.message || err.message || 'Ошибка выхода из игры'
            throw err
        }
    }

    // 🔄 REAL-TIME ОБНОВЛЕНИЯ ЧЕРЕЗ PUSHER (ОБНОВЛЕННЫЙ)
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
            // Основные игровые события
            channel.listen('.PlayerActionTaken', (e) => {
                console.log('🎯 Player action received:', e)
                loadGameState() // Перезагружаем полное состояние
            }),
            
            channel.listen('.CardsDistributed', (e) => {
                console.log('🎴 Cards distributed:', e)
                loadGameState()
            }),
            
            channel.listen('.PlayerJoined', (e) => {
                console.log('👤 Player joined:', e)
                // Обновляем список игроков в реальном времени
                if (gameState.value) {
                    gameState.value.players_list = e.players_list
                    gameState.value.players_count = e.current_players_count
                }
            }),
            
            channel.listen('.PlayerReady', (e) => {
                console.log('✅ Player ready:', e)
                // Обновляем статус готовности
                if (gameState.value && gameState.value.players_list) {
                    const playerIndex = gameState.value.players_list.findIndex(p => p.id === e.playerId)
                    if (playerIndex !== -1) {
                        gameState.value.players_list[playerIndex].is_ready = true
                    }
                }
            }),
            
            channel.listen('.GameStarted', (e) => {
                console.log('🎮 Game started:', e)
                loadGameState()
            }),
            
            channel.listen('.GameFinished', (e) => {
                console.log('🏁 Game finished:', e)
                loadGameState()
            }),
            
            channel.listen('.BiddingRoundStarted', (e) => {
                console.log('🎯 Bidding round started:', e)
                loadGameState()
            }),
            
            // События для свары
            channel.listen('.QuarrelInitiated', (e) => {
                console.log('⚡ Quarrel initiated:', e)
                loadGameState()
            }),
            
            channel.listen('.QuarrelStarted', (e) => {
                console.log('🔥 Quarrel started:', e)
                loadGameState()
            }),
            
            channel.listen('.QuarrelResolved', (e) => {
                console.log('✅ Quarrel resolved:', e)
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

    // 🔄 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА (ОБНОВЛЕННЫЕ)
    const currentPlayer = computed(() => {
        if (!gameState.value || !gameState.value.players_list) return null
        const user = usePage().props.auth.user
        return gameState.value.players_list.find(p => p.id === user.id)
    })

    const isCurrentPlayerTurn = computed(() => {
        return currentPlayer.value && 
               gameState.value && 
               gameState.value.current_player_id === currentPlayer.value.id
    })

    const activePlayers = computed(() => {
        return gameState.value?.players_list?.filter(p => 
            p.status === 'active' || p.status === 'dark' || p.status === 'ready'
        ) || []
    })

    const readyPlayersCount = computed(() => {
        return gameState.value?.players_list?.filter(p => p.is_ready).length || 0
    })

    const gameStatus = computed(() => {
        return gameState.value?.status || 'waiting'
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
        readyPlayersCount,
        gameStatus,
        
        // Методы
        loadGameState,
        joinGame,
        leaveGame,
        setupRealTimeUpdates,
        cleanupRealTimeUpdates
    }
}