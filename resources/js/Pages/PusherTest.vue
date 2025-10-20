<template>
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Pusher Connection Test</h1>
        <PusherTestComponent />
        
        <div class="mt-6 p-4 bg-gray-100 rounded">
            <h4 class="font-bold mb-2">Debug Information:</h4>
            <p>Pusher Key: {{ pusherKey }}</p>
            <p>Pusher Cluster: {{ pusherCluster }}</p>
            <p>Current URL: {{ currentUrl }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import PusherTestComponent from '../components/PusherTestComponent.vue'

const pusherKey = computed(() => import.meta.env.VITE_PUSHER_APP_KEY || 'Not set')
const pusherCluster = computed(() => import.meta.env.VITE_PUSHER_APP_CLUSTER || 'Not set')
const currentUrl = computed(() => window.location.href)
</script>