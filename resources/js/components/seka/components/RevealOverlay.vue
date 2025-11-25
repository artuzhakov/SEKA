<!-- components/RevealOverlay.vue -->
<template>
  <div v-if="revealState.isActive" class="reveal-overlay" :class="revealClasses">
    <div class="reveal-content">
      <!-- АНИМАЦИЯ REVEAL -->
      <div class="reveal-animation">
        <div class="cards-reveal">
          <div class="card-flip" v-for="player in participants" :key="player.id">
            <div class="card-container">
              <div class="card-inner" :class="{ flipped: cardsFlipped }">
                <div class="card-front">
                  <div class="player-name">{{ player.name }}</div>
                  <div class="player-cards">
                    <div v-for="(card, index) in player.cards" :key="index" class="reveal-card">
                      <div class="card-rank">{{ card.rank }}</div>
                      <div class="card-suit">{{ getSuitSymbol(card.suit) }}</div>
                    </div>
                  </div>
                  <div v-if="player.points" class="player-points">
                    {{ player.points }} очков
                  </div>
                </div>
                <div class="card-back"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- РЕЗУЛЬТАТ REVEAL -->
      <div v-if="revealState.resolved" class="reveal-result">
        <div class="result-title">
          <span v-if="revealState.winnerId">🎉 ПОБЕДА!</span>
          <span v-else>🤝 НИЧЬЯ!</span>
        </div>
        
        <div class="participants-result">
          <div v-for="player in participants" :key="player.id" 
               class="participant-result" 
               :class="{ winner: player.id === revealState.winnerId, loser: player.id === revealState.loserId }">
            <div class="participant-name">{{ player.name }}</div>
            <div class="participant-points">{{ player.points || 0 }} очков</div>
            <div v-if="player.id === revealState.winnerId" class="result-badge winner-badge">🏆 ПОБЕДИТЕЛЬ</div>
            <div v-else-if="player.id === revealState.loserId" class="result-badge loser-badge">💀 ВЫБЫЛ</div>
          </div>
        </div>

        <div class="reveal-timer">
          Продолжение через: {{ revealTimeLeft }}с
        </div>
      </div>

      <!-- ПРОЦЕСС REVEAL -->
      <div v-else class="reveal-in-progress">
        <div class="reveal-title">🔍 ВСКРЫТИЕ КАРТ</div>
        <div class="reveal-participants">
          <div v-for="player in participants" :key="player.id" class="reveal-participant">
            <div class="participant-avatar">{{ getInitials(player.name) }}</div>
            <div class="participant-name">{{ player.name }}</div>
          </div>
        </div>
        <div class="reveal-loading">
          <div class="loading-spinner"></div>
          <div>Сравнение комбинаций...</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  revealState: {
    type: Object,
    required: true
  },
  players: {
    type: Array,
    default: () => []
  },
  revealTimeLeft: {
    type: Number,
    default: 0
  }
})

// 🎯 АНИМАЦИЯ ПЕРЕВОРОТА КАРТ
const cardsFlipped = ref(false)

// 🎯 УЧАСТНИКИ REVEAL
const participants = computed(() => {
  return props.revealState.participants
    .map(playerId => props.players.find(p => p.id === playerId))
    .filter(Boolean)
})

// 🎯 КЛАССЫ ДЛЯ АНИМАЦИИ
const revealClasses = computed(() => ({
  'resolved': props.revealState.resolved,
  'in-progress': !props.revealState.resolved
}))

// 🎯 ЗАПУСК АНИМАЦИИ ПРИ АКТИВАЦИИ REVEAL
watch(() => props.revealState.isActive, (isActive) => {
  if (isActive) {
    // Задержка перед переворотом карт
    setTimeout(() => {
      cardsFlipped.value = true
    }, 1000)
  } else {
    cardsFlipped.value = false
  }
})

// 🎯 ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
const getSuitSymbol = (suit) => {
  const symbols = {
    'hearts': '♥',
    'diamonds': '♦', 
    'clubs': '♣',
    'spades': '♠',
    '♥': '♥',
    '♦': '♦',
    '♣': '♣',
    '♠': '♠'
  }
  return symbols[suit] || suit
}

