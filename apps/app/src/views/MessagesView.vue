<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import { fetchConversations, type Conversation } from '@/services/conversations'

const conversations = ref<Conversation[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)

async function load() {
  isLoading.value = true
  error.value = null

  try {
    conversations.value = await fetchConversations()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger les conversations.'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="space-y-8">
    <header class="space-y-2">
      <Breadcrumbs :items="[{ label: 'Accueil', to: '/' }, { label: 'Messagerie' }]" />
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Messagerie</p>
      <h1 class="text-3xl font-semibold text-slate-900">Mes conversations</h1>
      <p class="text-sm text-slate-500">Retrouve tes échanges avec les hôtes.</p>
    </header>

    <div v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
      {{ error }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Chargement des conversations...
    </div>

    <div
      v-else-if="conversations.length === 0"
      class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Aucune conversation.
    </div>

    <div v-else class="space-y-3">
      <article
        v-for="conversation in conversations"
        :key="conversation.id"
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-semibold text-slate-800">
              {{ conversation.listing?.title ?? 'Annonce #' + conversation.listing_id }}
            </p>
            <p class="text-xs text-slate-500">
              {{ conversation.listing?.city ?? 'Ville inconnue' }}
            </p>
          </div>
          <RouterLink
            :to="`/messages/${conversation.id}`"
            class="text-xs font-semibold text-slate-700 hover:text-slate-900"
          >
            Ouvrir
          </RouterLink>
        </div>
        <p v-if="conversation.last_message" class="mt-3 text-sm text-slate-500">
          {{ conversation.last_message.body }}
        </p>
      </article>
    </div>
  </section>
</template>
