import { ref } from 'vue'

export function useNotifications() {
  const notifications = ref([])

  const showNotification = (message, type = 'info') => {
    const id = Date.now()
    notifications.value.push({
      id,
      message,
      type,
      timestamp: new Date()
    })
    
    setTimeout(() => {
      removeNotification(id)
    }, 5000)
  }

  const removeNotification = (id) => {
    notifications.value = notifications.value.filter(n => n.id !== id)
  }

  const clearAllNotifications = () => {
    notifications.value = []
  }

  return {
    notifications,
    showNotification,
    removeNotification,
    clearAllNotifications
  }
}