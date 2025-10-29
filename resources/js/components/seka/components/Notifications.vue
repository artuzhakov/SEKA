<template>
  <div class="notifications-container">
    <div
      v-for="notification in notifications"
      :key="notification.id"
      class="notification"
      :class="notification.type"
    >
      <span class="notification-icon">{{ getNotificationIcon(notification.type) }}</span>
      <span class="notification-message">{{ notification.message }}</span>
      <button @click="removeNotification(notification.id)" class="notification-close">×</button>
    </div>
  </div>
</template>

<script setup>
import { useNotifications } from '@/components/seka/composables/useNotifications'

const { notifications, removeNotification } = useNotifications()

const getNotificationIcon = (type) => {
  const icons = {
    success: '✅',
    error: '❌',
    warning: '⚠️',
    info: 'ℹ️'
  }
  return icons[type] || '📢'
}
</script>

<style scoped>
.notifications-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1000;
  max-width: 400px;
}

.notification {
  background: white;
  padding: 15px;
  margin-bottom: 10px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  display: flex;
  align-items: center;
  gap: 10px;
  border-left: 4px solid #007bff;
  animation: slideIn 0.3s ease;
}

.notification.success { border-left-color: #28a745; }
.notification.error { border-left-color: #dc3545; }
.notification.warning { border-left-color: #ffc107; }
.notification.info { border-left-color: #17a2b8; }

.notification-icon {
  font-size: 18px;
}

.notification-message {
  flex: 1;
  font-size: 14px;
}

.notification-close {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  color: #6c757d;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@media (max-width: 768px) {
  .notifications-container {
    right: 10px;
    left: 10px;
    max-width: none;
  }
}
</style>