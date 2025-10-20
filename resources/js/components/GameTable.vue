<template>
    <div class="game-table">
        <!-- Игровой стол -->
        <div class="players-container">
            <PlayerComponent 
                v-for="player in players"
                :key="player.id"
                :player="player"
                :is-current-turn="isCurrentTurn(player)"
            />
        </div>
        
        <!-- Банк игры -->
        <div class="game-bank">
            Банк: {{ bank }}
        </div>
        
        <!-- Таймеры -->
        <div class="timers">
            <div v-for="timer in timers" :key="timer.player_id">
                Игрок {{ timer.player_id }}: {{ timer.turn_time_remaining }}с
            </div>
        </div>
        
        <!-- Действия игрока -->
        <PlayerActions 
            v-if="isMyTurn"
            @action="handlePlayerAction"
        />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import PlayerComponent from './PlayerComponent.vue'
import PlayerActions from './PlayerActions.vue'

const props = defineProps({
    gameId: Number,
    initialPlayers: Array,
    initialBank: Number
})

const players = ref(props.initialPlayers)
const bank = ref(props.initialBank)
const timers = ref([])
const currentUser = usePage().props.auth.user

// Подписка на WebSocket события
onMounted(() => {
    subscribeToGameEvents()
})

onUnmounted(() => {
    unsubscribeFromGameEvents()
})

const subscribeToGameEvents = () => {
    // Подписка на канал игры
    window.Echo.private(`game.${props.gameId}`)
        .listen('GameStarted', (e) => {
            console.log('Game started:', e)
            players.value = e.players
        })
        .listen('PlayerReady', (e) => {
            console.log('Player ready:', e)
            updatePlayerStatus(e.playerId, e.playerStatus)
        })
        .listen('PlayerActionTaken', (e) => {
            console.log('Player action:', e)
            handlePlayerActionEvent(e)
        })
        .listen('CardsDistributed', (e) => {
            console.log('Cards distributed:', e)
            updatePlayersCards(e.players)
        })
        .listen('TimersUpdated', (e) => {
            console.log('Timers updated:', e)
            timers.value = e.timers
        })
}

const unsubscribeFromGameEvents = () => {
    window.Echo.leave(`game.${props.gameId}`)
}

const isCurrentTurn = (player) => {
    return player.position === currentPlayerPosition.value
}

const isMyTurn = computed(() => {
    return players.value.some(p => 
        p.user_id === currentUser.id && isCurrentTurn(p)
    )
})

const handlePlayerAction = (action) => {
    // Отправка действия на сервер
    axios.post(`/api/games/${props.gameId}/action`, {
        player_id: currentUser.id,
        action: action.type,
        bet_amount: action.betAmount
    })
}
</script>