import { ref, computed } from 'vue'
import { validateAction, calculateActionAmount, determineWinner, isRoundComplete, handleVaraSituation } from '../utils/gameRules'

export function useGameLogic(initialGameState = null) {
    const gameState = ref(initialGameState || createInitialGameState())
    const isLoading = ref(false)
    const error = ref(null)

    // 🎯 СОЗДАНИЕ НАЧАЛЬНОГО СОСТОЯНИЯ
    function createInitialGameState() {
        return {
            id: null,
            status: 'waiting',
            currentRound: 1,
            pot: 0,
            currentPlayer: null,
            players: [],
            deck: [],
            currentMaxBet: 0,
            isVara: false,
            winner: null,
            history: []
        }
    }

    // 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
    const currentPlayerInfo = computed(() => {
        if (!gameState.value.currentPlayer) return null
        return gameState.value.players.find(p => p.id === gameState.value.currentPlayer)
    })

    const availableActions = computed(() => {
        if (!gameState.value.currentPlayer) return []
        
        const round = gameState.value.currentRound
        const baseActions = ['fold', 'call', 'raise']
        
        if (round === 1) return ['check', 'dark', ...baseActions]
        if (round === 2) return ['reveal', 'open', ...baseActions]
        return ['open', ...baseActions]
    })

    const activePlayers = computed(() => {
        return gameState.value.players.filter(p => !p.isFolded)
    })

    // 🎯 ОСНОВНЫЕ МЕТОДЫ

    // Обновление состояния игры
    const updateGameState = (newState) => {
        gameState.value = { ...gameState.value, ...newState }
        error.value = null
    }

    // Выполнение действия игрока
    const performGameAction = async (action, betAmount = null) => {
        isLoading.value = true
        error.value = null

        try {
            // Валидация действия
            const validation = validateAction(action, gameState.value, gameState.value.currentPlayer)
            if (!validation.isValid) {
                throw new Error(validation.error)
            }

            // Расчет суммы ставки если не указана
            if (!betAmount && ['call', 'dark', 'reveal'].includes(action)) {
                betAmount = calculateActionAmount(action, gameState.value, gameState.value.currentPlayer)
            }

            // Обновление состояния игры
            const newState = processGameAction(gameState.value, action, betAmount)
            updateGameState(newState)

            // Проверка завершения раунда
            if (isRoundComplete(newState)) {
                await advanceRound(newState)
            }

            // Проверка завершения игры
            if (activePlayers.value.length === 1) {
                await finishGame()
            }

            return newState

        } catch (err) {
            error.value = err.message
            console.error('Game action error:', err)
            throw err
        } finally {
            isLoading.value = false
        }
    }

    // 🎯 ОБРАБОТКА ИГРОВЫХ ДЕЙСТВИЙ
    function processGameAction(currentState, action, betAmount) {
        const playerId = currentState.currentPlayer
        const playerIndex = currentState.players.findIndex(p => p.id === playerId)
        
        if (playerIndex === -1) {
            throw new Error('Player not found')
        }

        const updatedPlayers = [...currentState.players]
        const player = { ...updatedPlayers[playerIndex] }
        let newPot = currentState.pot
        let newMaxBet = currentState.currentMaxBet

        // Обработка действий
        switch (action) {
            case 'check':
                // Ничего не делаем, просто передаем ход
                break

            case 'fold':
                player.isFolded = true
                break

            case 'call':
                player.currentBet = currentState.currentMaxBet
                player.totalBet = (player.totalBet || 0) + (currentState.currentMaxBet - (player.currentBet || 0))
                newPot += currentState.currentMaxBet - (player.currentBet || 0)
                break

            case 'raise':
                player.currentBet = betAmount
                player.totalBet = (player.totalBet || 0) + betAmount
                newPot += betAmount
                newMaxBet = Math.max(newMaxBet, betAmount)
                break

            case 'dark':
                player.isDark = true
                player.currentBet = betAmount
                player.totalBet = (player.totalBet || 0) + betAmount
                newPot += betAmount
                // Скрываем карты
                player.cards = player.cards.map(card => ({ ...card, isVisible: false }))
                break

            case 'reveal':
                player.isDark = false
                player.currentBet = betAmount
                player.totalBet = (player.totalBet || 0) + betAmount
                newPot += betAmount
                newMaxBet = Math.max(newMaxBet, betAmount)
                // Показываем карты
                player.cards = player.cards.map(card => ({ ...card, isVisible: true }))
                break

            case 'open':
                player.isDark = false
                // Показываем карты
                player.cards = player.cards.map(card => ({ ...card, isVisible: true }))
                break
        }

        updatedPlayers[playerIndex] = player

        // Определяем следующего игрока
        const nextPlayer = getNextPlayer(currentState, playerId)

        return {
            ...currentState,
            players: updatedPlayers,
            pot: newPot,
            currentMaxBet: newMaxBet,
            currentPlayer: nextPlayer,
            history: [
                ...currentState.history,
                {
                    playerId,
                    action,
                    amount: betAmount,
                    timestamp: new Date().toISOString()
                }
            ]
        }
    }

    // 🎯 ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    function getNextPlayer(currentState, currentPlayerId) {
        const activePlayers = currentState.players.filter(p => !p.isFolded)
        const currentIndex = activePlayers.findIndex(p => p.id === currentPlayerId)
        const nextIndex = (currentIndex + 1) % activePlayers.length
        return activePlayers[nextIndex]?.id || null
    }

    async function advanceRound(currentState) {
        if (currentState.currentRound >= 3) {
            await finishGame()
            return
        }

        // Переход к следующему раунду
        gameState.value = {
            ...currentState,
            currentRound: currentState.currentRound + 1,
            currentMaxBet: 0,
            players: currentState.players.map(player => ({
                ...player,
                currentBet: 0
            }))
        }
    }

    async function finishGame() {
        const result = determineWinner(gameState.value.players)
        
        if (result.isVara) {
            // Активируем ВАРА
            gameState.value = handleVaraSituation(gameState.value)
        } else {
            // Завершаем игру
            gameState.value = {
                ...gameState.value,
                status: 'finished',
                winner: result.winners[0] || null,
                winners: result.winners
            }
        }
    }

    // 🎯 СБРОС ОШИБКИ
    const clearError = () => {
        error.value = null
    }

    return {
        // Состояние
        gameState,
        isLoading,
        error,
        
        // Вычисляемые свойства
        currentPlayerInfo,
        availableActions,
        activePlayers,
        
        // Методы
        updateGameState,
        performGameAction,
        clearError
    }
}