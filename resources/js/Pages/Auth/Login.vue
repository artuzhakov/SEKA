<template>
  <div class="min-h-screen bg-seka-primary flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <h1 class="text-4xl font-bold text-gradient mb-2">🎴 SEKA</h1>
        <p class="text-gray-400">Войдите в свой аккаунт</p>
      </div>
      
      <div class="card-seka p-8 shadow-seka-lg">
        <form class="space-y-6" @submit.prevent="submit">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
            <input
              v-model="form.email"
              type="email"
              required
              autocomplete="email"
              class="input-seka"
              placeholder="your@email.com"
            >
            <div v-if="form.errors.email" class="text-red-400 text-sm mt-1">
              {{ form.errors.email }}
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Пароль</label>
            <input
              v-model="form.password"
              type="password"
              required
              autocomplete="current-password"
              class="input-seka"
              placeholder="••••••••"
            >
            <div v-if="form.errors.password" class="text-red-400 text-sm mt-1">
              {{ form.errors.password }}
            </div>
          </div>

          <div class="flex items-center justify-between">
            <label class="flex items-center">
              <input
                v-model="form.remember"
                type="checkbox"
                class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-600 rounded bg-gray-700"
              >
              <span class="ml-2 text-sm text-gray-300">Запомнить меня</span>
            </label>

            <Link 
              href="/forgot-password" 
              class="text-sm text-purple-400 hover:text-purple-300"
            >
              Забыли пароль?
            </Link>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full btn-seka-primary py-3 text-lg disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="form.processing">
              <span class="animate-spin inline-block mr-2">⟳</span>
              Вход...
            </span>
            <span v-else>🚪 Войти в аккаунт</span>
          </button>

          <div class="text-center">
            <Link href="/register" class="text-purple-400 hover:text-purple-300 font-medium">
              Нет аккаунта? Зарегистрироваться
            </Link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>