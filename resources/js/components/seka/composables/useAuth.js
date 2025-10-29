// resources/js/composables/useAuth.js
import { ref, computed } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

export function useAuth() {
    const user = ref(JSON.parse(localStorage.getItem('user') || 'null'));
    const isLoading = ref(false);
    const error = ref(null);

    const isAuthenticated = computed(() => !!user.value);

    const login = async (credentials) => {
        isLoading.value = true;
        error.value = null;

        try {
            // Получаем CSRF cookie
            await axios.get('/sanctum/csrf-cookie');

            // Логинимся
            const response = await axios.post('/login', credentials);

            if (response.status === 200) {
                // Получаем данные пользователя
                const userResponse = await axios.get('/api/user');
                user.value = userResponse.data;
                localStorage.setItem('user', JSON.stringify(user.value));
                
                // router.visit('/dashboard');
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Login failed';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const register = async (userData) => {
        isLoading.value = true;
        error.value = null;

        try {
            await axios.get('/sanctum/csrf-cookie');
            const response = await axios.post('/register', userData);

            if (response.status === 200) {
                const userResponse = await axios.get('/api/user');
                user.value = userResponse.data;
                localStorage.setItem('user', JSON.stringify(user.value));
                
                // router.visit('/dashboard');
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Registration failed';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const logout = async () => {
        try {
            await axios.post('/logout');
        } catch (err) {
            console.error('Logout error:', err);
        } finally {
            user.value = null;
            localStorage.removeItem('user');
            router.visit('/');
        }
    };

    const checkAuth = async () => {
        try {
            const response = await axios.get('/api/user');
            user.value = response.data;
            localStorage.setItem('user', JSON.stringify(user.value));
            return true;
        } catch (err) {
            user.value = null;
            localStorage.removeItem('user');
            return false;
        }
    };

    return {
        user,
        isAuthenticated,
        isLoading,
        error,
        login,
        register,
        logout,
        checkAuth
    };
}