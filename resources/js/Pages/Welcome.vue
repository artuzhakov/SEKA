<template>
  <div class="min-h-screen bg-seka-primary">
    <!-- Navigation -->
    <nav class="bg-gray-800 border-b border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <span class="text-2xl font-bold text-gradient">🎴 SEKA</span>
          </div>
          <div class="flex items-center space-x-4">
            <Link
              v-if="!auth.user"
              href="/login"
              class="btn-seka-secondary"
            >
              Войти
            </Link>
            <Link
              v-if="!auth.user"
              href="/register"
              class="btn-seka-primary"
            >
              Регистрация
            </Link>
            <Link
              v-if="auth.user"
              href="/lobby"
              class="btn-seka-primary"
            >
              Войти в лобби
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <div class="py-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h1 class="text-5xl md:text-7xl font-bold mb-6 text-gradient">
            SEKA
          </h1>
          <p class="text-xl md:text-2xl text-gray-300 mb-8 max-w-3xl mx-auto leading-relaxed">
            Легендарная русская карточная игра с уникальной механикой торгов, 
            <span class="text-purple-400 font-semibold">темной игрой</span> и захватывающими дуэлями
          </p>
          
          <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
            <Link
              v-if="!auth.user"
              href="/register"
              class="btn-seka-primary text-lg py-4 px-8"
            >
              🎮 Начать играть
            </Link>
            <Link
              v-if="auth.user"
              href="/lobby"
              class="btn-seka-primary text-lg py-4 px-8"
            >
              🎮 Войти в лобби
            </Link>
            <a
              href="#features"
              class="btn-seka-secondary text-lg py-4 px-8"
            >
              📖 Узнать больше
            </a>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-2xl mx-auto mb-16">
            <div class="text-center">
              <div class="text-3xl font-bold text-purple-400 mb-2">10k+</div>
              <div class="text-gray-400">Игроков онлайн</div>
            </div>
            <div class="text-center">
              <div class="text-3xl font-bold text-blue-400 mb-2">50k+</div>
              <div class="text-gray-400">Сыграно игр</div>
            </div>
            <div class="text-center">
              <div class="text-3xl font-bold text-green-400 mb-2">24/7</div>
              <div class="text-gray-400">Доступность</div>
            </div>
            <div class="text-center">
              <div class="text-3xl font-bold text-yellow-400 mb-2">100%</div>
              <div class="text-gray-400">Бесплатно</div>
            </div>
          </div>
        </div>

        <!-- Features -->
        <div id="features" class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-20">
          <div class="card-seka text-center group hover:transform hover:scale-105">
            <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">🎴</div>
            <h3 class="text-xl font-bold mb-3 text-gradient">Уникальные правила</h3>
            <p class="text-gray-400 leading-relaxed">
              Три круга торгов, темная игра, система ВАРА - все это делает SEKA особенной карточной игрой
            </p>
          </div>
          
          <div class="card-seka text-center group hover:transform hover:scale-105">
            <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">⚡</div>
            <h3 class="text-xl font-bold mb-3 text-gradient">Быстрая игра</h3>
            <p class="text-gray-400 leading-relaxed">
              Динамичные партии по 10-15 минут. Идеально для коротких перерывов и вечернего отдыха
            </p>
          </div>
          
          <div class="card-seka text-center group hover:transform hover:scale-105">
            <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">🏆</div>
            <h3 class="text-xl font-bold mb-3 text-gradient">Рейтинговая система</h3>
            <p class="text-gray-400 leading-relaxed">
              Соревнуйтесь с другими игроками, поднимайтесь в рейтинге и становитесь легендой SEKA
            </p>
          </div>
        </div>

        <!-- How to Play -->
        <div class="mt-20 card-seka">
          <h2 class="text-3xl font-bold text-center mb-12 text-gradient">🎯 Как играть в SEKA?</h2>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center">
              <div class="avatar-seka-lg mx-auto mb-4">1</div>
              <h4 class="font-bold mb-2">Создайте игру</h4>
              <p class="text-gray-400 text-sm">Выберите настройки и создайте игровую комнату</p>
            </div>
            <div class="text-center">
              <div class="avatar-seka-lg mx-auto mb-4">2</div>
              <h4 class="font-bold mb-2">Пригласите друзей</h4>
              <p class="text-gray-400 text-sm">До 6 игроков могут присоединиться к вашей игре</p>
            </div>
            <div class="text-center">
              <div class="avatar-seka-lg mx-auto mb-4">3</div>
              <h4 class="font-bold mb-2">Торгуйтесь</h4>
              <p class="text-gray-400 text-sm">Используйте CHECK, CALL, RAISE, FOLD и темную игру</p>
            </div>
            <div class="text-center">
              <div class="avatar-seka-lg mx-auto mb-4">4</div>
              <h4 class="font-bold mb-2">Побеждайте</h4>
              <p class="text-gray-400 text-sm">Собирайте лучшие комбинации и забирайте банк</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 border-t border-gray-700 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center text-gray-400">
          <p>&copy; 2024 SEKA Card Game. Все права защищены.</p>
          <p class="mt-2 text-sm">Легендарная русская карточная игра</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  canLogin: Boolean,
  canRegister: Boolean,
  auth: {
    type: Object,
    default: () => ({ user: null })
  },
  laravelVersion: String,
  phpVersion: String,
})
</script>