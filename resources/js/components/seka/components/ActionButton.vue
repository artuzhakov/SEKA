<template>
  <button
    :class="['btn-action', `btn-${action}`]"
    @click="$emit('click', action)"
  >
    <span class="action-icon">{{ getActionIcon(action) }}</span>
    <span class="action-text">{{ getActionText(action) }}</span>
    <small class="action-description">{{ getActionDescription(action) }}</small>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  action: {
    type: String,
    required: true
  },
  currentPlayerInfo: {
    type: Object,
    default: null
  },
  currentMaxBet: {
    type: Number,
    default: 0
  }
})

defineEmits(['click'])

const callAmount = computed(() => {
  const playerBet = props.currentPlayerInfo?.current_bet || 0
  return Math.max(0, props.currentMaxBet - playerBet)
})

const getActionIcon = (action) => {
  const icons = {
    check: '✓',
    call: '📞',
    raise: '📈',
    fold: '❌',
    dark: '🌙',
    reveal: '🃏',
    open: '👀'
  }
  return icons[action] || '❓'
}

const getActionText = (action) => {
  const texts = {
    check: 'ЧЕК',
    call: 'КОЛЛ',
    raise: 'РЕЙЗ',
    fold: 'ФОЛД',
    dark: 'ТЕМНАЯ',
    reveal: 'ВСКРЫТИЕ',
    open: 'ОТКРЫТЬ'
  }
  return texts[action] || action.toUpperCase()
}

const getActionDescription = (action) => {
  const descriptions = {
    check: 'Пропустить (нет ставок)',
    call: `Уравнять ${callAmount.value}`,
    raise: 'Повысить ставку',
    fold: 'Сбросить карты',
    dark: 'Играть вслепую (скидка 50%)',
    reveal: '2x ставка vs предыдущий',
    open: 'Показать карты (после темной)'
  }
  return descriptions[action] || ''
}
</script>

<style scoped>
.btn-action {
  padding: 15px 10px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  color: white;
}

.btn-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.btn-check { background: #17a2b8; }
.btn-call { background: #28a745; }
.btn-raise { background: #ffc107; color: black; }
.btn-fold { background: #dc3545; }
.btn-dark { background: #343a40; }
.btn-reveal { background: #6f42c1; }
.btn-open { background: #fd7e14; }

.action-icon {
  font-size: 18px;
}

.action-text {
  font-size: 14px;
}

.action-description {
  font-size: 11px;
  opacity: 0.9;
}
</style>