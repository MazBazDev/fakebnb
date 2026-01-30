<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { fetchListing, type Listing } from '@/services/listings'

const route = useRoute()
const listing = ref<Listing | null>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const id = Number(route.params.id)
    listing.value = await fetchListing(id)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger l’annonce.'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="space-y-8">
    <RouterLink to="/listings" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
      ← Retour aux annonces
    </RouterLink>

    <div v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
      {{ error }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Chargement de l’annonce...
    </div>

    <div v-else-if="listing" class="space-y-6">
      <header class="space-y-2">
        <p class="text-sm uppercase tracking-[0.2em] text-slate-500">{{ listing.city }}</p>
        <h1 class="text-3xl font-semibold text-slate-900">{{ listing.title }}</h1>
        <p class="text-sm text-slate-500">{{ listing.address }}</p>
      </header>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <p class="text-lg font-semibold text-slate-900">{{ listing.price_per_night }} €/nuit</p>
          <span class="text-xs uppercase tracking-[0.2em] text-slate-400">
            Id #{{ listing.id }}
          </span>
        </div>

        <p class="mt-4 text-sm text-slate-600">{{ listing.description }}</p>

        <div v-if="listing.rules" class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Règles</p>
          <p class="mt-2">{{ listing.rules }}</p>
        </div>
      </div>
    </div>
  </section>
</template>
