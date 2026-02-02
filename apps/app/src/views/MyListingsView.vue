<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import {
  deleteListing,
  fetchCohostListings,
  fetchMyListings,
  type Listing,
} from '@/services/listings'

const listings = ref<Listing[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)
const deleteBusy = ref<number[]>([])
const deleteError = ref<string | null>(null)
const search = ref('')
const page = ref(1)
const perPage = ref(9)
const lastPage = ref(1)
let searchTimeout: number | null = null
const activeTab = ref<'host' | 'cohost'>('host')
const total = ref(0)

const filteredListings = computed(() => listings.value)

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const response =
      activeTab.value === 'host'
        ? await fetchMyListings({
            search: search.value || undefined,
            page: page.value,
            per_page: perPage.value,
          })
        : await fetchCohostListings({
            search: search.value || undefined,
            page: page.value,
            per_page: perPage.value,
          })
    listings.value = response.data
    lastPage.value = response.meta?.last_page ?? 1
    total.value = response.meta?.total ?? response.data.length
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger vos annonces.'
  } finally {
    isLoading.value = false
  }
}

async function removeListing(listing: Listing) {
  if (deleteBusy.value.includes(listing.id)) return
  if (!confirm(`Êtes-vous sûr de vouloir supprimer "${listing.title}" ?`)) return

  deleteBusy.value = [...deleteBusy.value, listing.id]
  deleteError.value = null

  try {
    await deleteListing(listing.id)
    listings.value = listings.value.filter((item) => item.id !== listing.id)
    if (listings.value.length === 0 && page.value > 1) {
      page.value -= 1
      await load()
    }
  } catch (err) {
    deleteError.value = err instanceof Error ? err.message : 'Impossible de supprimer l\'annonce.'
  } finally {
    deleteBusy.value = deleteBusy.value.filter((id) => id !== listing.id)
  }
}

onMounted(load)

watch(search, () => {
  page.value = 1
  if (searchTimeout) {
    window.clearTimeout(searchTimeout)
  }
  searchTimeout = window.setTimeout(() => {
    load()
  }, 350)
})

watch(page, () => {
  load()
})

watch(activeTab, () => {
  page.value = 1
  load()
})
</script>

