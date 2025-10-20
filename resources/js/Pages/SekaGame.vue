<template>
    <div class="seka-game">
        <div class="game-header">
            <h1>🎴 Seka Game #{{ gameId }}</h1>
            <div class="game-status" :class="gameStatusClass">
                {{ gameStatus.toUpperCase() }}
            </div>
        </div>

        <!-- Игровой стол -->
        <div class="game-table">
            <div class="community-cards">
                <h3>Community Cards</h3>
                <div class="cards">
                    <div v-for="card in communityCards" :key="card" class="card">
                        {{ card }}
                    </div>
                </div>
            </div>

            <div class="pot-info">
                🏦 Bank: {{ bank }} chips
            </div>
        </div>

        <!-- Список игроков -->
        <div class="players-section">
            <h3>Players ({{ readyPlayersCount }}/{{ totalPlayers }} ready)</h3>
            <div class="players-grid">
                <div v-for="player in players" 
                     :key="player.id" 
                     class="player-card"
                     :class="{
                         'current-turn': player.position === currentPlayerPosition,
                         'ready': player.is_ready,
                         'active': player.status === 'active'
                     }">
                    <div class="player-header">
                        <span class="position">#{{ player.position }}</span>
                        <span class="name">Player {{ player.id }}</span>
                        <span v-if="player.is_ready" class="ready-badge">✅</span>
                    </div>
                    <div class="player-stats">
                        <div>💰 {{ player.balance }}</div>
                        <div v-if="player.current_bet">💵 {{ player.current_bet }}</div>
                        <div class="status">{{ player.status }}</div>
                    </div>
                    <div v-if="playerCards[player.id]" class="player-hand">
                        🎴 {{ playerCards[player.id].join(', ') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Управление игрой -->
        <div class="game-controls">
            <button @click="markReady(1)" class="btn-ready">✅ Player 1 Ready</button>
            <button @click="markReady(2)" class="btn-ready">✅ Player 2 Ready</button>
            <button @click="markReady(3)" class="btn-ready">✅ Player 3 Ready</button>
            <div class="control-group">
                <h4>Game Controls</h4>
                <button @click="clearGame" class="btn-clear">🧹 Clear Game State</button>
                <button @click="quickStart" class="btn-start">🎲 Quick Start Game</button>
                <button @click="markReady(1)" class="btn-ready">✅ Player 1 Ready</button>
                <button @click="markReady(2)" class="btn-ready">✅ Player 2 Ready</button>
                <button @click="forceStart" class="btn-force">🚀 Force Start Game</button>
                <button @click="distributeCards" class="btn-distribute">🎴 Distribute Cards</button>
                <button @click="finishGame" class="btn-finish">🏆 Finish Game</button>
            </div>

            <div class="action-group">
                <h4>Player Actions</h4>
                <button @click="takeAction('check')" :disabled="!isMyTurn">✓ Check</button>
                <button @click="takeAction('call')" :disabled="!isMyTurn">📞 Call</button>
                <button @click="takeAction('raise')" :disabled="!isMyTurn">📈 Raise (50)</button>
                <button @click="takeAction('fold')" :disabled="!isMyTurn">❌ Fold</button>
            </div>
        </div>

        <!-- Лог событий -->
        <div class="events-panel">
            <h3>📨 Game Events (Real-time)</h3>
            <div class="events-list">
                <div v-for="event in gameEvents" 
                     :key="event.timestamp" 
                     class="event-item"
                     :class="event.type">
                    <span class="event-time">{{ event.timestamp }}</span>
                    <span class="event-name">{{ event.name }}</span>
                    <span class="event-data">{{ event.data }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    gameId: Number
})

// Состояние игры
const gameStatus = ref('waiting')
const players = ref([])
const currentPlayerPosition = ref(0)
const readyPlayersCount = ref(0)
const totalPlayers = ref(0)
const bank = ref(0)
const communityCards = ref([])
const playerCards = ref({})

// События и UI
const gameEvents = ref([])
const currentPlayerId = ref(1) // В реальности из auth

// Computed
const gameStatusClass = computed(() => `status-${gameStatus.value}`)
const isMyTurn = computed(() => {
    const currentPlayer = players.value.find(p => p.position === currentPlayerPosition.value)
    return currentPlayer && currentPlayer.id === currentPlayerId.value
})

// Инициализация
onMounted(() => {
    initializeGame()
    subscribeToGameEvents()
})

onUnmounted(() => {
    unsubscribeFromGameEvents()
})

// Методы игры
const initializeGame = async () => {
    try {
        const response = await axios.get(`/api/seka/${props.gameId}/status`)
        updateGameState(response.data)
        addGameEvent('system', 'Game initialized')
    } catch (error) {
        console.error('Failed to initialize game:', error)
        addGameEvent('error', 'Failed to initialize game')
    }
}

const quickStart = async () => {
    try {
        addGameEvent('action', 'Starting real game...')
        
        // 🎯 Используем твой реальный эндпоинт
        const response = await axios.post('/api/seka/start', {
            room_id: 1,
            players: [1, 2, 3] // реальные ID игроков
        })
        
        addGameEvent('success', `Real game started! Game ID: ${response.data.game_id}`)
        console.log('Real start response:', response.data)
        
    } catch (error) {
        addGameEvent('error', `Failed to start real game: ${error.response?.data?.message || error.message}`)
        console.error('Real start error:', error.response?.data || error.message)
    }
}

const markReady = async (playerId) => {
    try {
        // 🎯 Используем твой реальный эндпоинт
        const response = await axios.post(`/api/seka/${props.gameId}/ready`, {
            game_id: props.gameId,
            player_id: playerId
        })
        
        addGameEvent('player', `Player ${playerId} marked as ready in real game`)
        
    } catch (error) {
        addGameEvent('error', `Real ready failed: ${error.response?.data?.message || error.message}`)
    }
}

const distributeCards = async () => {
    try {
        // 🎯 Используем твой реальный эндпоинт  
        const response = await axios.post(`/api/seka/${props.gameId}/distribute`)
        addGameEvent('action', 'Real cards distribution requested')
        
    } catch (error) {
        addGameEvent('error', `Real distribute failed: ${error.response?.data?.message || error.message}`)
    }
}

const takeAction = async (action) => {
    try {
        const requestData = {
            player_id: currentPlayerId.value,
            action: action
        }

        if (action === 'raise') {
            requestData.bet_amount = 50
        }

        // 🎯 Используем твой реальный эндпоинт
        const response = await axios.post(`/api/seka/${props.gameId}/action`, requestData)
        addGameEvent('action', `Real action: ${action}`)
        
    } catch (error) {
        addGameEvent('error', `Real action failed: ${error.response?.data?.message || error.message}`)
    }
}

const finishGame = async () => {
    try {
        // 🎯 Используем твой реальный эндпоинт
        const response = await axios.post(`/api/seka/${props.gameId}/finish`)
        addGameEvent('action', 'Real game finish requested')
        
    } catch (error) {
        addGameEvent('error', `Real finish failed: ${error.response?.data?.message || error.message}`)
    }
}

// Pusher события
const subscribeToGameEvents = () => {
    const channel = window.Echo.channel(`game.${props.gameId}`)
    
    channel.listen('.GameStarted', (e) => {
        addGameEvent('game-start', `🎮 REAL Game Started with ${e.players.length} players`)
        updateGameState({
            players: e.players,
            status: e.state?.status || 'active', // 🎯 Теперь статус в state
            bank: e.state?.bank || 0,
            current_player_position: e.state?.current_player_position || 1
        })
    })
    
    channel.listen('.PlayerReady', (e) => {
        addGameEvent('player-ready', `✅ REAL Player ${e.player_id} is ready (${e.ready_players_count} ready)`)
        readyPlayersCount.value = e.ready_players_count
    })
    
    channel.listen('.CardsDistributed', (e) => {
        addGameEvent('cards', `🎴 REAL Cards distributed - Round: ${e.round}`)
        communityCards.value = e.community_cards
        playerCards.value = e.player_cards
        
        // Обновляем игроков с картами
        updatePlayersWithCards(e.player_cards)
    })
    
    channel.listen('.PlayerActionTaken', (e) => {
        addGameEvent('player-action', `🎯 REAL Player ${e.player_id} ${e.action} ${e.bet_amount ? e.bet_amount + ' chips' : ''}`)
        currentPlayerPosition.value = e.new_player_position
        bank.value = e.bank
        
        // Обновляем ставки игроков
        updatePlayerBet(e.player_id, e.bet_amount || 0)
    })
    
    channel.listen('.GameFinished', (e) => {
        const winnerId = e.winner_id || Object.keys(e.scores || {})[0]
        const prize = e.scores ? e.scores[winnerId] : 0
        
        addGameEvent('game-finish', `🏆 REAL Game Finished! Winner: ${winnerId}, Prize: ${prize} chips`)
        gameStatus.value = 'finished'
    })
}

const unsubscribeFromGameEvents = () => {
    window.Echo.leave(`game.${props.gameId}`)
}

// Вспомогательные методы
const updateGameState = (data) => {
    if (data.players) players.value = data.players
    if (data.status) gameStatus.value = data.status
    if (data.current_player_position) currentPlayerPosition.value = data.current_player_position
    if (data.ready_players_count) readyPlayersCount.value = data.ready_players_count
    if (data.total_players) totalPlayers.value = data.total_players
    if (data.bank) bank.value = data.bank
}

const addGameEvent = (type, data) => {
    gameEvents.value.unshift({
        type,
        name: type.toUpperCase(),
        data,
        timestamp: new Date().toLocaleTimeString()
    })
}

const updatePlayersWithCards = (playerCardsData) => {
    players.value = players.value.map(player => ({
        ...player,
        hand: playerCardsData[player.id] || []
    }))
}

const updatePlayerBet = (playerId, betAmount) => {
    players.value = players.value.map(player => 
        player.id === playerId 
            ? { ...player, current_bet: betAmount }
            : player
    )
}

const forceStartGame = async () => {
    try {
        const response = await axios.post(`/api/seka/${props.gameId}/force-start`)
        addGameEvent('action', `Game force started: ${response.data.message}`)
    } catch (error) {
        addGameEvent('error', `Failed to force start game: ${error.message}`)
    }
}

const clearGame = async () => {
    try {
        const response = await axios.post(`/api/seka/${props.gameId}/clear`)
        addGameEvent('system', `Game state cleared: ${response.data.message}`)
        
        // 🎯 Сбрасываем состояние фронтенда
        players.value = []
        gameStatus.value = 'waiting'
        readyPlayersCount.value = 0
        bank.value = 0
        communityCards.value = []
        playerCards.value = {}
        
    } catch (error) {
        addGameEvent('error', `Failed to clear game: ${error.message}`)
    }
}

</script>

<style scoped>
.btn-clear { 
    background: #6c757d; 
    color: white; 
}
.seka-game {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
    background: #f8f9fa;
    min-height: 100vh;
}

.game-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.game-status {
    padding: 10px 20px;
    border-radius: 20px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 14px;
}

.status-waiting { background: #fff3cd; color: #856404; }
.status-active { background: #d4edda; color: #155724; }
.status-finished { background: #f8d7da; color: #721c24; }

.game-table {
    background: #2d5016;
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 20px;
    text-align: center;
}

.community-cards .cards {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 15px;
}

.card {
    background: white;
    color: black;
    padding: 10px 15px;
    border-radius: 8px;
    font-weight: bold;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.pot-info {
    margin-top: 15px;
    font-size: 18px;
    font-weight: bold;
}

.players-section {
    margin-bottom: 20px;
}

.players-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.player-card {
    background: white;
    padding: 15px;
    border-radius: 10px;
    border: 2px solid #ddd;
    transition: all 0.3s ease;
}

.player-card.current-turn {
    border-color: #007bff;
    background: #e7f3ff;
    transform: scale(1.05);
}

.player-card.ready {
    border-color: #28a745;
}

.player-card.active {
    border-color: #17a2b8;
}

.player-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.ready-badge {
    color: #28a745;
    font-size: 16px;
}

.player-stats {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}

.player-hand {
    margin-top: 10px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 5px;
    font-size: 12px;
}

.game-controls {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.control-group, .action-group {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.control-group h4, .action-group h4 {
    margin-bottom: 15px;
    color: #495057;
}

.control-group button, .action-group button {
    display: block;
    width: 100%;
    margin-bottom: 10px;
    padding: 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s ease;
}

.btn-start { background: #28a745; color: white; }
.btn-ready { background: #17a2b8; color: white; }
.btn-distribute { background: #dc3545; color: white; }
.btn-finish { background: #6f42c1; color: white; }

.action-group button { 
    background: #007bff; 
    color: white; 
}

.action-group button:disabled {
    background: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
}

.control-group button:hover, .action-group button:hover:not(:disabled) {
    opacity: 0.9;
    transform: translateY(-2px);
}

.events-panel {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.events-list {
    max-height: 400px;
    overflow-y: auto;
}

.event-item {
    padding: 10px;
    margin-bottom: 8px;
    border-left: 4px solid #6c757d;
    border-radius: 4px;
    background: #f8f9fa;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}

.event-item.game-start { border-left-color: #28a745; background: #d4edda; }
.event-item.player-ready { border-left-color: #17a2b8; background: #d1ecf1; }
.event-item.cards { border-left-color: #dc3545; background: #f8d7da; }
.event-item.player-action { border-left-color: #ffc107; background: #fff3cd; }
.event-item.game-finish { border-left-color: #6f42c1; background: #e2e3f3; }
.event-item.error { border-left-color: #dc3545; background: #f8d7da; }

.event-time {
    color: #6c757d;
    margin-right: 10px;
}

.event-name {
    font-weight: bold;
    margin-right: 10px;
}
</style>