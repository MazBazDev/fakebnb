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
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
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
      class="relative flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50"
      type="button"
      aria-label="Notifications"
      @click="toggle"
    >
      <span class="text-base">🔔</span>
      <span
        v-if="hasUnread"
        class="absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-semibold text-white"
      >
        {{ notifications.unreadCount }}
      </span>
    </button>

    <div
      v-if="open"
      :class="['absolute z-40 mt-3 w-80 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl', dropdownClasses]"
    >
      <div class="mb-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <p class="text-xs font-semibold text-slate-700">Notifications</p>
          <span v-if="livePulse" class="relative flex h-2 w-2">
            <span
              class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"
            ></span>
            <span class="relative inline-flex h-2 w-2 rounded-full bg-rose-500"></span>
          </span>
        </div>
        <button
          class="text-xs font-semibold text-slate-500 hover:text-slate-800"
          type="button"
          @click="handleMarkAll"
        >
          Tout marquer comme lu
        </button>
      </div>

      <div v-if="notifications.loading" class="text-xs text-slate-500">Chargement…</div>

      <div v-else-if="notifications.items.length === 0" class="text-xs text-slate-500">
        Pas de nouvelles notifications.
      </div>

      <div v-else class="max-h-80 space-y-2 overflow-y-auto">
        <div
          v-for="notification in notifications.items"
          :key="notification.id"
          class="rounded-xl border border-slate-100 bg-slate-50/60 p-3"
        >
          <RouterLink
            v-if="notification.action_url"
            :to="notification.action_url"
            class="block"
            @click="handleItemClick(notification.id, notification.is_read)"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-xs font-semibold text-slate-700">
                  {{ notification.title ?? 'Notification' }}
                </p>
                <p class="text-xs text-slate-500">
                  {{ notification.body ?? '' }}
                </p>
              </div>
              <span class="text-[11px] text-slate-400">{{ formatDate(notification.created_at) }}</span>
            </div>
          </RouterLink>
          <button
            v-else
            class="block w-full text-left"
            type="button"
            @click="handleItemClick(notification.id, notification.is_read)"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-xs font-semibold text-slate-700">
                  {{ notification.title ?? 'Notification' }}
                </p>
                <p class="text-xs text-slate-500">
                  {{ notification.body ?? '' }}
                </p>
              </div>
              <span class="text-[11px] text-slate-400">{{ formatDate(notification.created_at) }}</span>
            </div>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