<template>
  <section class="space-y-8">
    <header class="space-y-4">
      <Breadcrumbs :items="[{ label: 'Hôte', to: '/host' }, { label: 'Annonces' }]" />
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="space-y-2">
          <h1 class="text-5xl font-semibold tracking-tight text-[#222222]">Vos annonces</h1>
          <p class="text-lg text-gray-600">Gérez vos logements et co-hébergements</p>
        </div>
        <RouterLink
          v-if="activeTab === 'host'"
          to="/host/listings/new"
          class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:shadow-md"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24">
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
          Créer une annonce
        </RouterLink>
      </div>
    </header>

    <!-- Error Messages -->
    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-6 py-4 text-sm text-red-700">
      {{ error }}
    </div>
    <div v-if="deleteError" class="rounded-xl border border-red-200 bg-red-50 px-6 py-4 text-sm text-red-700">
      {{ deleteError }}
    </div>

    <!-- Filters Card -->
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <!-- Tabs -->
        <div class="inline-flex rounded-xl bg-gray-100 p-1">
          <button
            class="rounded-lg px-6 py-2.5 text-sm font-semibold transition"
            :class="
              activeTab === 'host'
                ? 'bg-white text-[#222222] shadow-sm'
                : 'text-gray-600 hover:text-[#222222]'
            "
            type="button"
            @click="activeTab = 'host'"
          >
            Mes annonces
          </button>
          <button
            class="rounded-lg px-6 py-2.5 text-sm font-semibold transition"
            :class="
              activeTab === 'cohost'
                ? 'bg-white text-[#222222] shadow-sm'
                : 'text-gray-600 hover:text-[#222222]'
            "
            type="button"
            @click="activeTab = 'cohost'"
          >
            Co-hébergement
          </button>
        </div>

        <!-- Stats -->
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2 text-sm text-gray-600">
            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
              />
            </svg>
            <span><strong class="font-semibold text-[#222222]">{{ total }}</strong> {{ total > 1 ? 'annonces' : 'annonce' }}</span>
          </div>
        </div>
      </div>

      <!-- Search -->
      <div class="mt-6 border-t border-gray-100 pt-6">
        <label class="block text-sm font-semibold text-[#222222]">Rechercher</label>
        <div class="relative mt-2">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
          </div>
          <input
            v-model="search"
            type="search"
            placeholder="Titre, ville, description..."
            class="w-full rounded-lg border border-gray-300 py-3 pl-12 pr-4 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div
      v-if="isLoading"
      class="flex items-center justify-center rounded-2xl border border-gray-100 bg-gray-50 py-20"
    >
      <div class="text-center">
        <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-[#FF385C]"></div>
        <p class="mt-4 text-sm text-gray-600">Chargement des annonces...</p>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="filteredListings.length === 0"
      class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-gray-50 py-20"
    >
      <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gray-100">
        <svg class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24">
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
          />
        </svg>
      </div>
      <h3 class="text-xl font-semibold text-[#222222]">Aucune annonce trouvée</h3>
      <p class="mt-2 text-sm text-gray-600">
        {{ search ? 'Essayez de modifier vos critères de recherche' : 'Créez votre première annonce pour commencer' }}
      </p>
      <RouterLink
        v-if="!search && activeTab === 'host'"
        to="/host/listings/new"
        class="mt-6 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:shadow-md"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24">
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        Créer une annonce
      </RouterLink>
    </div>

    <!-- Listings Grid -->
    <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <article
        v-for="listing in filteredListings"
        :key="listing.id"
        class="group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
      >
        <!-- Image -->
        <div class="relative aspect-square overflow-hidden bg-gray-100">
          <img
            v-if="listing.images?.length"
            :src="listing.images[0]?.url"
            class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
            :alt="listing.title"
          />
          <div v-else class="flex h-full w-full items-center justify-center text-gray-400">
            <svg class="h-16 w-16" fill="none" viewBox="0 0 24 24">
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
          </div>

          <!-- Badge -->
          <div class="absolute right-3 top-3">
            <span
              v-if="activeTab === 'cohost'"
              class="inline-flex items-center gap-1 rounded-full bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white shadow-lg"
            >
              <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                />
              </svg>
              Co-hôte
            </span>
          </div>
        </div>

        <!-- Content -->
        <div class="flex flex-1 flex-col p-6">
          <div class="flex-1 space-y-3">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <h2 class="truncate text-lg font-semibold text-[#222222]">{{ listing.title }}</h2>
                <p class="mt-1 text-sm text-gray-600">{{ listing.city }}</p>
              </div>
              <div class="text-right">
                <p class="text-lg font-semibold text-[#222222]">{{ listing.price_per_night }} €</p>
                <p class="text-xs text-gray-500">/ nuit</p>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600">
              <div class="flex items-center gap-1">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                  />
                </svg>
                <span>{{ listing.guest_capacity }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="mt-6 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
            <RouterLink
              :to="`/listings/${listing.id}`"
              class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 transition hover:text-[#222222]"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
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
              Voir
            </RouterLink>

            <RouterLink
              v-if="
                activeTab === 'host' ||
                (listing.cohost_permissions && listing.cohost_permissions.can_read_conversations)
              "
              :to="`/host/listings/${listing.id}/messages`"
              class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 transition hover:text-[#222222]"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                />
              </svg>
              Messages
            </RouterLink>

            <RouterLink
              v-if="
                activeTab === 'host' ||
                (listing.cohost_permissions && listing.cohost_permissions.can_edit_listings)
              "
              :to="`/host/listings/${listing.id}/edit`"
              class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 transition hover:text-[#222222]"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
              Modifier
            </RouterLink>

            <button
              v-if="activeTab === 'host'"
              class="ml-auto inline-flex items-center gap-1.5 text-sm font-medium text-red-600 transition hover:text-red-700 disabled:opacity-40"
              type="button"
              :disabled="deleteBusy.includes(listing.id)"
              @click="removeListing(listing)"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
              </svg>
              {{ deleteBusy.includes(listing.id) ? 'Suppression...' : 'Supprimer' }}
            </button>
          </div>
        </div>
      </article>
    </div>

    <!-- Pagination -->
    <div
      v-if="lastPage > 1"
      class="flex items-center justify-between border-t border-gray-200 pt-8"
    >
      <button
        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
        type="button"
        :disabled="page <= 1"
        @click="page -= 1"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 19l-7-7 7-7"
          />
        </svg>
        Précédent
      </button>
      <span class="text-sm text-gray-600">
        Page <strong class="font-semibold text-[#222222]">{{ page }}</strong> sur {{ lastPage }}
      </span>
      <button
        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
        type="button"
        :disabled="page >= lastPage"
        @click="page += 1"
      >
        Suivant
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5l7 7-7 7"
          />
        </svg>
      </button>
    </div>
  </section>
</template>
