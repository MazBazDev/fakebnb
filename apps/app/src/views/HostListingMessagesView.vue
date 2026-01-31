<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import { fetchConversations, type Conversation } from '@/services/conversations'
import { fetchListing, type Listing } from '@/services/listings'

const route = useRoute()
const conversations = ref<Conversation[]>([])
const listing = ref<Listing | null>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)

const listingId = computed(() => Number(route.params.id))

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const [conversationsData, listingData] = await Promise.all([
      fetchConversations(),
      fetchListing(listingId.value),
    ])
    listing.value = listingData
    conversations.value = conversationsData.filter(
      (conversation) => conversation.listing_id === listingId.value
    )
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
      <Breadcrumbs
        :items="[
          { label: 'Hôte', to: '/host' },
          { label: 'Annonces', to: '/host/listings' },
          { label: listing?.title ?? 'Annonce' },
          { label: 'Messagerie' },
        ]"
      />
      <h1 class="text-3xl font-semibold text-slate-900">
        Conversations — {{ listing?.title ?? 'Annonce' }}
      </h1>
      <p class="text-sm text-slate-500">Messages liés à cette annonce.</p>
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
      Aucune conversation pour cette annonce.
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
              Voyageur #{{ conversation.guest_user_id }}
            </p>
            <p class="text-xs text-slate-500">
              {{ conversation.last_message?.body ?? 'Aucun message' }}
            </p>
          </div>
          <RouterLink
            :to="`/host/messages/${conversation.id}?listing=${listingId}`"
            class="text-xs font-semibold text-slate-700 hover:text-slate-900"
          >
            Ouvrir
          </RouterLink>
        </div>
      </article>
    </div>

  </section>
</template>
