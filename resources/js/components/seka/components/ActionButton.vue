<template>
  <button
    :class="buttonClasses"
    @click="$emit('action-clicked', action)"
    :disabled="disabled"
  >
    <!-- Иконка действия -->
    <div class="action-icon">
      {{ getActionIcon(action) }}
    </div>

    <!-- Текст действия -->
    <div class="action-content">
      <div class="action-title">{{ getActionText(action) }}</div>
      <div class="action-description" v-if="description">{{ description }}</div>
      <div class="action-amount" v-if="amount !== null && amount > 0">
        {{ amount }}🪙
      </div>
    </div>

    <!-- Индикатор подсветки -->
    <div class="highlight-indicator" v-if="isHighlight"></div>

    <!-- Индикатор отключения -->
    <div class="disabled-overlay" v-if="disabled">
      <div class="disabled-icon">🚫</div>
    </div>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  action: {
    type: String,
    required: true
  },
  amount: {
    type: Number,
    default: null
  },
  disabled: {
    type: Boolean,
    default: false
  },
  isHighlight: {
    type: Boolean,
    default: false
  }
})

defineEmits(['action-clicked'])

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
const description = computed(() => {
  const descriptions = {
    check: 'Пропустить ход',
    call: 'Поддержать ставку',
    raise: 'Повысить ставку', 
    fold: 'Сбросить карты',
    dark: 'Играть в темную',
    reveal: 'Вскрыть карты',
    open: 'Открыть карты'
  }
  return descriptions[props.action] || ''
})

const buttonClasses = computed(() => [
  'action-button',
  `action-${props.action}`,
  {
    'disabled': props.disabled,
    'highlighted': props.isHighlight,
    'has-amount': props.amount !== null && props.amount > 0
  }
])

// 🎯 МЕТОДЫ
const getActionIcon = (action) => {
  const icons = {
    check: '⏭️',
    call: '📞',
    raise: '📈',
    fold: '❌',
    dark: '🌙', 
    reveal: '🔓',
    open: '👀'
  }
  return icons[action] || '🎯'
}

const getActionText = (action) => {
  const texts = {
    check: 'Пропуск',
    call: 'Поддержка',
    raise: 'Повышение',
    fold: 'Пас',
    dark: 'Темная',
    reveal: 'Вскрытие',
    open: 'Открыть'
  }
  return texts[action] || action
}
</script>

<style scoped>
.action-button {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  background: rgba(74, 85, 104, 0.8);
  color: white;
  border: 2px solid transparent;
  min-height: 60px;
  width: 100%;
  text-align: left;
  overflow: hidden;
}

.action-button:hover:not(.disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
  border-color: rgba(255, 255, 255, 0.2);
}

.action-button:active:not(.disabled) {
  transform: translateY(0);
}

/* Иконка действия */
.action-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  transition: all 0.3s ease;
}

.action-button:hover:not(.disabled) .action-icon {
  transform: scale(1.1);
  background: rgba(255, 255, 255, 0.2);
}

/* Контент действия */
.action-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.action-title {
  font-size: 0.95rem;
  font-weight: bold;
  color: #e2e8f0;
}

.action-description {
  font-size: 0.75rem;
  color: #a0aec0;
  line-height: 1.2;
}

.action-amount {
  font-size: 0.8rem;
  font-weight: bold;
  color: #f6e05e;
  background: rgba(246, 224, 94, 0.2);
  padding: 2px 6px;
  border-radius: 6px;
  align-self: flex-start;
  margin-top: 2px;
}

/* Цвета для разных действий */
.action-check {
  background: rgba(66, 153, 225, 0.8);
}
.action-check:hover:not(.disabled) {
  background: rgba(66, 153, 225, 1);
}

.action-call {
  background: rgba(237, 137, 54, 0.8);
}
.action-call:hover:not(.disabled) {
  background: rgba(237, 137, 54, 1);
}

.action-raise {
  background: rgba(72, 187, 120, 0.8);
}
.action-raise:hover:not(.disabled) {
  background: rgba(72, 187, 120, 1);
}

.action-fold {
  background: rgba(229, 62, 62, 0.8);
}
.action-fold:hover:not(.disabled) {
  background: rgba(229, 62, 62, 1);
}

.action-dark {
  background: rgba(128, 90, 213, 0.8);
}
.action-dark:hover:not(.disabled) {
  background: rgba(128, 90, 213, 1);
}

.action-reveal {
  background: rgba(214, 158, 46, 0.8);
}
.action-reveal:hover:not(.disabled) {
  background: rgba(214, 158, 46, 1);
}

.action-open {
  background: rgba(56, 161, 105, 0.8);
}
.action-open:hover:not(.disabled) {
  background: rgba(56, 161, 105, 1);
}

/* Подсветка рекомендуемых действий */
.action-button.highlighted {
  border-color: #f6e05e;
  box-shadow: 0 0 0 2px rgba(246, 224, 94, 0.3);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 2px rgba(246, 224, 94, 0.3); }
  50% { box-shadow: 0 0 0 4px rgba(246, 224, 94, 0.5); }
}

.highlight-indicator {
  position: absolute;
  top: 5px;
  right: 5px;
  width: 8px;
  height: 8px;
  background: #f6e05e;
  border-radius: 50%;
  animation: blink 1.5s infinite;
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

/* Состояние отключения */
.action-button.disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
}

.disabled-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
}

.disabled-icon {
  font-size: 1.2rem;
  opacity: 0.8;
}

/* Адаптивность */
@media (max-width: 768px) {
  .action-button {
    padding: 10px 12px;
    min-height: 55px;
    gap: 10px;
  }
  
  .action-icon {
    font-size: 1.3rem;
    width: 35px;
    height: 35px;
  }
  
  .action-title {
    font-size: 0.9rem;
  }
  
  .action-description {
    font-size: 0.7rem;
  }
}

@media (max-width: 480px) {
  .action-button {
    padding: 8px 10px;
    min-height: 50px;
    gap: 8px;
  }
  
  .action-icon {
    font-size: 1.1rem;
    width: 30px;
    height: 30px;
  }
  
  .action-content {
    gap: 2px;
  }
  
  .action-title {
    font-size: 0.85rem;
  }
  
  .action-description {
    font-size: 0.65rem;
  }
}

/* Плавные переходы */
.action-button {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.action-button * {
  transition: all 0.2s ease;
}
</style>