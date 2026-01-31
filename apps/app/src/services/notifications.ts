import { apiFetch } from '@/services/api'

export type NotificationItem = {
  id: string
  type: string
  title: string | null
  body: string | null
  action_url: string | null
  data: Record<string, unknown>
  read_at: string | null
  created_at: string | null
  is_read: boolean
}

export type NotificationsResponse = {
  data: NotificationItem[]
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export function fetchNotifications(perPage = 10) {
  return apiFetch<NotificationsResponse>(`/notifications?per_page=${perPage}`)
}

export function fetchUnreadCount() {
  return apiFetch<{ count: number }>('/notifications/unread-count')
}

export function markNotificationRead(id: string) {
  return apiFetch<void>(`/notifications/${id}/read`, { method: 'POST' })
}

export function markAllNotificationsRead() {
  return apiFetch<void>('/notifications/read-all', { method: 'POST' })
}
