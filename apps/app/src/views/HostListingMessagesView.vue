<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { fetchConversations, type Conversation } from '@/services/conversations'
import { fetchListing, type Listing } from '@/services/listings'
import { useAsyncData, useDateFormat } from '@/composables'
import { PageHeader, LoadingSpinner, EmptyState, AlertMessage } from '@/components/ui'

const route = useRoute()
const { formatRelativeDate } = useDateFormat()

// Computed
const listingId = computed(() => Number(route.params.id))

// Data fetching
const {
  data,
  isLoading,
  error,
  execute: load,
} = useAsyncData<{ listing: Listing | null; conversations: Conversation[] }>(
  async () => {
    const [conversationsData, listingData] = await Promise.all([
      fetchConversations(),
      fetchListing(listingId.value),
    ])

    const filteredConversations = conversationsData.filter(
      (conversation) => conversation.listing_id === listingId.value
    )

    return {
      listing: listingData,
      conversations: filteredConversations,
    }
  },
  {
    defaultValue: { listing: null, conversations: [] },
    errorMessage: 'Impossible de charger les conversations.',
  }
)

// Computed properties
const listing = computed(() => data.value.listing)
const conversations = computed(() => data.value.conversations)

const pageTitle = computed(() =>
  listing.value ? `Messages — ${listing.value.title}` : 'Messages'
)

const breadcrumbs = computed(() => [
  { label: 'Hôte', to: '/host' },
  { label: 'Annonces', to: '/host/listings' },
  { label: listing.value?.title ?? 'Annonce' },
  { label: 'Messages' },
])

onMounted(load)
</script>

<template>
  <section class="mx-auto max-w-4xl space-y-8">
    <PageHeader
      :title="pageTitle"
      subtitle="Conversations avec les voyageurs pour cette annonce"
      :breadcrumbs="breadcrumbs"
    >
      <template v-if="listing" #actions>
        <RouterLink
          :to="`/listings/${listing.id}`"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
            />
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
            />
          </svg>
          Voir l'annonce
        </RouterLink>
      </template>
    </PageHeader>

    <!-- Error Message -->
    <AlertMessage v-if="error" :message="error" type="error" />

    <!-- Loading State -->
    <LoadingSpinner v-if="isLoading" text="Chargement des conversations..." full-container />

    <!-- Empty State -->
    <EmptyState
      v-else-if="conversations.length === 0"
      title="Aucune conversation"
      subtitle="Les messages des voyageurs pour cette annonce apparaîtront ici"
      icon="messages"
      dashed
    />

    <!-- Conversations List -->
    <div v-else class="space-y-4">
      <article
        v-for="conversation in conversations"
        :key="conversation.id"
        class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
      >
        <RouterLink
          :to="`/host/messages/${conversation.id}?listing=${listingId}`"
          class="block"
        >
          <div class="flex items-start gap-4 p-5">
            <!-- Avatar -->
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#E61E4D] to-[#D70466] text-white"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
            </div>

            <!-- Content -->
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                  <h2 class="text-base font-semibold text-[#222222]">
                    Voyageur #{{ conversation.guest_user_id }}
                  </h2>
                  <p
                    v-if="conversation.last_message"
                    class="mt-1 truncate text-sm text-gray-600"
                  >
                    {{ conversation.last_message.body }}
                  </p>
                  <p v-else class="mt-1 text-sm italic text-gray-400">
                    Aucun message pour le moment
                  </p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                  <span
                    v-if="conversation.last_message?.created_at"
                    class="text-xs text-gray-500"
                  >
                    {{ formatRelativeDate(conversation.last_message.created_at) }}
                  </span>
                  <svg
                    class="h-5 w-5 text-gray-400 transition group-hover:translate-x-1 group-hover:text-gray-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
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
            </div>
          </div>
        </RouterLink>
      </article>
    </div>
  </section>
</template>
