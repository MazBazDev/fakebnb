<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { fetchMessages, sendMessage, type Message } from '@/services/messages'
import { getEcho } from '@/services/echo'

const route = useRoute()
const messages = ref<Message[]>([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const form = ref({ body: '' })
const channelName = ref<string | null>(null)

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const id = Number(route.params.id)
    messages.value = await fetchMessages(id)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger les messages.'
  } finally {
    isLoading.value = false
  }
}

async function submit() {
  error.value = null
  isSubmitting.value = true

  try {
    const id = Number(route.params.id)
    const message = await sendMessage(id, form.value.body)
    messages.value = [message, ...messages.value]
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

  echo.private(channelName.value).listen('MessageCreated', (payload: Message) => {
    if (!messages.value.find((item) => item.id === payload.id)) {
      messages.value = [payload, ...messages.value]
    }
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
  <section class="space-y-6">
    <RouterLink to="/messages" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
      ← Retour aux conversations
    </RouterLink>

    <div v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
      {{ error }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Chargement des messages...
    </div>

    <div v-else class="space-y-3">
      <article
        v-for="message in messages"
        :key="message.id"
        class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-600"
      >
        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">
          Utilisateur #{{ message.sender_user_id }}
        </p>
        <p class="mt-2">{{ message.body }}</p>
      </article>
    </div>

    <form
      class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
      @submit.prevent="submit"
    >
      <label class="text-sm font-medium text-slate-700">Nouveau message</label>
      <textarea
        v-model="form.body"
        rows="3"
        class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
        required
      ></textarea>
      <button
        class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="isSubmitting"
        type="submit"
      >
        {{ isSubmitting ? 'Envoi...' : 'Envoyer' }}
      </button>
    </form>
  </section>
</template>
