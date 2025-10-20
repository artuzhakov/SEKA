<template>
    <div class="pusher-test">
        <h3>🧪 Pusher Connection Test</h3>
        
        <div class="connection-status" :class="connectionStatus">
            Status: {{ connectionStatus }}
        </div>

        <div class="test-buttons">
            <button @click="checkSubscription">Check Subscription</button>
            <button @click="testDirectPusher">Test Direct Pusher</button>
            <button @click="simpleSubscriptionTest">Simple Subscription Test</button>
            <button @click="testGlobalListener">Test Global Listener</button>
        </div>

        <div class="test-controls">
            <input v-model="testGameId" type="number" placeholder="Game ID" />
            <input v-model="testMessage" type="text" placeholder="Test message" />
            <button @click="sendTestEvent" :disabled="!isConnected">
                Send Test Event
            </button>
        </div>

        <!-- ТЕСТОВЫЕ КНОПКИ ИГРЫ -->
        <div class="game-test-buttons">
            <h4>🎮 Тест игровых событий:</h4>
            <div class="button-group">
                <button @click.prevent="startGame" class="btn-game-start">🎲 Начать игру</button>
                <button @click.prevent="joinGame" class="btn-game-join">👤 Присоединиться</button>
                <button @click.prevent="playCard" class="btn-game-card">🎴 Сыграть карту</button>
                <button @click.prevent="changeTurn" class="btn-game-turn">🔄 Сменить ход</button>
                <button @click.prevent="finishGame" class="btn-game-finish">🏆 Завершить игру</button>
            </div>
        </div>

        <div class="events-log">
            <h4>Connection Log:</h4>
            <div v-for="(log, index) in connectionLog" :key="index" class="log-item">
                [{{ log.timestamp }}] {{ log.message }}
            </div>
        </div>

        <div class="events-log">
            <h4>Received Events:</h4>
            <div v-for="(event, index) in receivedEvents" :key="index" class="event-item">
                <strong>{{ event.name }}</strong>: {{ event.data }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const testGameId = ref(1)
const testMessage = ref('Hello Pusher!')
const connectionStatus = ref('disconnected')
const isConnected = ref(false)
const receivedEvents = ref([])
const connectionLog = ref([])

const addLog = (message) => {
    connectionLog.value.unshift({
        message,
        timestamp: new Date().toLocaleTimeString()
    })
    console.log('Pusher Log:', message)
}

// Подписка на Pusher события
onMounted(() => {
    addLog('Component mounted')
    initializePusher()
})

onUnmounted(() => {
    disconnectPusher()
})

const initializePusher = () => {
    try {
        addLog('Initializing Pusher...')

        if (!window.Echo) {
            addLog('ERROR: window.Echo is not defined')
            return
        }

        // Подписка на статус подключения
        window.Echo.connector.pusher.connection.bind('connected', () => {
            connectionStatus.value = 'connected'
            isConnected.value = true
            addLog('✅ Pusher connected successfully!')
        })

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            connectionStatus.value = 'disconnected'
            isConnected.value = false
            addLog('❌ Pusher disconnected')
        })

        window.Echo.connector.pusher.connection.bind('error', (error) => {
            connectionStatus.value = 'error'
            isConnected.value = false
            addLog(`💥 Pusher error: ${error?.message || 'Unknown error'}`)
        })

        // Подписка на тестовый канал
        subscribeToChannel()

    } catch (error) {
        addLog(`Failed to initialize Pusher: ${error.message}`)
        connectionStatus.value = 'error'
    }
}

const subscribeToChannel = () => {
    const channelName = `game.${testGameId.value}`
    addLog(`🔔 Subscribing to channel: ${channelName}`)

    try {
        const channel = window.Echo.channel(channelName)

        // Основной слушатель для TestPusherEvent
        channel.listen('.TestPusherEvent', (e) => {
            addLog(`🎉 [MAIN] Received TestPusherEvent: ${e.message}`)
            receivedEvents.value.unshift({
                name: 'TestPusherEvent',
                data: e,
                timestamp: new Date().toLocaleTimeString()
            })
        })

        // Альтернативный слушатель (если используем broadcastAs)
        channel.listen('.test.event', (e) => {
            addLog(`🎉 [CUSTOM] Received test.event: ${e.message}`)
            receivedEvents.value.unshift({
                name: 'test.event',
                data: e,
                timestamp: new Date().toLocaleTimeString()
            })
        })

        // Глобальный слушатель для диагностики
        channel.listenToAll((eventName, data) => {
            // Игнорируем системные события Pusher
            if (eventName.startsWith('pusher:')) return;
            
            addLog(`🌍 [GLOBAL] Event: ${eventName}`)
            receivedEvents.value.unshift({
                name: eventName,
                data: data,
                timestamp: new Date().toLocaleTimeString()
            })
        })

        channel.listen('pusher:subscription_succeeded', () => {
            addLog(`✅ Subscribed to: ${channelName}`)
        })

    } catch (error) {
        addLog(`💥 Subscription failed: ${error.message}`)
    }
}

