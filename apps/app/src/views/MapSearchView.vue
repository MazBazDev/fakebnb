<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import maplibregl from 'maplibre-gl'
import 'maplibre-gl/dist/maplibre-gl.css'
import { fetchListings, type Listing } from '@/services/listings'

const listings = ref<Listing[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)
const search = ref('')
const selectedCity = ref('')
const minGuests = ref<number | ''>('')
const total = ref(0)

const mapContainer = ref<HTMLDivElement | null>(null)
const map = ref<any>(null)
const markers = ref<Array<{ remove: () => void }>>([])
let moveTimeout: number | null = null

const paddingKm = 5

const cities = computed(() => {
  const unique = new Set(listings.value.map((listing) => listing.city).filter(Boolean))
  return Array.from(unique).sort()
})

function boundsString() {
  if (!map.value) return null
  const bounds = map.value.getBounds()
  const sw = bounds.getSouthWest()
  const ne = bounds.getNorthEast()
  return `${sw.lng},${sw.lat},${ne.lng},${ne.lat}`
}

async function load() {
  const mapInstance = map.value
  if (!mapInstance) return
  isLoading.value = true
  error.value = null

  try {
    const response = await fetchListings({
      search: search.value.trim() || undefined,
      city: selectedCity.value || undefined,
      min_guests: minGuests.value ? Number(minGuests.value) : undefined,
      bounds: boundsString() ?? undefined,
      padding_km: paddingKm,
      per_page: 100,
    })
    listings.value = response.data
    total.value = response.meta?.total ?? response.data.length
    refreshMarkers()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger les annonces.'
  } finally {
    isLoading.value = false
  }
}

function refreshMarkers() {
  markers.value.forEach((marker) => marker.remove())
  markers.value = []

  const mapInstance = map.value
  if (!mapInstance) return

  listings.value.forEach((listing) => {
    if (listing.latitude == null || listing.longitude == null) return
    const marker = new maplibregl.Marker({ color: '#0f172a' })
      .setLngLat([listing.longitude, listing.latitude])
      .setPopup(
        new maplibregl.Popup({ offset: 16 }).setHTML(
          `<div style="font-size:12px;font-weight:600;">${listing.title}</div>`
        )
      )
      .addTo(mapInstance) as unknown as { remove: () => void }

    markers.value.push(marker)
  })
}

function scheduleLoad() {
  if (moveTimeout) {
    window.clearTimeout(moveTimeout)
  }
  moveTimeout = window.setTimeout(() => {
    load()
  }, 300)
}

function clearFilters() {
  search.value = ''
  selectedCity.value = ''
  minGuests.value = ''
  load()
}

onMounted(() => {
  if (!mapContainer.value) return
  map.value = new maplibregl.Map({
    container: mapContainer.value,
    style: {
      version: 8,
      sources: {
        osm: {
          type: 'raster',
          tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
          tileSize: 256,
          maxzoom: 19,
        },
      },
      layers: [
        {
          id: 'osm-tiles',
          type: 'raster',
          source: 'osm',
        },
      ],
    },
    center: [2.3522, 48.8566],
    zoom: 5,
    maxZoom: 18,
  })

  map.value.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right')
  map.value.on('moveend', scheduleLoad)
  map.value.on('load', load)
})

onUnmounted(() => {
  if (moveTimeout) {
    window.clearTimeout(moveTimeout)
  }
  markers.value.forEach((marker) => marker.remove())
  map.value?.remove()
})

watch([search, selectedCity, minGuests], () => {
  load()
})
</script>

<template>
  <section class="space-y-6">
    <header class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <Breadcrumbs :items="[{ label: 'Accueil', to: '/' }, { label: 'Carte' }]" />
        <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Carte</p>
        <h1 class="text-3xl font-semibold text-slate-900">Recherche sur la carte</h1>
        <p class="text-sm text-slate-500">Bouge la carte pour filtrer les annonces.</p>
      </div>
      <RouterLink
        to="/"
        class="inline-flex rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
      >
        Voir en liste
      </RouterLink>
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

    <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
      <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div ref="mapContainer" class="h-[520px] w-full"></div>
      </div>

      <div class="space-y-3">
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
          Aucune annonce dans cette zone.
        </div>
        <div v-else class="space-y-3">
          <article
            v-for="listing in listings"
            :key="listing.id"
            class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-600"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">
                  {{ listing.city }}
                </p>
                <h2 class="text-base font-semibold text-slate-900">{{ listing.title }}</h2>
              </div>
              <span class="text-xs font-semibold text-slate-700">
                {{ listing.price_per_night }} €/nuit
              </span>
            </div>
            <p class="mt-2 text-xs text-slate-500">
              {{ listing.description.slice(0, 90) }}{{ listing.description.length > 90 ? '…' : '' }}
            </p>
            <RouterLink
              :to="`/listings/${listing.id}`"
              class="mt-3 inline-flex text-xs font-semibold text-slate-900 hover:underline"
            >
              Voir le détail
            </RouterLink>
          </article>
        </div>
      </div>
    </div>
  </section>
</template>
