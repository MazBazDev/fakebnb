<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { fetchMessages, sendMessage, type Message } from '@/services/messages'
import { getEcho } from '@/services/echo'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const messages = ref<Message[]>([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const form = ref({ body: '' })
const channelName = ref<string | null>(null)
const threadRef = ref<HTMLDivElement | null>(null)
const auth = useAuthStore()
const currentUserId = computed(() => auth.user?.id ?? null)
const messagesPath = computed(() => {
  if (route.meta.layout === 'host') {
    const listingId = route.query.listing
    return listingId ? `/host/listings/${listingId}/messages` : '/host/listings'
  }
  return '/messages'
})

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const id = Number(route.params.id)
    const data = await fetchMessages(id)
    messages.value = data.slice().reverse()
    await nextTick()
    scrollToBottom()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger les messages.'
  } finally {
    isLoading.value = false
  }
}

function scrollToBottom() {
  if (!threadRef.value) return
  threadRef.value.scrollTop = threadRef.value.scrollHeight
}

function appendMessage(message: Message) {
  if (messages.value.find((item) => item.id === message.id)) {
    return
  }
  messages.value = [...messages.value, message]
  nextTick().then(scrollToBottom)
}

async function submit() {
  error.value = null
  isSubmitting.value = true

  try {
    const id = Number(route.params.id)
    const message = await sendMessage(id, form.value.body)
    appendMessage(message)
    form.value.body = ''
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible d’envoyer le message.'
  } finally {
    isSubmitting.value = false
  }
}

onMounted(load)
onMounted(() => {
  const id = Number(route.params.id)
  channelName.value = `conversation.${id}`
  const echo = getEcho()

  echo.private(channelName.value).listen('.MessageCreated', (payload: Message) => {
    appendMessage(payload)
  })
})

onUnmounted(() => {
  const echo = getEcho()
  if (channelName.value) {
    echo.leave(channelName.value)
  }
})
</script>

<template>
  <section class="flex h-[calc(100vh-160px)] flex-col gap-4">
    <header class="flex items-center justify-between">
      <RouterLink
        :to="messagesPath"
        class="text-sm font-semibold text-slate-600 hover:text-slate-900"
      >
        ← Retour aux conversations
      </RouterLink>
      <span class="text-xs uppercase tracking-[0.2em] text-slate-400">Fil de discussion</span>
    </header>

    <div v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
      {{ error }}
    </div>

    <div
      v-if="isLoading"
      class="flex-1 rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Chargement des messages...
    </div>

    <div
      v-else
      ref="threadRef"
      class="flex-1 space-y-4 overflow-y-auto rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
    >
      <div
        v-for="message in messages"
        :key="message.id"
        class="flex flex-col"
        :class="message.sender_user_id === currentUserId ? 'items-end' : 'items-start'"
      >
        <div
          class="max-w-[70%] rounded-2xl px-4 py-3 text-sm shadow-sm"
          :class="
            message.sender_user_id === currentUserId
              ? 'bg-slate-900 text-white'
              : 'bg-slate-100 text-slate-700'
          "
        >
          {{ message.body }}
        </div>
        <p class="mt-1 text-xs text-slate-400">
          {{ message.sender_user_id === currentUserId ? 'Vous' : 'Utilisateur #' + message.sender_user_id }}
        </p>
      </div>
    </div>

    <form
      class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"
      @submit.prevent="submit"
    >
      <div class="flex flex-col gap-3 md:flex-row md:items-end">
        <div class="flex-1">
          <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
            Nouveau message
          </label>
          <textarea
            v-model="form.body"
            rows="2"
            class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm"
            required
          ></textarea>
        </div>
        <button
          class="rounded-full bg-slate-900 px-6 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="isSubmitting"
          type="submit"
        >
          {{ isSubmitting ? 'Envoi...' : 'Envoyer' }}
        </button>
      </div>
    </form>
  </section>
</template>
