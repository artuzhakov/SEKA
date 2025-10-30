// 🎯 КОНСТАНТЫ ИГРЫ
export const GAME_STATES = {
    WAITING: 'waiting',
    ACTIVE: 'active',
    BIDDING: 'bidding',
    FINISHED: 'finished'
}

export const ROUNDS = {
    1: 'first',
    2: 'second', 
    3: 'third'
}

export const ACTIONS = {
    CHECK: 'check',
    CALL: 'call',
    RAISE: 'raise',
    FOLD: 'fold',
    DARK: 'dark',
    REVEAL: 'reveal',
    OPEN: 'open'
}

// 🎯 ВАЛИДАЦИЯ ДЕЙСТВИЙ
export function validateAction(action, gameState, playerId) {
    const player = gameState.players.find(p => p.id === playerId)
    if (!player) {
        return { isValid: false, error: 'Игрок не найден' }
    }

    if (player.id !== gameState.currentPlayer) {
        return { isValid: false, error: 'Сейчас не ваш ход' }
    }

    if (player.isFolded) {
        return { isValid: false, error: 'Вы уже сбросили карты' }
    }

    // Валидация по раундам
    const roundValidations = {
        1: [ACTIONS.CHECK, ACTIONS.DARK, ACTIONS.FOLD, ACTIONS.CALL, ACTIONS.RAISE],
        2: [ACTIONS.REVEAL, ACTIONS.FOLD, ACTIONS.CALL, ACTIONS.RAISE, ACTIONS.OPEN],
        3: [ACTIONS.FOLD, ACTIONS.CALL, ACTIONS.RAISE, ACTIONS.OPEN]
    }

    if (!roundValidations[gameState.currentRound]?.includes(action)) {
        return { isValid: false, error: `Действие ${action} недоступно в раунде ${gameState.currentRound}` }
    }

    // Специфические проверки
    const playerBet = player.currentBet || 0
    const currentMaxBet = Math.max(...gameState.players.map(p => p.currentBet || 0))
    const needsCall = currentMaxBet > playerBet

    if (action === ACTIONS.CHECK && needsCall) {
        return { isValid: false, error: 'Нельзя проверить при активной ставке' }
    }

    if (action === ACTIONS.CALL && !needsCall) {
        return { isValid: false, error: 'Нет активной ставки для поддержания' }
    }

    if (action === ACTIONS.DARK && player.isDark) {
        return { isValid: false, error: 'Вы уже играете в темную' }
    }

    if (action === ACTIONS.REVEAL && gameState.currentRound === 1) {
        return { isValid: false, error: 'Вскрытие доступно только в раундах 2 и 3' }
    }

    if (action === ACTIONS.OPEN && !player.isDark) {
        return { isValid: false, error: 'Открытие доступно только после темной игры' }
    }

    return { isValid: true }
}

// 🎯 РАСЧЕТ СТАВОК
export function calculateActionAmount(action, gameState, playerId) {
    const player = gameState.players.find(p => p.id === playerId)
    const currentMaxBet = Math.max(...gameState.players.map(p => p.currentBet || 0))
    const playerBet = player?.currentBet || 0

    switch (action) {
        case ACTIONS.CALL:
            return currentMaxBet - playerBet

        case ACTIONS.DARK:
            // 50% от текущей максимальной ставки
            return Math.floor(currentMaxBet * 0.5)

        case ACTIONS.REVEAL:
            // Удвоение ставки для вскрытия
            return currentMaxBet * 2

        default:
            return 0
    }
}

