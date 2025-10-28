<template>
  <div class="min-h-screen bg-seka-green-primary">
    <!-- Navigation -->
    <nav class="bg-gray-800 border-b border-emerald-500/30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <span class="text-2xl font-bold text-gradient-green">🎴 SEKA</span>
            <div class="ml-6 flex space-x-4">
              <Link href="/dashboard" class="text-emerald-400 font-bold border-b-2 border-emerald-400 px-3 py-2">
                🏠 Главная
              </Link>
              <Link href="/lobby" class="text-gray-300 hover:text-white px-3 py-2 transition-colors">
                🎮 Лобби
              </Link>
              <Link href="/friends" class="text-gray-300 hover:text-white px-3 py-2 transition-colors">
                👥 Друзья
              </Link>
              <Link href="/leaderboard" class="text-gray-300 hover:text-white px-3 py-2 transition-colors">
                🏆 Рейтинг
              </Link>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-3 bg-gray-700/50 rounded-lg px-4 py-2">
              <div class="avatar-seka-green">{{ user.name.charAt(0) }}</div>
              <div class="flex flex-col">
                <span class="text-white font-medium text-sm">{{ user.name }}</span>
                <span class="text-emerald-400 text-xs">⭐ Премиум игрок</span>
              </div>
            </div>
            <button @click="logout" class="btn-seka-danger text-sm">
              🚪 Выйти
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Welcome Header -->
        <div class="text-center mb-12">
          <div class="flex items-center justify-center mb-4">
            <div class="avatar-seka-green-lg text-xl mr-4">
              {{ user.name.charAt(0) }}
            </div>
            <div class="text-left">
              <h1 class="text-4xl font-bold text-gradient-green">
                Добро пожаловать, {{ user.name }}!
              </h1>
              <p class="text-gray-300 mt-1">Ваш игровой профиль SEKA</p>
            </div>
          </div>
          <div class="flex justify-center space-x-2 mt-4">
            <span class="badge-seka-green">🎯 Активный игрок</span>
            <span class="badge-seka-green-light">⭐ Премиум</span>
            <span class="badge-seka-green-light">🏆 Чемпион</span>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="card-seka-green text-center group hover:transform hover:scale-105">
            <div class="text-2xl mb-2">🎮</div>
            <div class="text-3xl font-bold text-emerald-400 mb-1">127</div>
            <div class="text-gray-400 text-sm">Сыграно игр</div>
            <div class="text-emerald-300 text-xs mt-1">+5 за неделю</div>
          </div>
          
          <div class="card-seka-green text-center group hover:transform hover:scale-105">
            <div class="text-2xl mb-2">🏆</div>
            <div class="text-3xl font-bold text-emerald-400 mb-1">68</div>
            <div class="text-gray-400 text-sm">Побед</div>
            <div class="text-emerald-300 text-xs mt-1">53.5% побед</div>
          </div>
          
          <div class="card-seka-green text-center group hover:transform hover:scale-105">
            <div class="text-2xl mb-2">💰</div>
            <div class="text-3xl font-bold text-emerald-400 mb-1">8,450</div>
            <div class="text-gray-400 text-sm">Баланс</div>
            <div class="text-emerald-300 text-xs mt-1">+1,200 за месяц</div>
          </div>
          
          <div class="card-seka-green text-center group hover:transform hover:scale-105">
            <div class="text-2xl mb-2">📈</div>
            <div class="text-3xl font-bold text-emerald-400 mb-1">24</div>
            <div class="text-gray-400 text-sm">Уровень</div>
            <div class="text-emerald-300 text-xs mt-1">Следующий через 156 очков</div>
          </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
          <!-- Left Column -->
          <div class="xl:col-span-2 space-y-8">
            <!-- Quick Actions -->
            <div class="card-seka-green">
              <h2 class="text-xl font-bold text-gradient-green mb-6">🚀 Быстрые действия</h2>
              <div class="grid grid-cols-2 gap-4">
                <button @click="$inertia.visit('/lobby')" class="btn-seka-green py-4 text-center">
                  <div class="text-2xl mb-2">🎮</div>
                  <div>Быстрая игра</div>
                </button>
                <button class="btn-seka-green-secondary py-4 text-center">
                  <div class="text-2xl mb-2">👥</div>
                  <div>С друзьями</div>
                </button>
                <button class="btn-seka-green-secondary py-4 text-center">
                  <div class="text-2xl mb-2">🏆</div>
                  <div>Турнир</div>
                </button>
                <button class="btn-seka-green-secondary py-4 text-center">
                  <div class="text-2xl mb-2">🎁</div>
                  <div>Бонусы</div>
                </button>
              </div>
            </div>

            <!-- Recent Activity -->
            <div class="card-seka-green">
              <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gradient-green">📊 Последние игры</h2>
                <button class="text-emerald-400 hover:text-emerald-300 text-sm">
                  Вся история →
                </button>
              </div>
              <div class="space-y-4">
                <div v-for="game in recentGames" :key="game.id" 
                     class="flex items-center justify-between p-4 bg-gray-700/50 rounded-lg border-l-4"
                     :class="game.won ? 'border-emerald-500' : 'border-red-500'">
                  <div class="flex items-center space-x-4">
                    <div class="text-2xl">{{ game.won ? '🏆' : '💔' }}</div>
                    <div>
                      <div class="font-medium text-white">{{ game.mode }}</div>
                      <div class="text-gray-400 text-sm">{{ game.date }}</div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div :class="game.won ? 'text-emerald-400' : 'text-red-400'" class="font-bold">
                      {{ game.result }}
                    </div>
                    <div class="text-gray-400 text-sm">{{ game.duration }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Achievements -->
            <div class="card-seka-green">
              <h2 class="text-xl font-bold text-gradient-green mb-6">🎯 Достижения</h2>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-for="achievement in achievements" :key="achievement.id"
                     class="text-center p-4 bg-gray-700/30 rounded-lg border border-emerald-500/20">
                  <div class="text-3xl mb-2">{{ achievement.icon }}</div>
                  <div class="font-medium text-white text-sm mb-1">{{ achievement.name }}</div>
                  <div class="text-emerald-400 text-xs">{{ achievement.progress }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="space-y-8">
            <!-- Profile Card -->
            <div class="card-seka-green">
              <div class="text-center mb-6">
                <div class="avatar-seka-green-lg text-xl mx-auto mb-4">
                  {{ user.name.charAt(0) }}
                </div>
                <h3 class="text-xl font-bold text-white">{{ user.name }}</h3>
                <p class="text-emerald-400 text-sm">⭐ Премиум аккаунт</p>
              </div>

              <div class="space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-gray-700">
                  <span class="text-gray-400">Уровень:</span>
                  <span class="text-white font-bold">24</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-700">
                  <span class="text-gray-400">Опыт:</span>
                  <span class="text-emerald-400 font-bold">844/1000</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-700">
                  <span class="text-gray-400">Рейтинг:</span>
                  <span class="text-yellow-400 font-bold">1,856</span>
                </div>
                <div class="flex justify-between items-center py-2">
                  <span class="text-gray-400">В сети:</span>
                  <span class="text-green-400 font-bold">● Онлайн</span>
                </div>
              </div>

              <div class="mt-6 grid grid-cols-2 gap-2">
                <button class="btn-seka-green-secondary py-2 text-sm">
                  ✏️ Профиль
                </button>
                <button class="btn-seka-green-secondary py-2 text-sm">
                  ⚙️ Настройки
                </button>
              </div>
            </div>

            <!-- Friends Online -->
            <div class="card-seka-green">
              <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-gradient-green">👥 Друзья онлайн</h3>
                <span class="text-emerald-400 text-sm">3/12</span>
              </div>
              <div class="space-y-3">
                <div v-for="friend in onlineFriends" :key="friend.id"
                     class="flex items-center justify-between p-3 bg-gray-700/30 rounded-lg">
                  <div class="flex items-center space-x-3">
                    <div class="avatar-seka-green-sm">{{ friend.name.charAt(0) }}</div>
                    <div>
                      <div class="text-white text-sm font-medium">{{ friend.name }}</div>
                      <div class="text-emerald-400 text-xs">В лобби</div>
                    </div>
                  </div>
                  <button class="text-emerald-400 hover:text-emerald-300 text-sm">
                    Присоединиться
                  </button>
                </div>
              </div>
            </div>

            <!-- Daily Bonus -->
            <div class="card-seka-green bg-gradient-to-br from-emerald-900/50 to-green-900/50 border-emerald-400/40">
              <div class="text-center">
                <div class="text-4xl mb-3">🎁</div>
                <h3 class="font-bold text-white mb-2">Ежедневный бонус</h3>
                <p class="text-emerald-300 text-sm mb-4">Зайдите завтра для получения награды</p>
                <div class="bg-emerald-500/20 rounded-lg p-3">
                  <div class="text-emerald-400 font-bold">+500 ₽</div>
                  <div class="text-emerald-300 text-xs">Следующая награда</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps({
  user: Object,
  auth: Object,
  errors: Object
})

// Mock data
const recentGames = ref([
  { id: 1, mode: 'Быстрая игра', won: true, result: '+1,200 ₽', date: '2 часа назад', duration: '15 мин' },
  { id: 2, mode: 'Турнир', won: false, result: '-500 ₽', date: '5 часов назад', duration: '25 мин' },
  { id: 3, mode: 'С друзьями', won: true, result: '+800 ₽', date: 'Вчера', duration: '18 мин' },
  { id: 4, mode: 'Рейтинговая', won: true, result: '+1,500 ₽', date: '2 дня назад', duration: '22 мин' }
])

const achievements = ref([
  { id: 1, icon: '🎯', name: 'Первая победа', progress: 'Получено' },
  { id: 2, icon: '💰', name: 'Богач', progress: '75%' },
  { id: 3, icon: '⚡', name: 'Скоростная игра', progress: '3/10' },
  { id: 4, icon: '🏆', name: 'Чемпион', progress: 'Получено' },
  { id: 5, icon: '👑', name: 'Король стола', progress: '45%' },
  { id: 6, icon: '🎪', name: 'Тактик', progress: '60%' },
  { id: 7, icon: '🌟', name: 'Звезда', progress: 'Получено' },
  { id: 8, icon: '📈', name: 'Восхождение', progress: '80%' }
])

const onlineFriends = ref([
  { id: 1, name: 'Алексей', status: 'В лобби' },
  { id: 2, name: 'Мария', status: 'В игре' },
  { id: 3, name: 'Дмитрий', status: 'Онлайн' }
])

const logout = () => {
  router.post('/logout')
}
</script>

<style scoped>
/* Прогресс-бар уровня */
.progress-bar {
  background: linear-gradient(90deg, #10b981 0%, #059669 84%, #047857 100%);
}
</style>