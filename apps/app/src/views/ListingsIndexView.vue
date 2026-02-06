<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { fetchListings, type Listing } from '@/services/listings'
import { useAsyncData } from '@/composables'
import { PageHeader, LoadingSpinner, EmptyState, AlertMessage } from '@/components/ui'

const defaultListings: Listing[] = []

const {
  data: listings,
  isLoading,
  error,
  execute: load,
} = useAsyncData<Listing[], Listing[]>(
  async () => {
    const response = await fetchListings()
    return response.data
  },
  {
    defaultValue: defaultListings,
    errorMessage: 'Impossible de charger les annonces.',
  }
)

onMounted(load)
</script>

<template>
  <section class="space-y-8">
    <PageHeader
      title="Explore les logements"
      subtitle="Découvrez nos annonces disponibles"
      :breadcrumbs="[{ label: 'Accueil', to: '/' }, { label: 'Annonces' }]"
    >
      <template #actions>
        <RouterLink
          to="/host/listings/new"
          class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
          Publier une annonce
        </RouterLink>
      </template>
    </PageHeader>

    <AlertMessage v-if="error" :message="error" type="error" />

    <LoadingSpinner v-if="isLoading" text="Chargement des annonces..." full-container />

    <EmptyState
      v-else-if="listings.length === 0"
      title="Aucune annonce publiée"
      subtitle="Soyez le premier à publier une annonce sur notre plateforme"
      icon="listings"
      action-text="Publier une annonce"
      action-to="/host/listings/new"
      dashed
    />

    <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      <article
        v-for="listing in listings"
        :key="listing.id"
        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
      >
        <!-- Image -->
        <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
          <img
            v-if="listing.images?.length"
            :src="listing.images[0]?.url"
            :alt="listing.title"
            class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
            loading="lazy"
          />
          <div
            v-else
            class="flex h-full w-full items-center justify-center text-gray-400"
            aria-label="Aucune image"
          >
            <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" aria-hidden="true">
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
          </div>
        </div>

        <!-- Content -->
        <div class="flex flex-1 flex-col p-5">
          <div class="flex-1 space-y-2">
            <div class="flex items-center justify-between gap-2">
              <span class="text-sm font-medium text-gray-500">{{ listing.city }}</span>
              <span class="text-sm font-semibold text-[#222222]">
                {{ listing.price_per_night }} € / nuit
              </span>
            </div>

            <h2 class="text-lg font-semibold text-[#222222] line-clamp-1">
              {{ listing.title }}
            </h2>

            <p class="text-sm text-gray-600 line-clamp-2">
              {{ listing.description }}
            </p>
          </div>

          <RouterLink
            :to="`/listings/${listing.id}`"
            class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-[#222222] transition hover:text-[#E61E4D]"
          >
            Voir le détail
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </RouterLink>
        </div>
      </article>
    </div>
  </section>
</template>
