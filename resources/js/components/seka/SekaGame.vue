
<!-- resources/js/components/seka/SekaGame.vue -->
<template>
    <div class="seka-game">
        <div v-if="authError" class="auth-error">
        <div class="error-message">
            <h3>🔐 Требуется авторизация</h3>
            <p>{{ authError }}</p>
            <button @click="redirectToLogin" class="btn-login">
            Войти в систему
            </button>
        </div>
        </div>

        <template v-else>
        <GameHeader 
            :game-id="gameId"
            :game-status="gameStatus"
            :current-round="currentRound"
            :user="user"
        />
        
        <Notifications />
        
        <PlayerControlPanel 
        v-if="gameStatus === 'bidding'"
        :current-player-id="currentPlayerId"
        :players="players"
        :current-player-position="currentPlayerPosition"
        @switch-player="switchPlayer"
        />
        
        <ActionPanel 
        v-if="gameStatus === 'bidding' && isMyTurn"
        :available-actions="availableActions"
        :current-player-info="currentPlayerInfo"
        :current-max-bet="currentMaxBet"
        @take-action="takeAction"
        @show-raise-modal="showRaiseModal = true"
        />
        
        <RaiseModal 
        v-if="showRaiseModal"
        :min-raise="minRaise"
        :max-raise="maxRaise"
        :current-player-info="currentPlayerInfo"
        :current-max-bet="currentMaxBet"
        @execute-raise="executeRaise"
        @cancel="cancelRaise"
        />
        
        <GameTable 
        :players="players"
        :player-cards="playerCards"
        :current-player-position="currentPlayerPosition"
        :bank="bank"
        :current-round="currentRound"
        :game-status="gameStatus"
        />
        
        <MonitoringPanel />
        
        <TestPanel />
        
        <DebugPanel 
        v-if="showDebug"
        :game-status="gameStatus"
        :current-player-position="currentPlayerPosition"
        :current-player-id="currentPlayerId"
        :is-my-turn="isMyTurn"
        :active-players-count="activePlayersCount"
        :current-round="currentRound"
        :available-actions="availableActions"
        />

        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useGameState, useGameActions, useGameMonitoring, useGameTesting, useNotifications } from '@/composables'
import { validateGameState, checkDataConsistency } from '@/utils/gameValidators'

// Components
import GameHeader from './components/GameHeader.vue'
import GameTable from './components/GameTable.vue'
import PlayerControlPanel from './components/PlayerControlPanel.vue'
import ActionPanel from './components/ActionPanel.vue'
import RaiseModal from './components/RaiseModal.vue'
import Notifications from './components/Notifications.vue'
import MonitoringPanel from './components/MonitoringPanel.vue'
import TestPanel from './components/TestPanel.vue'
import DebugPanel from './components/DebugPanel.vue'

const props = defineProps({
  gameId: Number
})

// Auth
const { user, isAuthenticated, checkAuth } = useAuth()

// Composables
const {
  gameStatus,
  players,
  currentPlayerPosition,
  currentPlayerId,
  bank,
  currentRound,
  playerCards,
  showAllCards,
  initializeGame,
  switchPlayer,
  updateGameState
} = useGameState(props.gameId)

const {
  takeAction,
  executeRaise,
  showRaiseModal,
  raiseAmount,
  cancelRaise
} = useGameActions(props.gameId, { currentPlayerId, players, updateGameState })

const { showNotification } = useNotifications()
const { startStateMonitoring } = useGameMonitoring({ gameStatus, players, bank, currentRound, currentPlayerPosition })
const { runQuickTest, runComprehensiveTest } = useGameTesting(props.gameId, { initializeGame, showNotification })

// Computed
const isMyTurn = computed(() => {
  const currentPlayer = players.value.find(p => p.position === currentPlayerPosition.value)
  return currentPlayer ? currentPlayer.id === currentPlayerId.value : false
})

const currentPlayerInfo = computed(() => 
  players.value.find(p => p.id === currentPlayerId.value)
)

const currentMaxBet = computed(() => 
  Math.max(...players.value.map(p => p.current_bet || 0))
)

const activePlayersCount = computed(() => 
  players.value.filter(p => p.status === 'active').length
)

const availableActions = computed(() => {
  if (!isMyTurn.value || gameStatus.value !== 'bidding') return []
  
  const player = currentPlayerInfo.value
  if (!player) return []
  
  const actions = ['fold']
  
  if (currentMaxBet.value > 0 && player.current_bet < currentMaxBet.value) {
    actions.push('call')
  }
  
  if (player.balance > (currentMaxBet.value - player.current_bet)) {
    actions.push('raise')
  }
  
  if (currentRound.value === 1) {
    if (currentMaxBet.value === 0) {
      actions.push('check')
    }
    if (!player.has_played_dark && player.current_bet === 0) {
      actions.push('dark')
    }
  }
  
  if (currentRound.value >= 2) {
    actions.push('reveal')
  }
  
  if (player.has_played_dark || player.status === 'dark') {
    actions.push('open')
  }
  
  return actions
})

const minRaise = computed(() => {
  const playerBet = currentPlayerInfo.value?.current_bet || 0
  return Math.max(1, currentMaxBet.value - playerBet + 1)
})

const maxRaise = computed(() => 
  currentPlayerInfo.value?.balance || 0
)

// UI State
const showDebug = ref(true)

const redirectToLogin = () => {
  window.location.href = '/login'
}

// Lifecycle
onMounted(async () => {
  const authenticated = await checkAuth()
  if (authenticated) {
    initializeGame()
    startStateMonitoring()
    startAutoRefresh()
  }
})

const startAutoRefresh = () => {
  setInterval(() => {
    if (gameStatus.value === 'bidding') {
      initializeGame()
    }
  }, 2000)
}

// Expose for testing
defineExpose({
  gameStatus,
  players,
  currentPlayerPosition,
  currentPlayerId,
  availableActions,
  initializeGame
})
</script>

<style scoped>
.auth-error {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
  background: #f8d7da;
  border: 2px solid #dc3545;
  border-radius: 10px;
  padding: 40px;
}

.error-message {
  text-align: center;
  color: #721c24;
}

.btn-login {
  background: #dc3545;
  color: white;
  padding: 12px 24px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
  margin-top: 15px;
}

.btn-login:hover {
  background: #c82333;
}
.seka-game {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
  background: #f8f9fa;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

@media (max-width: 768px) {
  .seka-game {
    padding: 10px;
    gap: 15px;
  }
}
</style>