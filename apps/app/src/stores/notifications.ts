import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import {
  fetchNotifications,
  fetchUnreadCount,
  markNotificationRead,
  markAllNotificationsRead,
  type NotificationItem,
} from '@/services/notifications'
import { getEcho, setEchoAuthToken } from '@/services/echo'

function normalizeNotification(raw: any): NotificationItem {
  const data = raw?.data ?? {}
  const fallbackId =
    typeof crypto !== 'undefined' && 'randomUUID' in crypto
      ? crypto.randomUUID()
      : `${Date.now()}-${Math.random().toString(16).slice(2)}`

  return {
    id: raw?.id ?? data.id ?? fallbackId,
    type: raw?.type ?? data.type ?? 'notification',
    title: data.title ?? null,
    body: data.body ?? null,
    action_url: data.action_url ?? null,
    data,
    read_at: raw?.read_at ?? null,
    created_at: raw?.created_at ?? null,
    is_read: Boolean(raw?.read_at),
  }
}

export const useNotificationsStore = defineStore('notifications', () => {
  const items = ref<NotificationItem[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const loaded = ref(false)
  const channelUserId = ref<number | null>(null)

  const unreadItems = computed(() => items.value.filter((item) => !item.is_read))

  async function refresh() {
    if (loading.value) return
    loading.value = true
    try {
      const response = await fetchNotifications(10)
      items.value = response.data
      loaded.value = true
    } finally {
      loading.value = false
    }
  }

  async function hydrate() {
    if (loaded.value) return
    await refresh()
  }

  async function refreshUnreadCount() {
    const response = await fetchUnreadCount()
    unreadCount.value = response.count
  }

  function receive(raw: any) {
    const notification = normalizeNotification(raw)
    items.value = [notification, ...items.value.filter((item) => item.id !== notification.id)]
    if (!notification.is_read) {
      unreadCount.value += 1
    }
  }

  async function markRead(id: string) {
    await markNotificationRead(id)
    items.value = items.value.filter((item) => item.id !== id)
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  }

  async function markAllRead() {
    await markAllNotificationsRead()
    items.value = []
    unreadCount.value = 0
  }

  function bindToUser(userId: number) {
    const token = localStorage.getItem('auth.token')
    setEchoAuthToken(token)
    if (channelUserId.value === userId) return
    if (channelUserId.value) {
      getEcho().leave(`App.Models.User.${channelUserId.value}`)
    }

    channelUserId.value = userId

    getEcho()
      .private(`App.Models.User.${userId}`)
      .notification((notification: any) => {
        receive(notification)
      })
      .listen('.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', (notification: any) => {
        receive(notification)
      })
  }

  function clear() {
    if (channelUserId.value) {
      getEcho().leave(`App.Models.User.${channelUserId.value}`)
    }

    channelUserId.value = null
    items.value = []
    unreadCount.value = 0
    loaded.value = false
  }

  return {
    items,
    unreadItems,
    unreadCount,
    loading,
    loaded,
    hydrate,
    refresh,
    refreshUnreadCount,
    receive,
    markRead,
    markAllRead,
    bindToUser,
    clear,
  }
})
