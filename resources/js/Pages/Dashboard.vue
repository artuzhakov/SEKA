<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-900 via-seka-green-dark to-gray-900">
    <!-- Enhanced Navigation -->
    <nav class="bg-gradient-to-r from-gray-900 to-emerald-900/30 border-b border-emerald-500/40 backdrop-blur-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-green-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/25">
                <span class="text-white font-bold text-lg">🎴</span>
              </div>
              <span class="text-2xl font-bold bg-gradient-to-r from-emerald-300 to-green-400 bg-clip-text text-transparent">
                SEKA
              </span>
            </div>
            <div class="ml-8 flex space-x-1">
              <Link href="/dashboard" class="nav-link-seka-active">
                🏠 Главная
              </Link>
              <Link href="/lobby" class="nav-link-seka">
                🎮 Лобби
              </Link>
              <Link href="/friends" class="nav-link-seka">
                👥 Друзья
              </Link>
              <Link href="/leaderboard" class="nav-link-seka">
                🏆 Рейтинг
              </Link>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-3 bg-gradient-to-r from-emerald-900/40 to-green-900/40 rounded-2xl px-4 py-2 border border-emerald-500/30 backdrop-blur-sm">
              <div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-green-500 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-lg">
                {{ user.name.charAt(0) }}
              </div>
              <div class="flex flex-col">
                <span class="text-white font-medium text-sm">{{ user.name }}</span>
                <span class="text-emerald-300 text-xs flex items-center">
                  ⭐ Премиум игрок
                </span>
              </div>
            </div>
            <button @click="logout" class="btn-seka-logout">
              <span class="mr-2">🚪</span>
              Выйти
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Enhanced Welcome Header -->
        <div class="text-center mb-12">
          <div class="relative inline-block">
            <div class="absolute -inset-4 bg-gradient-to-r from-emerald-500/20 to-green-500/20 rounded-3xl blur-lg opacity-75"></div>
            <div class="relative flex items-center justify-center space-x-6 bg-gray-800/80 backdrop-blur-sm rounded-2xl p-8 border border-emerald-500/30">
              <div class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-green-500 rounded-2xl flex items-center justify-center text-white text-2xl font-bold shadow-2xl shadow-emerald-500/25">
                {{ user.name.charAt(0) }}
              </div>
              <div class="text-left">
                <h1 class="text-4xl font-bold bg-gradient-to-r from-emerald-300 to-green-400 bg-clip-text text-transparent">
                  Добро пожаловать, {{ user.name }}!
                </h1>
                <p class="text-gray-300 mt-2 text-lg">Ваш путь к покорению карточных вершин начинается здесь</p>
              </div>
            </div>
          </div>
          <div class="flex justify-center space-x-3 mt-6">
            <span class="badge-seka-premium">🎯 Активный игрок</span>
            <span class="badge-seka-gold">⭐ Премиум</span>
            <span class="badge-seka-champion">🏆 Чемпион</span>
          </div>
        </div>

        <!-- Enhanced Quick Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
          <div class="stats-card-seka">
            <div class="stats-icon bg-gradient-to-br from-emerald-500/20 to-green-500/20">
              🎮
            </div>
            <div class="text-3xl font-bold text-white mb-1">127</div>
            <div class="text-gray-400 text-sm">Сыграно игр</div>
            <div class="text-emerald-300 text-xs mt-2 flex items-center">
              <span class="w-2 h-2 bg-emerald-400 rounded-full mr-1"></span>
              +5 за неделю
            </div>
          </div>
          
          <div class="stats-card-seka">
            <div class="stats-icon bg-gradient-to-br from-yellow-500/20 to-amber-500/20">
              🏆
            </div>
            <div class="text-3xl font-bold text-white mb-1">68</div>
            <div class="text-gray-400 text-sm">Побед</div>
            <div class="text-amber-300 text-xs mt-2 flex items-center">
              <span class="w-2 h-2 bg-amber-400 rounded-full mr-1"></span>
              53.5% побед
            </div>
          </div>
          
          <div class="stats-card-seka">
            <div class="stats-icon bg-gradient-to-br from-green-500/20 to-emerald-500/20">
              💰
            </div>
            <div class="text-3xl font-bold text-white mb-1">8,450</div>
            <div class="text-gray-400 text-sm">Баланс</div>
            <div class="text-green-300 text-xs mt-2 flex items-center">
              <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
              +1,200 за месяц
            </div>
          </div>
          
          <div class="stats-card-seka">
            <div class="stats-icon bg-gradient-to-br from-blue-500/20 to-cyan-500/20">
              📈
            </div>
            <div class="text-3xl font-bold text-white mb-1">24</div>
            <div class="text-gray-400 text-sm">Уровень</div>
            <div class="text-cyan-300 text-xs mt-2 flex items-center">
              <span class="w-2 h-2 bg-cyan-400 rounded-full mr-1"></span>
              Следующий через 156 очков
            </div>
          </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
          <!-- Left Column -->
          <div class="xl:col-span-2 space-y-8">
            <!-- Enhanced Quick Actions -->
            <div class="glass-card-seka">
              <h2 class="section-title">🚀 Быстрые действия</h2>
              <div class="grid grid-cols-2 gap-4">
                <button @click="$inertia.visit('/lobby')" class="action-btn-seka-primary">
                  <div class="action-icon">🎮</div>
                  <div class="font-semibold">Быстрая игра</div>
                  <div class="text-xs text-emerald-300 mt-1">Начать сейчас</div>
                </button>
                <button class="action-btn-seka-secondary">
                  <div class="action-icon">👥</div>
                  <div class="font-semibold">С друзьями</div>
                  <div class="text-xs text-blue-300 mt-1">Приватная игра</div>
                </button>
                <button class="action-btn-seka-secondary">
                  <div class="action-icon">🏆</div>
                  <div class="font-semibold">Турнир</div>
                  <div class="text-xs text-yellow-300 mt-1">Соревнование</div>
                </button>
                <button class="action-btn-seka-secondary">
                  <div class="action-icon">🎁</div>
                  <div class="font-semibold">Бонусы</div>
                  <div class="text-xs text-purple-300 mt-1">Ежедневные награды</div>
                </button>
              </div>
            </div>

            <!-- Enhanced Recent Activity -->
            <div class="glass-card-seka">
              <div class="flex justify-between items-center mb-6">
                <h2 class="section-title">📊 Последние игры</h2>
                <button class="text-emerald-400 hover:text-emerald-300 text-sm font-semibold transition-colors">
                  Вся история →
                </button>
              </div>
              <div class="space-y-3">
                <div v-for="game in recentGames" :key="game.id" 
                     class="game-history-item"
                     :class="game.won ? 'border-l-emerald-500' : 'border-l-red-500'">
                  <div class="flex items-center space-x-4">
                    <div class="text-2xl" :class="game.won ? 'text-emerald-400' : 'text-red-400'">
                      {{ game.won ? '🏆' : '💔' }}
                    </div>
                    <div class="flex-1">
                      <div class="font-semibold text-white">{{ game.mode }}</div>
                      <div class="text-gray-400 text-sm">{{ game.date }}</div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div :class="game.won ? 'text-emerald-400' : 'text-red-400'" class="font-bold text-lg">
                      {{ game.result }}
                    </div>
                    <div class="text-gray-400 text-sm">{{ game.duration }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Enhanced Achievements -->
            <div class="glass-card-seka">
              <h2 class="section-title">🎯 Достижения</h2>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-for="achievement in achievements" :key="achievement.id"
                     class="achievement-card"
                     :class="achievement.progress === 'Получено' ? 'achievement-completed' : ''">
                  <div class="achievement-icon">{{ achievement.icon }}</div>
                  <div class="achievement-name">{{ achievement.name }}</div>
                  <div class="achievement-progress" :class="achievement.progress === 'Получено' ? 'text-emerald-400' : 'text-amber-400'">
                    {{ achievement.progress }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="space-y-8">
            <!-- Enhanced Profile Card -->
            <div class="glass-card-seka">
              <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-emerald-400 to-green-500 rounded-2xl flex items-center justify-center text-white text-xl font-bold mx-auto mb-4 shadow-lg">
                  {{ user.name.charAt(0) }}
                </div>
                <h3 class="text-xl font-bold text-white">{{ user.name }}</h3>
                <p class="text-emerald-300 text-sm flex items-center justify-center">
                  <span class="mr-1">⭐</span>
                  Премиум аккаунт
                </p>
              </div>

              <div class="space-y-3">
                <div class="profile-stat">
                  <span class="text-gray-400">Уровень:</span>
                  <span class="text-white font-bold">24</span>
                </div>
                <div class="profile-stat">
                  <span class="text-gray-400">Опыт:</span>
                  <div class="flex items-center space-x-2">
                    <div class="w-20 bg-gray-700 rounded-full h-2">
                      <div class="bg-gradient-to-r from-emerald-400 to-green-500 h-2 rounded-full" style="width: 84%"></div>
                    </div>
                    <span class="text-emerald-400 font-bold text-sm">844/1000</span>
                  </div>
                </div>
                <div class="profile-stat">
                  <span class="text-gray-400">Рейтинг:</span>
                  <span class="text-yellow-400 font-bold">1,856</span>
                </div>
                <div class="profile-stat">
                  <span class="text-gray-400">В сети:</span>
                  <span class="text-green-400 font-bold flex items-center">
                    <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                    Онлайн
                  </span>
                </div>
              </div>

              <div class="mt-6 grid grid-cols-2 gap-3">
                <button class="profile-action-btn">
                  <span class="mr-2">✏️</span>
                  Профиль
                </button>
                <button class="profile-action-btn">
                  <span class="mr-2">⚙️</span>
                  Настройки
                </button>
              </div>
            </div>

            <!-- Enhanced Friends Online -->
            <div class="glass-card-seka">
              <div class="flex justify-between items-center mb-4">
                <h3 class="section-title-sm">👥 Друзья онлайн</h3>
                <span class="text-emerald-400 text-sm font-semibold">3/12</span>
              </div>
              <div class="space-y-3">
                <div v-for="friend in onlineFriends" :key="friend.id"
                     class="friend-online-item">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                      {{ friend.name.charAt(0) }}
                    </div>
                    <div class="flex-1">
                      <div class="text-white text-sm font-medium">{{ friend.name }}</div>
                      <div class="text-emerald-400 text-xs">{{ friend.status }}</div>
                    </div>
                  </div>
                  <button class="join-friend-btn">
                    Присоединиться
                  </button>
                </div>
              </div>
            </div>

            <!-- Enhanced Daily Bonus -->
            <div class="bonus-card">
              <div class="text-center">
                <div class="text-4xl mb-3">🎁</div>
                <h3 class="font-bold text-white mb-2">Ежедневный бонус</h3>
                <p class="text-emerald-300 text-sm mb-4">Зайдите завтра для получения награды</p>
                <div class="bonus-reward">
                  <div class="text-emerald-400 font-bold text-lg">+500 ₽</div>
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

// Mock data остается таким же...
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
/* Modern Glass Effect */
.glass-card-seka {
  background: rgba(55, 65, 81, 0.6);
  backdrop-filter: blur(8px);
  border-radius: 1rem;
  padding: 1.5rem;
  border: 1px solid rgba(16, 185, 129, 0.2);
  box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.1);
}

/* Section Titles */
.section-title {
  font-size: 1.25rem;
  font-weight: bold;
  background: linear-gradient(to right, #6ee7b7, #34d399);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  margin-bottom: 1.5rem;
}

.section-title-sm {
  font-weight: bold;
  background: linear-gradient(to right, #6ee7b7, #34d399);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

/* Stats Cards */
.stats-card-seka {
  background: rgba(55, 65, 81, 0.6);
  backdrop-filter: blur(8px);
  border-radius: 1rem;
  padding: 1.5rem;
  border: 1px solid rgba(16, 185, 129, 0.2);
  box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.1);
  text-align: center;
  transition: all 0.3s ease;
  cursor: pointer;
}

.stats-card-seka:hover {
  transform: scale(1.05);
}

.stats-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  margin: 0 auto 0.75rem auto;
}

/* Navigation */
.nav-link-seka {
  color: #d1d5db;
  padding: 0.5rem 1rem;
  transition: all 0.3s ease;
  border-radius: 0.5rem;
}

.nav-link-seka:hover {
  color: white;
  background: rgba(16, 185, 129, 0.1);
}

.nav-link-seka-active {
  color: white;
  background: linear-gradient(to right, rgba(16, 185, 129, 0.2), rgba(34, 197, 94, 0.2));
  border: 1px solid rgba(16, 185, 129, 0.3);
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-weight: 600;
}

/* Buttons */
.btn-seka-logout {
  background: linear-gradient(to right, rgba(239, 68, 68, 0.2), rgba(236, 72, 153, 0.2));
  color: #fca5a5;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  border: 1px solid rgba(239, 68, 68, 0.3);
  transition: all 0.3s ease;
  font-weight: 600;
}

.btn-seka-logout:hover {
  background: linear-gradient(to right, rgba(239, 68, 68, 0.3), rgba(236, 72, 153, 0.3));
  color: #fecaca;
}

.action-btn-seka-primary {
  background: rgba(55, 65, 81, 0.6);
  backdrop-filter: blur(8px);
  border-radius: 1rem;
  padding: 1rem;
  border: 2px solid rgba(16, 185, 129, 0.4);
  text-align: center;
  transition: all 0.3s ease;
  cursor: pointer;
}

.action-btn-seka-primary:hover {
  transform: scale(1.05);
  border-color: rgba(16, 185, 129, 0.6);
}

.action-btn-seka-secondary {
  background: rgba(55, 65, 81, 0.6);
  backdrop-filter: blur(8px);
  border-radius: 1rem;
  padding: 1rem;
  border: 1px solid rgba(75, 85, 99, 0.6);
  text-align: center;
  transition: all 0.3s ease;
  cursor: pointer;
}

.action-btn-seka-secondary:hover {
  transform: scale(1.05);
  border-color: rgba(16, 185, 129, 0.4);
}

.action-icon {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

/* Game History */
.game-history-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: linear-gradient(to right, rgba(55, 65, 81, 0.5), rgba(31, 41, 55, 0.5));
  border-radius: 0.75rem;
  border-left-width: 4px;
  backdrop-filter: blur(8px);
}

/* Achievements */
.achievement-card {
  text-align: center;
  padding: 0.75rem;
  background: rgba(55, 65, 81, 0.4);
  border-radius: 0.75rem;
  border: 1px solid rgba(16, 185, 129, 0.1);
  transition: all 0.3s ease;
}

.achievement-card:hover {
  border-color: rgba(16, 185, 129, 0.4);
}

.achievement-completed {
  background: linear-gradient(to bottom right, rgba(16, 185, 129, 0.1), rgba(34, 197, 94, 0.1));
  border-color: rgba(16, 185, 129, 0.4);
}

.achievement-icon {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

.achievement-name {
  font-weight: 500;
  color: white;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.achievement-progress {
  font-size: 0.75rem;
  font-weight: 600;
}

/* Profile Stats */
.profile-stat {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid rgba(55, 65, 81, 0.5);
}

.profile-action-btn {
  background: linear-gradient(to right, rgba(55, 65, 81, 0.5), rgba(31, 41, 55, 0.5));
  color: #d1d5db;
  padding: 0.5rem;
  border-radius: 0.5rem;
  border: 1px solid rgba(75, 85, 99, 0.6);
  transition: all 0.3s ease;
  font-size: 0.875rem;
  font-weight: 500;
}

.profile-action-btn:hover {
  background: linear-gradient(to right, rgba(16, 185, 129, 0.2), rgba(34, 197, 94, 0.2));
  color: white;
  border-color: rgba(16, 185, 129, 0.4);
}

/* Friends */
.friend-online-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
  background: linear-gradient(to right, rgba(55, 65, 81, 0.4), rgba(31, 41, 55, 0.4));
  border-radius: 0.75rem;
  border: 1px solid rgba(75, 85, 99, 0.5);
}

.join-friend-btn {
  color: #34d399;
  font-size: 0.75rem;
  font-weight: 600;
  background: rgba(16, 185, 129, 0.1);
  padding: 0.25rem 0.75rem;
  border-radius: 0.5rem;
  transition: all 0.3s ease;
}

.join-friend-btn:hover {
  color: #6ee7b7;
  background: rgba(16, 185, 129, 0.2);
}

/* Bonus Card */
.bonus-card {
  background: rgba(55, 65, 81, 0.6);
  backdrop-filter: blur(8px);
  border-radius: 1rem;
  padding: 1.5rem;
  border: 1px solid rgba(16, 185, 129, 0.5);
  background: linear-gradient(to bottom right, rgba(6, 78, 59, 0.4), rgba(5, 150, 105, 0.4));
}

.bonus-reward {
  background: linear-gradient(to right, rgba(16, 185, 129, 0.2), rgba(34, 197, 94, 0.2));
  border-radius: 0.75rem;
  padding: 1rem;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

/* Badges */
.badge-seka-premium {
  background: linear-gradient(to right, rgba(16, 185, 129, 0.2), rgba(34, 197, 94, 0.2));
  color: #6ee7b7;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 600;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.badge-seka-gold {
  background: linear-gradient(to right, rgba(234, 179, 8, 0.2), rgba(245, 158, 11, 0.2));
  color: #fcd34d;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 600;
  border: 1px solid rgba(234, 179, 8, 0.3);
}

.badge-seka-champion {
  background: linear-gradient(to right, rgba(168, 85, 247, 0.2), rgba(236, 72, 153, 0.2));
  color: #d8b4fe;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 600;
  border: 1px solid rgba(168, 85, 247, 0.3);
}
</style>