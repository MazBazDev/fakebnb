<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { fetchHostConversations, type Conversation } from '@/services/conversations'
import { fetchCohostListings, fetchMyListings, type Listing } from '@/services/listings'
import { useAsyncData, useDateFormat } from '@/composables'
import { PageHeader, LoadingSpinner, EmptyState, AlertMessage } from '@/components/ui'

const { formatRelativeDate } = useDateFormat()

const listings = ref<Listing[]>([])
const selectedListingId = ref<number | ''>('')

const {
  data: conversations,
  isLoading,
  error,
  execute: reload,
} = useAsyncData<Conversation[]>(
  async () => {
    const listingId = selectedListingId.value ? Number(selectedListingId.value) : undefined
    return fetchHostConversations(listingId)
  },
  { errorMessage: 'Impossible de charger les conversations.' }
)

const listingOptions = computed(() => {
  const unique = new Map<number, Listing>()
  listings.value.forEach((listing) => unique.set(listing.id, listing))
  return Array.from(unique.values())
})

async function loadListings() {
  const [hostListings, cohostListings] = await Promise.all([
    fetchMyListings({ per_page: 100 }),
    fetchCohostListings({ per_page: 100 }),
  ])
  listings.value = [...(hostListings.data ?? []), ...(cohostListings.data ?? [])]
}

watch(selectedListingId, () => {
  reload()
})

onMounted(async () => {
  await loadListings()
  await reload()
})
</script>

<template>
  <section class="mx-auto max-w-4xl space-y-8">
    <PageHeader
      title="Messages"
      subtitle="Conversations avec les voyageurs"
      :breadcrumbs="[{ label: 'Hôte', to: '/host' }, { label: 'Messages' }]"
    >
      <template #actions>
        <div class="flex items-center gap-3">
          <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Annonce</label>
          <select
            v-model="selectedListingId"
            class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700"
          >
            <option value="">Toutes</option>
            <option v-for="listing in listingOptions" :key="listing.id" :value="listing.id">
              {{ listing.title }} — {{ listing.city }}
            </option>
          </select>
        </div>
      </template>
    </PageHeader>

    <AlertMessage v-if="error" :message="error" type="error" />

    <LoadingSpinner v-if="isLoading" text="Chargement des conversations..." full-container />

    <EmptyState
      v-else-if="!conversations || conversations.length === 0"
      title="Aucune conversation"
      subtitle="Les messages des voyageurs apparaîtront ici"
      icon="messages"
      dashed
    />

    <div v-else class="space-y-4">
      <article
        v-for="conversation in conversations"
        :key="conversation.id"
        class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
      >
        <RouterLink
          :to="`/host/messages/${conversation.id}?listing=${conversation.listing_id}`"
          class="block"
        >
          <div class="flex items-start gap-6 p-6">
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
                      {{ formatRelativeDate(conversation.last_message?.created_at) }}
                    </span>
                    <svg
                      class="h-5 w-5 text-gray-400 transition group-hover:translate-x-1"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
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

                <p class="text-sm text-gray-500">
                  {{ conversation.last_message?.body ?? 'Aucun message pour le moment.' }}
                </p>
              </div>
            </div>
          </div>
        </RouterLink>
      </article>
    </div>
  </section>
</template>
