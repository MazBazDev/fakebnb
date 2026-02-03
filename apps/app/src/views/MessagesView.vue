<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { fetchConversations, type Conversation } from '@/services/conversations'
import { useAsyncData, useDateFormat } from '@/composables'
import { PageHeader, LoadingSpinner, EmptyState, AlertMessage } from '@/components/ui'

const {
  data: conversations,
  isLoading,
  error,
} = useAsyncData<Conversation[]>(() => fetchConversations(), {
  errorMessage: 'Impossible de charger les conversations.',
})

const { formatRelativeDate } = useDateFormat()
</script>

<template>
  <section class="mx-auto max-w-4xl space-y-8">
    <PageHeader
      title="Messages"
      subtitle="Communiquez avec vos hôtes"
      :breadcrumbs="[{ label: 'Accueil', to: '/' }, { label: 'Messages' }]"
    />

    <AlertMessage v-if="error" :message="error" type="error" />

    <LoadingSpinner v-if="isLoading" text="Chargement des conversations..." full-container />

    <EmptyState
      v-else-if="!conversations || conversations.length === 0"
      title="Aucune conversation"
      subtitle="Vos messages avec les hôtes apparaîtront ici"
      icon="messages"
      action-text="Explorer les logements"
      action-to="/listings"
      dashed
    />

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

                <p
                  v-if="conversation.last_message"
                  class="line-clamp-2 text-sm leading-relaxed text-gray-600"
                >
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
