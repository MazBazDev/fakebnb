<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { fetchListings, type Listing } from '@/services/listings'

const listings = ref<Listing[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)
const search = ref('')
const selectedCity = ref('')
const minGuests = ref<number | ''>('')
const page = ref(1)
const perPage = 12
const total = ref(0)
const lastPage = ref(1)

const cities = computed(() => {
  const unique = new Set(listings.value.map((listing) => listing.city).filter(Boolean))
  return Array.from(unique).sort()
})

const filteredListings = computed(() => listings.value)

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const response = await fetchListings({
      search: search.value.trim() || undefined,
      city: selectedCity.value || undefined,
      min_guests: minGuests.value ? Number(minGuests.value) : undefined,
      page: page.value,
      per_page: perPage,
    })
    listings.value = response.data
    total.value = response.meta?.total ?? response.data.length
    lastPage.value = response.meta?.last_page ?? 1
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger les annonces.'
  } finally {
    isLoading.value = false
  }
}

function clearFilters() {
  search.value = ''
  selectedCity.value = ''
  minGuests.value = ''
  page.value = 1
  load()
}

onMounted(load)

watch([search, selectedCity, minGuests], () => {
  page.value = 1
  load()
})

function nextPage() {
  if (page.value < lastPage.value) {
    page.value += 1
    load()
  }
}

function prevPage() {
  if (page.value > 1) {
    page.value -= 1
    load()
  }
}
</script>

<template>
  <section class="space-y-8">
    <header class="space-y-3">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Explorer</p>
      <h1 class="text-4xl font-semibold text-slate-900">Trouve ton prochain séjour</h1>
      <p class="max-w-2xl text-sm text-slate-500">
        Filtre par ville, recherche et capacité pour trouver le logement idéal.
      </p>
    </header>

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="grid gap-4 md:grid-cols-3">
        <div class="space-y-2">
          <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
            Recherche
          </label>
          <input
            v-model="search"
            type="text"
            class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm"
            placeholder="Titre, adresse, description..."
          />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
            Ville
          </label>
          <select
            v-model="selectedCity"
            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm"
          >
            <option value="">Toutes les villes</option>
            <option v-for="city in cities" :key="city" :value="city">
              {{ city }}
            </option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
            Capacité min.
          </label>
          <input
            v-model.number="minGuests"
            type="number"
            min="1"
            class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm"
            placeholder="Ex: 2"
          />
        </div>
      </div>

      <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
        <span>{{ total }} annonce(s) trouvée(s)</span>
        <button
          class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
          type="button"
          @click="clearFilters"
        >
          Réinitialiser
        </button>
      </div>
    </div>

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
      v-else-if="filteredListings.length === 0"
      class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Aucune annonce ne correspond à ces filtres.
    </div>

    <div v-else class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
      <article
        v-for="listing in filteredListings"
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
          <p class="text-xs text-slate-400">Capacité {{ listing.guest_capacity }} personnes</p>
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

    <div v-if="lastPage > 1" class="flex items-center justify-between">
      <button
        class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50"
        type="button"
        :disabled="page === 1"
        @click="prevPage"
      >
        Précédent
      </button>
      <span class="text-xs text-slate-500">Page {{ page }} / {{ lastPage }}</span>
      <button
        class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50"
        type="button"
        :disabled="page === lastPage"
        @click="nextPage"
      >
        Suivant
      </button>
    </div>
  </section>
</template>