const disconnectPusher = () => {
    window.Echo.leave(`game.${testGameId.value}`)
    addLog('Pusher disconnected')
}

const checkSubscription = () => {
    addLog('=== Checking Pusher Connection ===')
    
    if (!window.Echo) {
        addLog('❌ ERROR: window.Echo is not defined')
        return
    }
    addLog('✅ window.Echo found')

    if (!window.Echo.connector) {
        addLog('❌ ERROR: Echo connector not found')
        return
    }
    addLog('✅ Echo connector found')

    if (!window.Echo.connector.pusher) {
        addLog('❌ ERROR: Pusher instance not found')
        return
    }
    addLog('✅ Pusher instance found')

    const pusher = window.Echo.connector.pusher
    
    addLog(`Pusher connection state: ${pusher.connection.state}`)
    addLog(`Pusher socket ID: ${pusher.connection.socket_id || 'No socket ID'}`)
    addLog(`WebSocket supported: ${typeof WebSocket !== 'undefined'}`)
    
    const allChannels = Object.keys(pusher.channels.channels)
    addLog(`All subscribed channels: ${JSON.stringify(allChannels)}`)
    
    const channelName = `game.${testGameId.value}`
    const channel = pusher.channel(channelName)
    if (channel) {
        addLog(`✅ Our channel found: ${channelName}`)
        addLog(`Channel subscribed: ${channel.subscribed}`)
    } else {
        addLog(`❌ Our channel NOT found: ${channelName}`)
    }
}

const testDirectPusher = () => {
    addLog('=== Testing Direct Pusher Subscription ===')
    
    try {
        const pusher = new window.Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
            forceTLS: true
        })
        
        const channelName = `game.${testGameId.value}`
        addLog(`Subscribing to: ${channelName}`)
        
        const channel = pusher.subscribe(channelName)
        
        channel.bind('pusher:subscription_succeeded', () => {
            addLog('✅ Direct Pusher: Channel subscribed successfully')
        })
        
        channel.bind('pusher:subscription_error', (error) => {
            addLog(`❌ Direct Pusher: Subscription error: ${JSON.stringify(error)}`)
        })
        
        channel.bind('test.event', (data) => {
            addLog(`📨 Direct Pusher: Event received: ${JSON.stringify(data)}`)
            receivedEvents.value.unshift({
                name: 'direct.test.event',
                data: data,
                timestamp: new Date().toLocaleTimeString()
            })
        })
        
        setTimeout(() => {
            addLog('Sending test event for direct Pusher...')
            sendTestEvent()
        }, 2000)
        
    } catch (error) {
        addLog(`💥 Direct Pusher error: ${error.message}`)
    }
}

const simpleSubscriptionTest = () => {
    addLog('=== Simple Subscription Test ===')
    
    const channelName = `game.${testGameId.value}`
    
    // Отписываемся от старого канала
    window.Echo.leave(channelName)
    
    // Подписываемся заново
    subscribeToChannel()
    
    addLog(`Resubscribed to: ${channelName}`)
}

const sendTestEvent = async () => {
    try {
        addLog(`Sending test event to game ${testGameId.value}...`)
        
        const response = await axios.post('/api/test/pusher/event', {
            game_id: testGameId.value,
            message: testMessage.value
        })

        addLog('✅ Test event sent successfully')
        
        // НЕ добавляем sent.event в receivedEvents - будем ждать реальное событие от Pusher

    } catch (error) {
        addLog(`💥 Failed to send test event: ${error.message}`)
        receivedEvents.value.unshift({
            name: 'error',
            data: `Failed to send: ${error.message}`,
            timestamp: new Date().toLocaleTimeString()
        })
    }
}

const testGlobalListener = () => {
    addLog('=== Testing Global Listeners ===')
    
    // Слушаем ВСЕ события на канале
    const channelName = `game.${testGameId.value}`
    const channel = window.Echo.channel(channelName)
    
    // Слушаем все события без фильтра
    channel.listenToAll((eventName, data) => {
        addLog(`🌍 GLOBAL EVENT: ${eventName}`, data)
        console.log('Global event:', eventName, data)
        
        receivedEvents.value.unshift({
            name: eventName,
            data: data,
            timestamp: new Date().toLocaleTimeString()
        })
    })
    
    addLog('Global listener activated - listening to ALL events')
}

