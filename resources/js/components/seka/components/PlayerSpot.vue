<template>
  <div class="player-spot" :class="spotClasses">
    <!-- Информация об игроке -->
    <div class="player-info">
      <div class="player-main">
        <span class="player-name">{{ displayName }}</span>
        <span class="player-balance">{{ player.balance }}🪙</span>
      </div>
      
      <div class="player-stats">
        <span class="player-bet" v-if="player.currentBet > 0">
          Ставка: {{ player.currentBet }}🪙
        </span>
        <span class="last-action" v-if="player.lastAction">
          {{ getActionIcon(player.lastAction) }}
        </span>
      </div>
    </div>

    <!-- Карты игрока -->
    <div class="cards-container" :class="{ 'dark-mode': player.isDark }">
      <div 
        v-for="(card, index) in displayedCards" 
        :key="`card-${index}`"
        class="card-wrapper"
        :style="getCardStyle(index)"
      >
        <!-- Карта с анимацией -->
        <div 
          class="playing-card"
          :class="{
            'hidden': !isCardVisible(card),
            'folded': player.isFolded,
            'dark': player.isDark,
            'animated': shouldAnimate
          }"
          @click="handleCardClick"
        >
          <!-- Лицевая сторона карты -->
          <div class="card-front" v-if="isCardVisible(card)">
            <div class="card-corner top-left">
              <div class="card-rank">{{ card.rank }}</div>
              <div class="card-suit">{{ card.suit }}</div>
            </div>
            <div class="card-center">
              <div class="card-suit-large">{{ card.suit }}</div>
              <div class="card-joker" v-if="card.isJoker">🎭</div>
            </div>
            <div class="card-corner bottom-right">
              <div class="card-rank">{{ card.rank }}</div>
              <div class="card-suit">{{ card.suit }}</div>
            </div>
          </div>
          
          <!-- Рубашка карты -->
          <div class="card-back" v-else>
            <div class="card-pattern"></div>
            <div class="card-logo">SEKA</div>
          </div>
        </div>
      </div>
      
      <!-- Сообщение если нет карт -->
      <div v-if="!displayedCards || displayedCards.length === 0" class="no-cards">
        <div class="empty-slot" v-if="!player.id">+</div>
        <div v-else class="waiting-cards">🃏</div>
      </div>
    </div>

    <!-- Статусы игрока -->
    <div class="status-indicators">
      <span v-if="player.isFolded" class="status folded">❌ ПАС</span>
      <span v-else-if="player.isDark" class="status dark">🌙 ТЕМНАЯ</span>
      <span v-else-if="isCurrentTurn" class="status current">🎯 ХОДИТ</span>
      <span v-if="isDealer" class="status dealer">🎫 ДИЛЕР</span>
    </div>

    <!-- Всплывающая панель действий (для текущего игрока) -->
    <ActionPanel 
      v-if="showActionPanel"
      :player="player"
      :available-actions="availableActions"
      @action="handleAction"
      class="floating-actions"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import ActionPanel from './ActionPanel.vue'

