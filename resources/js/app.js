import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import '../css/app.css';

// Импортируем axios для глобальной настройки
import axios from 'axios';

// Настраиваем axios для работы с Sanctum
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Функция для получения CSRF токена
const getCsrfToken = async () => {
    try {
        await axios.get('/sanctum/csrf-cookie');
        return true;
    } catch (error) {
        console.error('CSRF token fetch failed:', error);
        return false;
    }
};

// Перехватчик для автоматической аутентификации
axios.interceptors.request.use(async (config) => {
    // Для API запросов добавляем заголовки аутентификации
    if (config.url.startsWith('/api/')) {
        await getCsrfToken();
        
        // Добавляем заголовок авторизации если пользователь авторизован
        const user = JSON.parse(localStorage.getItem('user') || 'null');
        if (user && user.token) {
            config.headers.Authorization = `Bearer ${user.token}`;
        }
    }
    return config;
});

// Обработка ошибок аутентификации
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Перенаправляем на страницу логина
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});