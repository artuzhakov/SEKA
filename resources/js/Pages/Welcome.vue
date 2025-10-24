<!-- resources/js/Pages/Welcome.vue -->
<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <span class="text-2xl font-bold text-indigo-600">🎴 SEKA</span>
          </div>
          <div class="flex items-center space-x-4">
            <Link
              v-if="!$page.props.auth.user"
              :href="route('login')"
              class="text-gray-600 hover:text-gray-900"
            >
              Войти
            </Link>
            <Link
              v-if="!$page.props.auth.user && canRegister"
              :href="route('register')"
              class="text-gray-600 hover:text-gray-900"
            >
              Регистрация
            </Link>
            <Link
              v-if="$page.props.auth.user"
              :href="route('dashboard')"
              class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700"
            >
              Кабинет
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <div class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
            Добро пожаловать в
            <span class="text-indigo-600">SEKA</span>
          </h1>
          <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
            Увлекательная карточная игра с уникальной механикой торгов, 
            темной игрой и захватывающими дуэлями. Присоединяйтесь к сообществу игроков!
          </p>
          
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              v-if="!$page.props.auth.user"
              :href="route('register')"
              class="bg-indigo-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-indigo-700 transition"
            >
              Начать играть
            </Link>
            <Link
              v-if="$page.props.auth.user"
              :href="route('seka.lobby')"
              class="bg-indigo-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-indigo-700 transition"
            >
              Войти в лобби
            </Link>
            <a
              href="#features"
              class="border border-indigo-600 text-indigo-600 px-8 py-3 rounded-lg text-lg font-semibold hover:bg-indigo-50 transition"
            >
              Узнать больше
            </a>
          </div>
        </div>

        <!-- Features -->
        <div id="features" class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="bg-white p-6 rounded-xl shadow-sm">
            <div class="text-3xl mb-4">🎴</div>
            <h3 class="text-xl font-semibold mb-2">Уникальные правила</h3>
            <p class="text-gray-600">Три круга торгов, темная игра, система ВАРА - все это делает SEKA особенной</p>
          </div>
          
          <div class="bg-white p-6 rounded-xl shadow-sm">
            <div class="text-3xl mb-4">⚡</div>
            <h3 class="text-xl font-semibold mb-2">Быстрая игра</h3>
            <p class="text-gray-600">Динамичные партии по 10-15 минут. Идеально для коротких перерывов</p>
          </div>
          
          <div class="bg-white p-6 rounded-xl shadow-sm">
            <div class="text-3xl mb-4">🏆</div>
            <h3 class="text-xl font-semibold mb-2">Рейтинговая система</h3>
            <p class="text-gray-600">Соревнуйтесь с другими игроками и поднимайтесь в рейтинге</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t mt-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center text-gray-600">
          <p>&copy; 2024 SEKA Card Game. Все права защищены.</p>
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
  laravelVersion: String,
  phpVersion: String,
})
</script>