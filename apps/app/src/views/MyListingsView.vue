<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { fetchMyListings, type Listing } from '@/services/listings'

const listings = ref<Listing[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)

async function load() {
  isLoading.value = true
  error.value = null

  try {
    listings.value = await fetchMyListings()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger vos annonces.'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="space-y-8">
    <header class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Mes annonces</p>
        <h1 class="text-3xl font-semibold text-slate-900">Vos logements publiés</h1>
      </div>
      <RouterLink
        to="/host/listings/new"
        class="inline-flex rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
      >
        Nouvelle annonce
      </RouterLink>
    </header>

    <div v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
      {{ error }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Chargement des annonces...
    </div>

    <div
      v-else-if="listings.length === 0"
      class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Vous n’avez pas encore publié d’annonce.
    </div>

    <div v-else class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
      <article
        v-for="listing in listings"
        :key="listing.id"
        class="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
      >
        <div class="flex-1 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs uppercase tracking-[0.2em] text-slate-400">
              {{ listing.city }}
            </span>
            <span class="text-xs font-semibold text-slate-700">
              {{ listing.price_per_night }} €/nuit
            </span>
          </div>
          <h2 class="text-lg font-semibold text-slate-900">{{ listing.title }}</h2>
          <p class="text-sm text-slate-500">
            {{ listing.description.slice(0, 120) }}{{ listing.description.length > 120 ? '…' : '' }}
          </p>
        </div>

        <div v-if="listing.images?.length" class="mt-3 overflow-hidden rounded-2xl">
          <img
            :src="listing.images[0].url"
            class="h-40 w-full object-cover transition duration-300 group-hover:scale-105"
            alt=""
          />
        </div>

        <RouterLink
          :to="`/listings/${listing.id}`"
          class="mt-4 inline-flex text-sm font-semibold text-slate-900 group-hover:underline"
        >
          Voir le détail
        </RouterLink>
      </article>
    </div>
  </section>
</template>
