<!-- resources/js/Pages/Dashboard.vue -->
<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
          
          <!-- Header -->
          <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-semibold text-gray-900">
              Добро пожаловать, {{ user.name }}! 👋
            </h1>
            <div class="flex space-x-4">
              <Link 
                :href="route('seka.lobby')" 
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700"
              >
                🎮 Играть в SEKA
              </Link>
            </div>
          </div>

          <!-- Stats Grid -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-blue-50 p-6 rounded-lg">
              <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                  <span class="text-2xl">💰</span>
                </div>
                <div class="ml-4">
                  <p class="text-sm text-blue-600">Баланс</p>
                  <p class="text-2xl font-bold text-gray-900">{{ wallet?.balance || 0 }} ₽</p>
                </div>
              </div>
            </div>
            
            <div class="bg-green-50 p-6 rounded-lg">
              <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                  <span class="text-2xl">🎴</span>
                </div>
                <div class="ml-4">
                  <p class="text-sm text-green-600">Сыграно игр</p>
                  <p class="text-2xl font-bold text-gray-900">0</p>
                </div>
              </div>
            </div>
            
            <div class="bg-purple-50 p-6 rounded-lg">
              <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                  <span class="text-2xl">🏆</span>
                </div>
                <div class="ml-4">
                  <p class="text-sm text-purple-600">Рейтинг</p>
                  <p class="text-2xl font-bold text-gray-900">-</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="mb-8">
            <h2 class="text-lg font-semibold mb-4">Быстрые действия</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <Link 
                :href="route('seka.lobby')" 
                class="bg-white border border-gray-300 p-4 rounded-lg text-center hover:bg-gray-50"
              >
                <div class="text-2xl mb-2">🎲</div>
                <p class="font-medium">Быстрая игра</p>
              </Link>
              
              <button 
                @click="showDepositModal = true"
                class="bg-white border border-gray-300 p-4 rounded-lg text-center hover:bg-gray-50"
              >
                <div class="text-2xl mb-2">💳</div>
                <p class="font-medium">Пополнить счет</p>
              </button>
              
              <Link 
                :href="route('profile.edit')" 
                class="bg-white border border-gray-300 p-4 rounded-lg text-center hover:bg-gray-50"
              >
                <div class="text-2xl mb-2">👤</div>
                <p class="font-medium">Профиль</p>
              </Link>
              
              <button 
                @click="showRules = true"
                class="bg-white border border-gray-300 p-4 rounded-lg text-center hover:bg-gray-50"
              >
                <div class="text-2xl mb-2">📖</div>
                <p class="font-medium">Правила</p>
              </button>
            </div>
          </div>

          <!-- Recent Activity -->
          <div>
            <h2 class="text-lg font-semibold mb-4">Последние игры</h2>
            <div class="bg-gray-50 rounded-lg p-6 text-center">
              <p class="text-gray-500">Здесь будет отображаться история ваших игр</p>
              <Link 
                :href="route('seka.lobby')" 
                class="text-indigo-600 hover:text-indigo-800 mt-2 inline-block"
              >
                Сыграйте первую игру!
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps({
  wallet: Object,
  user: Object
})

const showDepositModal = ref(false)
const showRules = ref(false)
</script>