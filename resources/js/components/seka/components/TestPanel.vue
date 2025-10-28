<template>
  <div class="test-panel">
    <h3>🧪 Тестирование</h3>
    
    <div class="test-buttons">
      <button @click="runQuickTest" class="test-btn quick">
        Быстрый тест
      </button>
      <button @click="runComprehensiveTest" class="test-btn comprehensive">
        Полный тест
      </button>
      <button @click="resetGame" class="test-btn reset">
        Сбросить игру
      </button>
    </div>
    
    <div class="test-scenarios">
      <h4>Сценарии:</h4>
      <div class="scenarios-list">
        <button 
          v-for="scenario in testScenarios"
          :key="scenario.id"
          @click="runScenario(scenario)"
          class="scenario-btn"
        >
          {{ scenario.name }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useNotifications } from '@/composables/useNotifications'

const { showNotification } = useNotifications()

const testScenarios = ref([
  { id: 1, name: 'Все игроки чекают' },
  { id: 2, name: 'Рейз и коллы' },
  { id: 3, name: 'Темная игра' },
  { id: 4, name: 'Фолд всех игроков' },
  { id: 5, name: 'Вскрытие карт' }
])

const runQuickTest = () => {
  showNotification('Быстрый тест запущен', 'info')
  setTimeout(() => {
    showNotification('Быстрый тест завершен ✅', 'success')
  }, 1000)
}

const runComprehensiveTest = () => {
  showNotification('Полный тест запущен', 'info')
  setTimeout(() => {
    showNotification('Полный тест завершен ✅', 'success')
  }, 3000)
}

const resetGame = () => {
  showNotification('Игра сброшена', 'warning')
  // Здесь будет логика сброса игры
}

const runScenario = (scenario) => {
  showNotification(`Запуск сценария: ${scenario.name}`, 'info')
  // Здесь будет логика конкретного сценария
}
</script>

<style scoped>
.test-panel {
  background: white;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.test-buttons {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.test-btn {
  padding: 10px 15px;
  border: none;
  border-radius: 6px;
  font-weight: bold;
  cursor: pointer;
  flex: 1;
}

.test-btn.quick {
  background: #3b82f6;
  color: white;
}

.test-btn.comprehensive {
  background: #8b5cf6;
  color: white;
}

.test-btn.reset {
  background: #ef4444;
  color: white;
}

.test-scenarios h4 {
  margin-bottom: 10px;
  color: #374151;
}

.scenarios-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 8px;
}

.scenario-btn {
  padding: 8px 12px;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.2s;
}

.scenario-btn:hover {
  background: #e5e7eb;
  border-color: #9ca3af;
}
</style>