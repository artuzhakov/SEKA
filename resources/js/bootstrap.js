/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from "laravel-echo";

import Pusher from "pusher-js";
window.Pusher = Pusher;

const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER
// console.log(import.meta.env.VITE_PUSHER_APP_KEY)
// console.log(import.meta.env.VITE_PUSHER_PORT ?? 443)
// console.log((import.meta.env.VITE_PUSHER_SCHEME ?? "https") === "https")
console.log('🔧 Pusher Configuration:')
console.log('Key:', pusherKey)
console.log('Cluster:', pusherCluster)
console.log('Key exists:', !!pusherKey)
console.log('Key length:', pusherKey?.length)

if (!pusherKey || pusherKey === 'your_pusher_key' || pusherKey.includes('your_')) {
    console.error('❌ INVALID PUSHER KEY: Please set real Pusher credentials in .env')
}

// Инициализация Echo
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: pusherKey,
    cluster: pusherCluster,
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
    disabledTransports: ['sockjs', 'xhr_polling', 'xhr_streaming']
})

console.log('✅ Echo initialized')
console.log('Echo instance:', window.Echo)

// window.Echo = new Echo({
//     broadcaster: "pusher",
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? "mt1",
//     wsHost: import.meta.env.VITE_PUSHER_HOST
//         ? import.meta.env.VITE_PUSHER_HOST
//         : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     // wsPort: 6000,
//     // wssPort: 6001,
//     forceTLS: true,
//     // forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? "https") === "https",
//     enabledTransports: ["ws", "wss"],
// });