const subscribeToGameEvents = () => {
    const channelName = `game.${testGameId.value}`
    
    // Игровые события
    window.Echo.channel(channelName)
        .listen('.GameStarted', (e) => {
            addLog(`🎮 Game Started: ${e.players.length} players`)
            receivedEvents.value.unshift({
                name: 'GameStarted',
                data: e,
                timestamp: new Date().toLocaleTimeString()
            })
        })
        .listen('.PlayerJoined', (e) => {
            addLog(`👤 Player Joined: ${e.player.name}`)
            receivedEvents.value.unshift({
                name: 'PlayerJoined', 
                data: e,
                timestamp: new Date().toLocaleTimeString()
            })
        })
        .listen('.CardPlayed', (e) => {
            addLog(`🎴 Card Played: ${e.player_id} played ${e.card.value}${e.card.suit}`)
            receivedEvents.value.unshift({
                name: 'CardPlayed',
                data: e,
                timestamp: new Date().toLocaleTimeString()
            })
        })
        .listen('.TurnChanged', (e) => {
            addLog(`🔄 Turn Changed: ${e.current_player_id}'s turn`)
            receivedEvents.value.unshift({
                name: 'TurnChanged',
                data: e,
                timestamp: new Date().toLocaleTimeString()
            })
        })
        .listen('.GameFinished', (e) => {
            addLog(`🏆 Game Finished: Winner ${e.winner_id}`)
            receivedEvents.value.unshift({
                name: 'GameFinished',
                data: e,
                timestamp: new Date().toLocaleTimeString()
            })
        })
}

// ИГРОВЫЕ МЕТОДЫ
const startGame = async () => {
    try {
        addLog('🎲 Starting game...')
        const response = await axios.post(`/api/game/${testGameId.value}/start`, {}, {
            headers: {
                'Content-Type': 'application/json'
            }
        })
        addLog(`✅ ${response.data.message}`)
    } catch (error) {
        addLog(`💥 Failed to start game: ${error.response?.data?.message || error.message}`)
    }
}

const joinGame = async () => {
    try {
        addLog('👤 Joining game...')
        const playerId = 'test_player_' + Math.random().toString(36).substr(2, 5)
        const response = await axios.post(`/api/game/${testGameId.value}/join`, {
            player_id: playerId,
            player_name: 'TestPlayer'
        }, {
            headers: {
                'Content-Type': 'application/json'
            }
        })
        addLog(`✅ ${response.data.message}`)
    } catch (error) {
        addLog(`💥 Failed to join game: ${error.response?.data?.message || error.message}`)
    }
}

const playCard = async () => {
    try {
        addLog('🎴 Playing card...')
        const response = await axios.post(`/api/game/${testGameId.value}/play-card`, {
            player_id: 'player1',
            card: { value: 'A', suit: '♥', code: 'AH' },
            action: 'raise'
        }, {
            headers: {
                'Content-Type': 'application/json'
            }
        })
        addLog(`✅ ${response.data.message}`)
    } catch (error) {
        addLog(`💥 Failed to play card: ${error.response?.data?.message || error.message}`)
    }
}

const changeTurn = async () => {
    try {
        addLog('🔄 Changing turn...')
        const response = await axios.post(`/api/game/${testGameId.value}/change-turn`, {
            previous_player_id: 'player1',
            current_player_id: 'player2',
            turn_time_left: 30
        }, {
            headers: {
                'Content-Type': 'application/json'
            }
        })
        addLog(`✅ ${response.data.message}`)
    } catch (error) {
        addLog(`💥 Failed to change turn: ${error.response?.data?.message || error.message}`)
    }
}

const finishGame = async () => {
    try {
        addLog('🏆 Finishing game...')
        const response = await axios.post(`/api/game/${testGameId.value}/finish`, {
            winner_id: 'player1',
            scores: { player1: 1500, player2: 500 },
            final_state: { pot: 2000, winner: 'player1' }
        }, {
            headers: {
                'Content-Type': 'application/json'
            }
        })
        addLog(`✅ ${response.data.message}`)
    } catch (error) {
        addLog(`💥 Failed to finish game: ${error.response?.data?.message || error.message}`)
    }
}

</script>

<style scoped>
.pusher-test {
    padding: 20px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    margin: 20px 0;
}

.connection-status {
    padding: 8px 12px;
    border-radius: 4px;
    margin-bottom: 15px;
    font-weight: bold;
}

.connection-status.connected {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.connection-status.disconnected {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.connection-status.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.test-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.test-controls {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.test-controls input {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    flex: 1;
    min-width: 120px;
}

.test-buttons button,
.test-controls button {
    padding: 8px 16px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.test-controls button:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

.events-log {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
}

.log-item, .event-item {
    padding: 8px;
    margin-bottom: 8px;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 4px solid #6c757d;
    font-family: monospace;
    font-size: 12px;
}

.event-item {
    border-left-color: #007bff;
}
</style>