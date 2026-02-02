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

function formatDate(dateString: string | null | undefined) {
  if (!dateString) return ''
  const date = new Date(dateString)
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

onMounted(load)
</script>

<template>
  <section class="mx-auto max-w-4xl space-y-8">
    <header class="space-y-4">
      <Breadcrumbs :items="[{ label: 'Accueil', to: '/' }, { label: 'Messages' }]" />
      <h1 class="text-5xl font-semibold tracking-tight text-[#222222]">Messages</h1>
      <p class="text-lg text-gray-600">Communiquez avec vos hôtes</p>
    </header>

    <!-- Error State -->
    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-6 py-4 text-sm text-red-700">
      {{ error }}
    </div>

    <!-- Loading State -->
    <div
      v-if="isLoading"
      class="flex items-center justify-center rounded-2xl border border-gray-100 bg-gray-50 py-20"
    >
      <div class="text-center">
        <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-[#FF385C]"></div>
        <p class="mt-4 text-sm text-gray-600">Chargement des conversations...</p>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="conversations.length === 0"
      class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-gray-50 py-20"
    >
      <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gray-100">
        <svg class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24">
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
          />
        </svg>
      </div>
      <h3 class="text-xl font-semibold text-[#222222]">Aucune conversation</h3>
      <p class="mt-2 text-sm text-gray-600">Vos messages avec les hôtes apparaîtront ici</p>
      <RouterLink
        to="/listings"
        class="mt-6 rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:shadow-md"
      >
        Explorer les logements
      </RouterLink>
    </div>

    <!-- Conversations List -->
    <div v-else class="space-y-4">
      <article
        v-for="conversation in conversations"
        :key="conversation.id"
        class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
      >
        <RouterLink :to="`/messages/${conversation.id}`" class="block">
          <div class="flex items-start gap-6 p-6">
            <!-- Content -->
            <div class="flex min-w-0 flex-1 flex-col justify-between">
              <div class="space-y-2">
                <div class="flex items-start justify-between gap-4">
                  <div class="min-w-0 flex-1">
                    <h2 class="truncate text-lg font-semibold text-[#222222]">
                      {{ conversation.listing?.title ?? 'Annonce #' + conversation.listing_id }}
                    </h2>
                    <p class="mt-0.5 text-sm text-gray-600">
                      {{ conversation.listing?.city ?? 'Ville inconnue' }}
                    </p>
                  </div>
                  <div class="flex shrink-0 items-center gap-2">
                    <span class="text-xs text-gray-500">
                      {{ formatDate(conversation.last_message?.created_at) }}
                    </span>
                    <svg class="h-5 w-5 text-gray-400 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24">
                      <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"
                      />
                    </svg>
                  </div>
                </div>

                <p v-if="conversation.last_message" class="line-clamp-2 text-sm leading-relaxed text-gray-600">
                  {{ conversation.last_message.body }}
                </p>
                <p v-else class="text-sm italic text-gray-400">Aucun message pour le moment</p>
              </div>
            </div>
          </div>
        </RouterLink>
      </article>
    </div>
  </section>
</template>
