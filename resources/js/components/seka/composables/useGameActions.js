// resources/js/components/seka/composables/useGameActions.js

import { ref } from 'vue'
import axios from 'axios'

export function useGameActions(gameId) {
    const isActionLoading = ref(false)

    // 🔄 РЕАЛЬНЫЕ ДЕЙСТВИЯ ВМЕСТО МОКОВ
    const performAction = async (action, betAmount = null) => {
        isActionLoading.value = true
        try {
            const user = usePage().props.auth.user
            
            const response = await axios.post(`/api/seka/${gameId}/action`, {
                player_id: user.id,
                action: action,
                bet_amount: betAmount
            })

            return response.data
        } catch (error) {
            console.error('Action failed:', error)
            throw error
        } finally {
            isActionLoading.value = false
        }
    }

    // 🔄 ОТМЕТИТЬСЯ КАК ГОТОВЫЙ
    const markPlayerReady = async () => {
        try {
            const user = usePage().props.auth.user
            const response = await axios.post(`/api/seka/${gameId}/ready`, {
                game_id: gameId,
                player_id: user.id
            })
            return response.data
        } catch (error) {
            console.error('Ready action failed:', error)
            throw error
        }
    }

    return {
        isActionLoading,
        performAction,
        markPlayerReady
    }
}