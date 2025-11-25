// useGameTimers.js - АДАПТИРОВАННЫЙ ПОД РЕАЛЬНЫЙ API
import { ref, computed, onUnmounted } from 'vue'

export function useGameTimers() {
  const turnTimeLeft = ref(0)
  const readyTimeLeft = ref(0)
  const revealTimeLeft = ref(0)

  const visualTurnTimer = ref(0)
  const visualReadyTimer = ref(0)
  const visualRevealTimer = ref(0)

  let turnInterval = null
  let readyInterval = null
  let revealInterval = null

  const turnProgress = computed(() => {
    if (turnTimeLeft.value <= 0) return 0
    return (visualTurnTimer.value / turnTimeLeft.value) * 100
  })

  const readyProgress = computed(() => {
    if (readyTimeLeft.value <= 0) return 0
    return (visualReadyTimer.value / readyTimeLeft.value) * 100
  })

  const revealProgress = computed(() => {
    if (revealTimeLeft.value <= 0) return 0
    return (visualRevealTimer.value / revealTimeLeft.value) * 100
  })

  const isTurnTimeCritical = computed(() => visualTurnTimer.value <= 10 && visualTurnTimer.value > 0)
  const isReadyTimeCritical = computed(() => visualReadyTimer.value <= 5 && visualReadyTimer.value > 0)
  const isRevealTimeCritical = computed(() => visualRevealTimer.value <= 5 && visualRevealTimer.value > 0)

  // 🎯 АДАПТАЦИЯ ПОД РЕАЛЬНЫЕ TIMERS ИЗ БЭКА
  const syncTimersFromBackend = (backendTimers, gamePhase = null) => {
    if (!backendTimers) {
      resetAllTimers()
      return
    }

    console.log('🔄 Syncing timers from backend:', backendTimers, 'phase=', gamePhase)

    const {
      turn_timeout,
      ready_timeout,
      action_timeout,
      current_turn_started_at
    } = backendTimers

    // ⏱ TURN таймер считаем по текущему времени и времени старта
    if (turn_timeout && current_turn_started_at) {
      const startedAtMs = Date.parse(current_turn_started_at)
      const nowMs = Date.now()
      const elapsed = Math.max(Math.floor((nowMs - startedAtMs) / 1000), 0)
      const left = Math.max(turn_timeout - elapsed, 0)

      startTurnTimer(left)
    } else {
      clearTimer('turn')
      turnTimeLeft.value = 0
      visualTurnTimer.value = 0
    }

    // ⏱ READY таймер — при фазе "waiting_for_players" показываем полный ready_timeout
    if (typeof ready_timeout === 'number') {
      if (gamePhase === 'waiting_for_players' || gamePhase === 'waiting') {
        startReadyTimer(ready_timeout)
      } else {
        clearTimer('ready')
        readyTimeLeft.value = 0
        visualReadyTimer.value = 0
      }
    }

    // ⏱ REVEAL/Action: пока не активируем автоматически
    if (typeof action_timeout === 'number') {
      clearTimer('reveal')
      revealTimeLeft.value = 0
      visualRevealTimer.value = 0
    }
  }

  const startTurnTimer = (seconds) => {
    clearTimer('turn')

    if (!seconds || seconds <= 0) {
      visualTurnTimer.value = 0
      turnTimeLeft.value = 0
      return
    }

    turnTimeLeft.value = seconds
    visualTurnTimer.value = seconds

    turnInterval = setInterval(() => {
      if (visualTurnTimer.value > 0) {
        visualTurnTimer.value--

        if (visualTurnTimer.value === 10) {
          console.log('⚠️ Turn time critical: 10s left')
        }

        if (visualTurnTimer.value <= 0) {
          console.log('⏰ Turn time expired')
          clearTimer('turn')
        }
      } else {
        clearTimer('turn')
      }
    }, 1000)
  }

  const startReadyTimer = (seconds) => {
    clearTimer('ready')

    if (!seconds || seconds <= 0) {
      visualReadyTimer.value = 0
      readyTimeLeft.value = 0
      return
    }

    readyTimeLeft.value = seconds
    visualReadyTimer.value = seconds

    readyInterval = setInterval(() => {
      if (visualReadyTimer.value > 0) {
        visualReadyTimer.value--
        if (visualReadyTimer.value <= 0) {
          console.log('⏰ Ready time expired')
          clearTimer('ready')
        }
      } else {
        clearTimer('ready')
      }
    }, 1000)
  }

  const startRevealTimer = (seconds) => {
    clearTimer('reveal')

    if (!seconds || seconds <= 0) {
      visualRevealTimer.value = 0
      revealTimeLeft.value = 0
      return
    }

    revealTimeLeft.value = seconds
    visualRevealTimer.value = seconds

    revealInterval = setInterval(() => {
      if (visualRevealTimer.value > 0) {
        visualRevealTimer.value--
        if (visualRevealTimer.value <= 0) {
          console.log('⏰ Reveal time expired')
          clearTimer('reveal')
        }
      } else {
        clearTimer('reveal')
      }
    }, 1000)
  }

  const clearTimer = (type) => {
    switch (type) {
      case 'turn':
        if (turnInterval) {
          clearInterval(turnInterval)
          turnInterval = null
        }
        break
      case 'ready':
        if (readyInterval) {
          clearInterval(readyInterval)
          readyInterval = null
        }
        break
      case 'reveal':
        if (revealInterval) {
          clearInterval(revealInterval)
          revealInterval = null
        }
        break
      case 'all':
        clearTimer('turn')
        clearTimer('ready')
        clearTimer('reveal')
        break
    }
  }

  const resetAllTimers = () => {
    clearTimer('all')
    turnTimeLeft.value = 0
    readyTimeLeft.value = 0
    revealTimeLeft.value = 0
    visualTurnTimer.value = 0
    visualReadyTimer.value = 0
    visualRevealTimer.value = 0
    console.log('🔄 All timers reset')
  }

  onUnmounted(() => {
    clearTimer('all')
  })

  return {
    // значения для UI
    turnTimeLeft: visualTurnTimer,
    readyTimeLeft: visualReadyTimer,
    revealTimeLeft: visualRevealTimer,

    // прогресс-бары
    turnProgress,
    readyProgress,
    revealProgress,

    // критичность
    isTurnTimeCritical,
    isReadyTimeCritical,
    isRevealTimeCritical,

    // методы
    syncTimersFromBackend,
    startTurnTimer,
    startReadyTimer,
    startRevealTimer,
    clearTimer,
    resetAllTimers
  }
}