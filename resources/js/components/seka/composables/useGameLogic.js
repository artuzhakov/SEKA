// resources/js/components/seka/composables/useGameLogic.js

import { ref, computed } from 'vue'

export function useGameLogic() {
    const gameState = ref(null)
    
    // 🔄 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ДЛЯ UI
    const availableActions = computed(() => {
        if (!gameState.value || !gameState.value.available_actions) return []
        return gameState.value.available_actions
    })

    const currentRound = computed(() => {
        return gameState.value?.current_round || 1
    })

    const potAmount = computed(() => {
        return gameState.value?.bank || 0
    })

    const currentMaxBet = computed(() => {
        return gameState.value?.current_max_bet || 0
    })

    const gameStatus = computed(() => {
        return gameState.value?.status || 'waiting'
    })

    // 🔄 ОБНОВЛЕНИЕ СОСТОЯНИЯ ИЗ WebSocket
    const updateGameState = (newState) => {
        gameState.value = { ...gameState.value, ...newState }
    }

    // 🔄 ПРОВЕРКА ДОСТУПНОСТИ ДЕЙСТВИЯ
    const isActionAvailable = (action) => {
        return availableActions.value.includes(action)
    }

    // 🔄 РАСЧЕТ СУММЫ ДЛЯ CALL
    const getCallAmount = (player) => {
        if (!gameState.value || !player) return 0
        return Math.max(0, currentMaxBet.value - (player.current_bet || 0))
    }

    // 🔄 РАСЧЕТ СУММЫ ДЛЯ RAISE (минимальная)
    const getMinRaiseAmount = () => {
        return currentMaxBet.value > 0 ? currentMaxBet.value * 2 : 10
    }

    return {
        // Состояние
        gameState,
        
        // Вычисляемые свойства
        availableActions,
        currentRound,
        potAmount,
        currentMaxBet,
        gameStatus,
        
        // Методы
        updateGameState,
        isActionAvailable,
        getCallAmount,
        getMinRaiseAmount
    }
}