// 🎯 СИСТЕМА ПОДСЧЕТА ОЧКОВ
export function calculateHandScore(cards) {
    if (!cards || cards.length !== 3) return 0

    const values = cards.map(card => card.value).sort((a, b) => a - b)
    const suits = cards.map(card => card.suit)

    // 1. ТРОЙКА (Three of a Kind) - самая сильная
    if (values[0] === values[1] && values[1] === values[2]) {
        return 100000 + (values[0] * 1000) + values[0]
    }

    // 2. СТРИТ (Straight) - три последовательные карты
    const isStraight = (values[2] - values[1] === 1 && values[1] - values[0] === 1) ||
                      // Особый случай: A-2-3
                      (values[0] === 2 && values[1] === 3 && values[2] === 14)
    
    if (isStraight) {
        // Для A-2-3 используем 3 как старшую
        const highCard = (values[2] === 14 && values[0] === 2) ? 3 : values[2]
        return 80000 + (highCard * 1000)
    }

    // 3. ФЛЕШ (Flush) - три карты одной масти
    if (suits[0] === suits[1] && suits[1] === suits[2]) {
        return 60000 + (values[2] * 1000) + (values[1] * 100) + values[0]
    }

    // 4. ПАРА (Pair) - две карты одного достоинства
    let pairValue = 0
    let kicker = 0

    if (values[0] === values[1]) {
        pairValue = values[0]
        kicker = values[2]
    } else if (values[1] === values[2]) {
        pairValue = values[1]
        kicker = values[0]
    } else if (values[0] === values[2]) {
        pairValue = values[0]
        kicker = values[1]
    }

    if (pairValue > 0) {
        return 40000 + (pairValue * 1000) + kicker
    }

    // 5. СТАРШАЯ КАРТА (High Card)
    return (values[2] * 1000) + (values[1] * 100) + values[0]
}

// 🎯 ОПРЕДЕЛЕНИЕ ПОБЕДИТЕЛЯ
export function determineWinner(players) {
    const activePlayers = players.filter(p => !p.isFolded && p.cards?.length === 3)

    if (activePlayers.length === 0) {
        return { winners: [], isVara: false }
    }

    if (activePlayers.length === 1) {
        return { winners: [activePlayers[0]], isVara: false }
    }

    // Рассчитываем очки для каждого игрока
    const scoredPlayers = activePlayers.map(player => ({
        ...player,
        score: calculateHandScore(player.cards),
        handStrength: getHandStrength(calculateHandScore(player.cards))
    })).sort((a, b) => b.score - a.score)

    // Проверяем ничью (ВАРА)
    const topScore = scoredPlayers[0].score
    const winners = scoredPlayers.filter(p => p.score === topScore)

    return {
        winners: winners.length === 1 ? winners : [],
        isVara: winners.length > 1,
        allScores: scoredPlayers
    }
}

// 🎯 ПОЛУЧЕНИЕ НАЗВАНИЯ КОМБИНАЦИИ
export function getHandStrength(score) {
    if (score >= 100000) return 'Тройка'
    if (score >= 80000) return 'Стрит'
    if (score >= 60000) return 'Флеш'
    if (score >= 40000) return 'Пара'
    return 'Старшая карта'
}

// 🎯 МЕХАНИКА ВАРА (перераздача)
export function handleVaraSituation(gameState) {
    // При ВАРА - перераздача с увеличенным банком
    console.log('⚡ Активирована механика ВАРА - перераздача')
    
    return {
        ...gameState,
        isVara: true,
        pot: gameState.pot * 2, // Удваиваем банк
        currentRound: 1,
        // Сбрасываем состояния игроков но сохраняем банк
        players: gameState.players.map(player => ({
            ...player,
            isFolded: false,
            isDark: false,
            currentBet: 0,
            cards: [] // Карты будут переразданы
        }))
    }
}

// 🎯 ПРОВЕРКА ЗАВЕРШЕНИЯ РАУНДА
export function isRoundComplete(gameState) {
    const activePlayers = gameState.players.filter(p => !p.isFolded)
    
    // Все активные игроки сделали ставки
    const currentMaxBet = Math.max(...gameState.players.map(p => p.currentBet || 0))
    const allPlayersEqualized = activePlayers.every(player => 
        (player.currentBet || 0) === currentMaxBet || player.isFolded
    )

    return allPlayersEqualized && activePlayers.length > 0
}