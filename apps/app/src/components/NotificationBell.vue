<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useNotificationsStore } from '@/stores/notifications'

const props = withDefaults(
  defineProps<{ align?: 'left' | 'right' }>(),
  {
    align: 'right',
  }
)

const notifications = useNotificationsStore()
const open = ref(false)
const livePulse = ref(false)
const rootRef = ref<HTMLElement | null>(null)

const hasUnread = computed(() => notifications.unreadCount > 0)
const dropdownClasses = computed(() =>
  props.align === 'left' ? 'left-0' : 'right-0'
)

async function toggle() {
  open.value = !open.value
  if (open.value) {
    await notifications.refresh()
    await notifications.refreshUnreadCount()
  }
}

function handleClickOutside(event: MouseEvent) {
  if (!rootRef.value) return
  if (!rootRef.value.contains(event.target as Node)) {
    open.value = false
  }
}

function formatDate(value: string | null) {
  if (!value) return ''
  const date = new Date(value)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))

  if (days === 0) return "Aujourd'hui"
  if (days === 1) return 'Hier'
  if (days < 7) return `Il y a ${days} jours`

  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
  })
}

async function handleMarkAll() {
  await notifications.markAllRead()
}

async function handleItemClick(id: string, isRead: boolean) {
  if (!isRead) {
    await notifications.markRead(id)
  }
  open.value = false
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

watch(
  () => notifications.unreadCount,
  async (next, prev) => {
    if (!open.value) return
    if (next > prev) {
      await notifications.refresh()
      livePulse.value = true
      window.setTimeout(() => {
        livePulse.value = false
      }, 900)
    }
  }
)
</script>

<template>
  <div ref="rootRef" class="relative">
    <button
      class="relative flex h-10 w-10 items-center justify-center rounded-full text-gray-600 transition hover:bg-gray-100"
      type="button"
      aria-label="Notifications"
      @click="toggle"
    >
      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24">
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
        />
      </svg>
      <span
        v-if="hasUnread"
        class="absolute right-0 top-0 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-[#FF385C] px-1.5 text-[11px] font-semibold text-white shadow-sm"
      >
        {{ notifications.unreadCount > 9 ? '9+' : notifications.unreadCount }}
      </span>
    </button>

    <div
      v-if="open"
      :class="[
        'absolute z-50 mt-3 w-96 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl',
        dropdownClasses,
      ]"
    >
      <div class="border-b border-gray-100 px-6 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <h3 class="text-lg font-semibold text-[#222222]">Notifications</h3>
            <span v-if="livePulse" class="relative flex h-2 w-2">
              <span
                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#FF385C] opacity-75"
              ></span>
              <span class="relative inline-flex h-2 w-2 rounded-full bg-[#FF385C]"></span>
            </span>
          </div>
          <button
            v-if="hasUnread"
            class="text-sm font-medium text-gray-600 underline transition hover:text-[#222222]"
            type="button"
            @click="handleMarkAll"
          >
            Tout lire
          </button>
        </div>
      </div>

      <div v-if="notifications.loading" class="px-6 py-8 text-center text-sm text-gray-500">
        <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-[#FF385C]"></div>
        Chargement...
      </div>

      <div v-else-if="notifications.items.length === 0" class="px-6 py-12 text-center">
        <svg class="mx-auto mb-3 h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24">
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
          />
        </svg>
        <p class="text-sm font-medium text-gray-900">Aucune notification</p>
        <p class="mt-1 text-xs text-gray-500">Vous êtes à jour !</p>
      </div>

      <div v-else class="max-h-[28rem] overflow-y-auto">
        <div
          v-for="notification in notifications.items"
          :key="notification.id"
          :class="[
            'border-b border-gray-100 px-6 py-4 transition hover:bg-gray-50',
            !notification.is_read && 'bg-blue-50/30',
          ]"
        >
          <component
            :is="notification.action_url ? RouterLink : 'button'"
            :to="notification.action_url"
            :type="notification.action_url ? undefined : 'button'"
            class="block w-full text-left"
            @click="handleItemClick(notification.id, notification.is_read)"
          >
            <div class="flex items-start gap-3">
              <div class="flex-1 space-y-1">
                <div class="flex items-start justify-between gap-2">
                  <p class="text-sm font-semibold text-[#222222]">
                    {{ notification.title ?? 'Notification' }}
                  </p>
                  <span
                    v-if="!notification.is_read"
                    class="mt-0.5 h-2 w-2 shrink-0 rounded-full bg-[#FF385C]"
                  ></span>
                </div>
                <p class="text-sm leading-relaxed text-gray-600">
                  {{ notification.body ?? '' }}
                </p>
                <p class="text-xs text-gray-500">{{ formatDate(notification.created_at) }}</p>
              </div>
            </div>
          </component>
        </div>
      </div>

      <div v-if="notifications.items.length > 0" class="border-t border-gray-100 px-6 py-3">
        <RouterLink
          to="/notifications"
          class="block text-center text-sm font-medium text-gray-600 transition hover:text-[#222222]"
          @click="open = false"
        >
          Voir toutes les notifications
        </RouterLink>
      </div>
    </div>
  </div>
</template>
