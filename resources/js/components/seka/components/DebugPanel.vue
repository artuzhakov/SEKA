<template>
  <div class="debug-panel" v-if="gameState">
    <h4>🔧 Отладка</h4>
    
    <div class="debug-grid">
      <!-- Основная информация -->
      <div class="debug-section">
        <h5>Состояние игры</h5>
        <div class="debug-item">
          <span>Банк:</span>
          <strong>{{ gameState.pot }}🪙</strong>
        </div>
        <div class="debug-item">
          <span>Раунд:</span>
          <strong>{{ gameState.currentRound }}</strong>
        </div>
        <div class="debug-item">
          <span>Текущий игрок:</span>
          <strong>{{ gameState.currentPlayerId }}</strong>
        </div>
        <div class="debug-item">
          <span>Дилер:</span>
          <strong>{{ gameState.dealerId }}</strong>
        </div>
        <div class="debug-item">
          <span>Базовая ставка:</span>
          <strong>{{ gameState.baseBet }}🪙</strong>
        </div>
      </div>

      <!-- Тестовые действия -->
      <div class="debug-section">
        <h5>Тестовые действия</h5>
        <div class="test-actions">
          <button @click="forceAction('check')" class="test-btn">⏭️ Check</button>
          <button @click="forceAction('call')" class="test-btn">📞 Call</button>
          <button @click="forceAction('raise')" class="test-btn">📈 Raise</button>
          <button @click="forceAction('fold')" class="test-btn">❌ Fold</button>
          <button @click="forceAction('dark')" class="test-btn">🌙 Dark</button>
          <button @click="forceAction('open')" class="test-btn">👀 Open</button>
        </div>
      </div>

      <!-- Информация о подключении -->
      <div class="debug-section">
        <h5>Подключение</h5>
        <div class="debug-item">
          <span>Pusher:</span>
          <span class="status connected">✅ Подключен</span>
        </div>
        <div class="debug-item">
          <span>WebSocket:</span>
          <span class="status connected">✅ Активен</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  gameState: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['test-action'])

const forceAction = (action) => {
  console.log('🔧 Тестовое действие:', action)
  emit('test-action', action)
}
</script>

<style scoped>
.debug-panel {
  background: rgba(45, 55, 72, 0.9);
  border: 2px solid #4a5568;
  border-radius: 10px;
  padding: 15px;
  margin-top: 20px;
  color: white;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.debug-panel h4 {
  margin: 0 0 15px 0;
  color: #68d391;
  font-size: 1.1rem;
  border-bottom: 1px solid #4a5568;
  padding-bottom: 8px;
}

.debug-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 15px;
}

.debug-section {
  background: rgba(74, 85, 104, 0.5);
  border-radius: 8px;
  padding: 12px;
}

.debug-section h5 {
  margin: 0 0 10px 0;
  font-size: 0.9rem;
  color: #a0aec0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.debug-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
  font-size: 0.85rem;
}

.debug-item:last-child {
  margin-bottom: 0;
}

.debug-item span {
  color: #cbd5e0;
}

.debug-item strong {
  color: #e2e8f0;
}

.status.connected {
  color: #68d391;
  font-weight: bold;
}

.status.disconnected {
  color: #fc8181;
  font-weight: bold;
}

/* Тестовые кнопки */
.test-actions {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 6px;
}

.test-btn {
  padding: 6px 8px;
  background: #4a5568;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 0.75rem;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 4px;
  justify-content: center;
}

.test-btn:hover {
  background: #718096;
  transform: translateY(-1px);
}

.test-btn:active {
  transform: translateY(0);
}

/* Адаптивность */
@media (max-width: 768px) {
  .debug-grid {
    grid-template-columns: 1fr;
  }
  
  .test-actions {
    grid-template-columns: repeat(3, 1fr);
  }
  
  .debug-panel {
    padding: 12px;
  }
}

@media (max-width: 480px) {
  .test-actions {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .test-btn {
    font-size: 0.7rem;
    padding: 5px 6px;
  }
}
</style>