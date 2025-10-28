export function useRoute() {
    const routes = {
        'welcome': '/',
        'login': '/login',
        'register': '/register',
        'dashboard': '/dashboard',
        'lobby': '/lobby',
        'seka.lobby': '/lobby'
    };

    const route = (name) => routes[name] || '/';
    
    return { route };
}