const props = defineProps({
  player: {
    type: Object,
    required: true
  },
  cards: {
    type: Array,
    default: () => []
  },
  isCurrentTurn: {
    type: Boolean,
    default: false
  },
  isDealer: {
    type: Boolean,
    default: false
  },
  showAllCards: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['player-action'])

// 🎯 РЕАКТИВНОЕ СОСТОЯНИЕ
const shouldAnimate = ref(false)

// 🎯 ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
const displayName = computed(() => {
  return props.player.name || `Игрок ${props.player.position}`
})

const displayedCards = computed(() => {
  return props.cards || []
})

const spotClasses = computed(() => ({
  'current-turn': props.isCurrentTurn,
  'dealer': props.isDealer,
  'folded': props.player.isFolded,
  'dark': props.player.isDark,
  'empty': !props.player.id,
  'occupied': !!props.player.id
}))

const showActionPanel = computed(() => {
  return props.isCurrentTurn && props.player.id === 1 // Только для текущего пользователя
})

const availableActions = computed(() => {
  // Здесь будет логика доступных действий
  return ['check', 'call', 'raise', 'fold', 'dark', 'reveal', 'open']
})

// 🎯 МЕТОДЫ
const isCardVisible = (card) => {
  if (!card) return false
  if (props.showAllCards) return true
  if (props.player.id === 1) return true // Текущий игрок всегда видит свои карты
  return card.isVisible && !props.player.isDark
}

const getCardStyle = (index) => {
  // Небольшое смещение карт для красивого отображения
  return {
    transform: `translateX(${(index - 1) * 5}px)`
  }
}

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

const handleCardClick = () => {
  // Можно добавить функционал просмотра карт
  if (props.player.id === 1 && !props.player.isDark) {
    console.log('Просмотр карт игрока')
  }
}

const handleAction = (action) => {
  emit('player-action', action)
}

// 🎯 АНИМАЦИИ
const startAnimation = () => {
  shouldAnimate.value = true
  setTimeout(() => {
    shouldAnimate.value = false
  }, 1000)
}

// Запускаем анимацию при получении карт
if (displayedCards.value.length > 0) {
  startAnimation()
}
</script>

<style scoped>
.player-spot {
  position: relative;
  min-width: 200px;
  padding: 15px;
  border-radius: 15px;
  background: rgba(45, 55, 72, 0.9);
  border: 2px solid #4a5568;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

/* Состояния спота */
.player-spot.current-turn {
  border-color: #48bb78;
  background: rgba(72, 187, 120, 0.2);
  box-shadow: 0 0 20px rgba(72, 187, 120, 0.4);
}

.player-spot.dealer {
  border-color: #d69e2e;
}

.player-spot.folded {
  opacity: 0.6;
  background: rgba(229, 62, 62, 0.1);
}

.player-spot.dark {
  border-color: #805ad5;
  background: rgba(128, 90, 213, 0.2);
}

.player-spot.empty {
  background: rgba(74, 85, 104, 0.3);
  border: 2px dashed #718096;
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Информация об игроке */
.player-info {
  margin-bottom: 15px;
}

.player-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 5px;
}

.player-name {
  font-weight: bold;
  font-size: 14px;
  color: #e2e8f0;
}

.player-balance {
  font-size: 12px;
  color: #f6e05e;
  background: rgba(246, 224, 94, 0.2);
  padding: 2px 6px;
  border-radius: 8px;
}

.player-stats {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
}

.player-bet {
  color: #68d391;
  font-weight: bold;
}

.last-action {
  font-size: 16px;
}

/* Контейнер карт */
.cards-container {
  display: flex;
  justify-content: center;
  gap: 5px;
  margin-bottom: 10px;
  min-height: 100px;
  align-items: center;
  position: relative;
}

.cards-container.dark-mode {
  opacity: 0.8;
}

.card-wrapper {
  transition: transform 0.3s ease;
}

/* Игровая карта */
.playing-card {
  width: 70px;
  height: 95px;
  border-radius: 8px;
  position: relative;
  perspective: 1000px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.playing-card.animated {
  animation: dealCard 0.6s ease-out;
}

.playing-card.folded {
  opacity: 0.5;
  filter: grayscale(1);
}

.playing-card:hover {
  transform: translateY(-5px);
}

/* Лицевая сторона карты */
.card-front {
  width: 100%;
  height: 100%;
  background: white;
  border-radius: 8px;
  border: 2px solid #e2e8f0;
  position: relative;
  color: #1a202c;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.card-corner {
  display: flex;
  flex-direction: column;
  align-items: center;
  font-size: 12px;
  font-weight: bold;
}

.top-left {
  align-self: flex-start;
}

.bottom-right {
  align-self: flex-end;
  transform: rotate(180deg);
}

.card-center {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
}

.card-suit-large {
  font-size: 24px;
}

.card-joker {
  font-size: 20px;
}

/* Рубашка карты */
.card-back {
  width: 100%;
  height: 100%;
  background: linear-gradient(45deg, #1a202c, #4a5568);
  border-radius: 8px;
  border: 2px solid #e2e8f0;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.card-pattern {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: 
    radial-gradient(circle at 30% 30%, rgba(255,255,255,0.1) 2%, transparent 2.5%),
    radial-gradient(circle at 70% 70%, rgba(255,255,255,0.1) 2%, transparent 2.5%);
  border-radius: 6px;
}

.card-logo {
  color: rgba(255, 255, 255, 0.8);
  font-size: 12px;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Сообщения */
.no-cards {
  color: rgba(255, 255, 255, 0.6);
  font-style: italic;
  text-align: center;
}

.empty-slot {
  font-size: 3rem;
  color: #718096;
}

.waiting-cards {
  font-size: 2rem;
  opacity: 0.7;
}

/* Статусы */
.status-indicators {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
  justify-content: center;
}

.status {
  font-size: 11px;
  padding: 3px 6px;
  border-radius: 8px;
  font-weight: bold;
  text-transform: uppercase;
}

.status.folded {
  background: #e53e3e;
  color: white;
}

.status.dark {
  background: #805ad5;
  color: white;
}

.status.current {
  background: #48bb78;
  color: white;
}

.status.dealer {
  background: #d69e2e;
  color: white;
}

/* Всплывающая панель действий */
.floating-actions {
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  z-index: 100;
  margin-top: 10px;
}

/* Анимации */
@keyframes dealCard {
  0% {
    transform: scale(0) rotate(-180deg);
    opacity: 0;
  }
  50% {
    transform: scale(1.1) rotate(-90deg);
    opacity: 0.7;
  }
  100% {
    transform: scale(1) rotate(0deg);
    opacity: 1;
  }
}

/* Адаптивность */
@media (max-width: 768px) {
  .player-spot {
    min-width: 160px;
    padding: 10px;
  }
  
  .playing-card {
    width: 50px;
    height: 70px;
  }
  
  .card-corner {
    font-size: 10px;
  }
  
  .card-suit-large {
    font-size: 18px;
  }
  
  .player-name {
    font-size: 12px;
  }
  
  .status {
    font-size: 10px;
    padding: 2px 4px;
  }
}
</style>