const getInitials = (name) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}
</script>

<style scoped>
.reveal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  animation: fadeIn 0.3s ease;
}

.reveal-content {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  border: 3px solid #fbbf24;
  border-radius: 20px;
  padding: 30px;
  max-width: 600px;
  width: 90%;
  color: white;
  text-align: center;
  animation: scaleIn 0.5s ease;
}

/* АНИМАЦИЯ REVEAL КАРТ */
.cards-reveal {
  display: flex;
  justify-content: center;
  gap: 40px;
  margin: 30px 0;
}

.card-flip {
  perspective: 1000px;
}

.card-container {
  width: 120px;
  height: 160px;
  position: relative;
}

.card-inner {
  width: 100%;
  height: 100%;
  position: relative;
  transform-style: preserve-3d;
  transition: transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.card-inner.flipped {
  transform: rotateY(180deg);
}

.card-front,
.card-back {
  position: absolute;
  width: 100%;
  height: 100%;
  backface-visibility: hidden;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 10px;
}

.card-front {
  background: white;
  color: #1a202c;
  transform: rotateY(180deg);
}

.card-back {
  background: linear-gradient(45deg, #1e40af, #3b82f6);
  border: 2px solid white;
}

.player-name {
  font-size: 0.8rem;
  font-weight: bold;
  margin-bottom: 8px;
}

.player-cards {
  display: flex;
  gap: 4px;
  margin-bottom: 8px;
}

.reveal-card {
  width: 25px;
  height: 35px;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: bold;
}

.player-points {
  font-size: 0.7rem;
  font-weight: bold;
  color: #059669;
}

/* РЕЗУЛЬТАТ REVEAL */
.reveal-result {
  animation: slideUp 0.5s ease;
}

.result-title {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 20px;
  color: #fbbf24;
}

.participants-result {
  display: flex;
  justify-content: center;
  gap: 30px;
  margin: 20px 0;
}

.participant-result {
  padding: 15px;
  border-radius: 10px;
  min-width: 150px;
  transition: all 0.3s ease;
}

.participant-result.winner {
  background: rgba(34, 197, 94, 0.2);
  border: 2px solid #22c55e;
  transform: scale(1.05);
}

.participant-result.loser {
  background: rgba(239, 68, 68, 0.2);
  border: 2px solid #ef4444;
  opacity: 0.8;
}

.participant-name {
  font-weight: bold;
  margin-bottom: 5px;
}

.participant-points {
  font-size: 1.2rem;
  font-weight: bold;
  color: #fbbf24;
  margin-bottom: 8px;
}

.result-badge {
  font-size: 0.8rem;
  font-weight: bold;
  padding: 4px 8px;
  border-radius: 12px;
}

.winner-badge {
  background: #22c55e;
  color: white;
}

.loser-badge {
  background: #ef4444;
  color: white;
}

.reveal-timer {
  margin-top: 15px;
  font-size: 1rem;
  color: #d1d5db;
}

/* ПРОЦЕСС REVEAL */
.reveal-in-progress {
  animation: pulse 2s infinite;
}

.reveal-title {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 20px;
  color: #fbbf24;
}

.reveal-participants {
  display: flex;
  justify-content: center;
  gap: 40px;
  margin: 20px 0;
}

.reveal-participant {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.participant-avatar {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  color: white;
}

.participant-name {
  font-weight: bold;
}

.reveal-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  margin-top: 20px;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #374151;
  border-top: 4px solid #fbbf24;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

/* АНИМАЦИИ */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes scaleIn {
  from { 
    opacity: 0;
    transform: scale(0.8);
  }
  to { 
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* АДАПТИВНОСТЬ */
@media (max-width: 768px) {
  .reveal-content {
    padding: 20px;
    margin: 20px;
  }
  
  .cards-reveal {
    gap: 20px;
  }
  
  .card-container {
    width: 80px;
    height: 120px;
  }
  
  .participants-result {
    flex-direction: column;
    gap: 15px;
  }
  
  .reveal-participants {
    flex-direction: column;
    gap: 20px;
  }
}
</style>