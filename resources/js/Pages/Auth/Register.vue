<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-sm">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Регистрация в SEKA
        </h2>
      </div>

      <!-- Показываем ошибки -->
      <div v-if="form.errors && Object.keys(form.errors).length" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div v-for="error in form.errors" :key="error" class="text-red-800 text-sm">
          {{ error }}
        </div>
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="submit">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Имя</label>
          <input
            id="name"
            v-model="form.name"
            name="name"
            type="text"
            required
            autocomplete="name"
            class="relative block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Ваше имя"
          >
          <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">
            {{ form.errors.name }}
          </div>
        </div>
        
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            id="email"
            v-model="form.email"
            name="email"
            type="email"
            required
            autocomplete="email"
            class="relative block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Email адрес"
          >
          <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">
            {{ form.errors.email }}
          </div>
        </div>
        
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
          <input
            id="password"
            v-model="form.password"
            name="password"
            type="password"
            required
            autocomplete="new-password"
            class="relative block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Пароль"
          >
          <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">
            {{ form.errors.password }}
          </div>
        </div>

        <div>
          <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Подтверждение пароля</label>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            name="password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            class="relative block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Повторите пароль"
          >
        </div>

        <!-- Поле avatar (скрытое) -->
        <input type="hidden" v-model="form.avatar" />

        <div>
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="form.processing">Регистрация...</span>
            <span v-else>Зарегистрироваться</span>
          </button>
        </div>

        <div class="text-center">
          <Link href="/login" class="text-indigo-600 hover:text-indigo-500">
            Уже есть аккаунт? Войти
          </Link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  //avatar: '/avatars/default.png', // Добавляем значение по умолчанию для avatar
})

const submit = () => {
  form.post('/register')
}
</script>