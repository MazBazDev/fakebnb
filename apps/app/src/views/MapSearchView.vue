<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import maplibregl from 'maplibre-gl'
import 'maplibre-gl/dist/maplibre-gl.css'
import { fetchListings, type Listing } from '@/services/listings'
import { useTheme } from '@/composables/useTheme'

const { isDark } = useTheme()

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
    const markerColor = isDark.value ? '#ff385c' : '#0f172a'
    const marker = new maplibregl.Marker({ color: markerColor })
      .setLngLat([listing.longitude, listing.latitude])
      .setPopup(
        new maplibregl.Popup({ offset: 16 }).setHTML(
          `<div style="font-size:12px;font-weight:600;color:#222;">${listing.title}</div>`
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

// Rafraîchir les markers quand le thème change
watch(isDark, () => {
  refreshMarkers()
})
</script>

<template>
  <section class="map-search-view">
    <!-- Header -->
    <header class="page-header">
      <div class="header-content">
        <Breadcrumbs :items="[{ label: 'Accueil', to: '/' }, { label: 'Carte' }]" />
        <p class="page-label">Carte</p>
        <h1 class="page-title">Recherche sur la carte</h1>
        <p class="page-subtitle">Déplacez la carte pour filtrer les annonces.</p>
      </div>
      <RouterLink to="/" class="back-btn">
        <svg class="back-icon" fill="none" viewBox="0 0 24 24">
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 6h16M4 10h16M4 14h16M4 18h16"
          />
        </svg>
        Voir en liste
      </RouterLink>
    </header>

    <!-- Filters -->
    <div class="filters-card">
      <div class="filters-grid">
        <div class="filter-group">
          <label class="filter-label">Recherche</label>
          <input
            v-model="search"
            type="text"
            class="filter-input"
            placeholder="Titre, adresse, description..."
          />
        </div>
        <div class="filter-group">
          <label class="filter-label">Ville</label>
          <div class="select-wrapper">
            <div class="select-icon-left">
              <svg class="icon" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                />
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
            </div>
            <select v-model="selectedCity" class="filter-select-with-icon">
              <option value="">Toutes les villes</option>
              <option v-for="city in cities" :key="city" :value="city">
                {{ city }}
              </option>
            </select>
            <div class="select-icon-right">
              <svg class="icon" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 9l-7 7-7-7"
                />
              </svg>
            </div>
          </div>
        </div>
        <div class="filter-group">
          <label class="filter-label">Capacité min.</label>
          <input
            v-model.number="minGuests"
            type="number"
            min="1"
            class="filter-input"
            placeholder="Ex: 2"
          />
        </div>
      </div>

      <div class="filters-footer">
        <span class="results-count">{{ total }} annonce(s) trouvée(s)</span>
        <button class="reset-btn" type="button" @click="clearFilters">
          Réinitialiser
        </button>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <!-- Map + Listings Grid -->
    <div class="content-grid">
      <!-- Map Container -->
      <div class="map-container">
        <div ref="mapContainer" class="map"></div>
      </div>

      <!-- Listings Panel -->
      <div class="listings-panel">
        <!-- Loading -->
        <div v-if="isLoading" class="loading-card">
          <div class="spinner"></div>
          <span>Chargement des annonces...</span>
        </div>

        <!-- Empty -->
        <div v-else-if="listings.length === 0" class="empty-card">
          <svg class="empty-icon" fill="none" viewBox="0 0 24 24">
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
            />
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
            />
          </svg>
          <p>Aucune annonce dans cette zone.</p>
        </div>

        <!-- Listings -->
        <div v-else class="listings-list">
          <article v-for="listing in listings" :key="listing.id" class="listing-card">
            <div class="listing-header">
              <div class="listing-info">
                <p class="listing-city">{{ listing.city }}</p>
                <h2 class="listing-title">{{ listing.title }}</h2>
              </div>
              <span class="listing-price">
                {{ listing.price_per_night }} €<span class="price-suffix">/nuit</span>
              </span>
            </div>
            <p class="listing-description">
              {{ listing.description.slice(0, 90) }}{{ listing.description.length > 90 ? '…' : '' }}
            </p>
            <RouterLink :to="`/listings/${listing.id}`" class="listing-link">
              Voir le détail
              <svg class="link-arrow" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </RouterLink>
          </article>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.map-search-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Header */
.page-header {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

@media (min-width: 768px) {
  .page-header {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.page-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  color: var(--color-text-tertiary);
}

.page-title {
  font-size: 1.875rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.page-subtitle {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  color: var(--color-text-secondary);
  font-size: 0.75rem;
  font-weight: 600;
  transition: all var(--transition-fast);
}

.back-btn:hover {
  border-color: var(--color-text-primary);
  color: var(--color-text-primary);
  background-color: var(--color-bg-hover);
}

.back-icon {
  width: 1rem;
  height: 1rem;
}

/* Filters */
.filters-card {
  padding: 1.25rem;
  border-radius: 1.5rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-elevated);
  box-shadow: var(--shadow-sm);
}

.filters-grid {
  display: grid;
  gap: 1rem;
}

@media (min-width: 768px) {
  .filters-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  color: var(--color-text-tertiary);
}

.filter-input {
  width: 100%;
  padding: 0.5rem 1rem;
  border-radius: 1rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  font-size: 0.875rem;
  transition: border-color var(--transition-fast);
}

.filter-input::placeholder {
  color: var(--color-text-tertiary);
}

.filter-input:focus {
  outline: none;
  border-color: var(--color-brand-primary);
}

/* Select wrapper avec icônes */
.select-wrapper {
  position: relative;
}

.select-icon-left {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: var(--color-text-tertiary);
  transition: color var(--transition-fast);
}

.select-icon-right {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: var(--color-text-tertiary);
  transition: color var(--transition-fast);
}

.icon {
  width: 1.25rem;
  height: 1.25rem;
}

.filter-select-with-icon {
  width: 100%;
  padding: 0.5rem 2.5rem 0.5rem 3rem;
  border-radius: 1rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  font-size: 0.875rem;
  cursor: pointer;
  appearance: none;
  transition: border-color var(--transition-fast);
}

.filter-select-with-icon:hover {
  border-color: var(--color-text-tertiary);
}

.filter-select-with-icon:focus {
  outline: none;
  border-color: var(--color-brand-primary);
}

.select-wrapper:hover .select-icon-left,
.select-wrapper:hover .select-icon-right {
  color: var(--color-text-secondary);
}

.select-wrapper:focus-within .select-icon-left,
.select-wrapper:focus-within .select-icon-right {
  color: var(--color-brand-primary);
}

.filters-footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border-secondary);
}

.results-count {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.reset-btn {
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  color: var(--color-text-secondary);
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.reset-btn:hover {
  border-color: var(--color-text-primary);
  color: var(--color-text-primary);
  background-color: var(--color-bg-hover);
}

/* Error */
.error-message {
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  background-color: var(--color-error-bg);
  color: var(--color-error);
  font-size: 0.875rem;
}

/* Content Grid */
.content-grid {
  display: grid;
  gap: 1.5rem;
}

@media (min-width: 1024px) {
  .content-grid {
    grid-template-columns: 1.2fr 1fr;
  }
}

/* Map */
.map-container {
  overflow: hidden;
  border-radius: 1.5rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-elevated);
  box-shadow: var(--shadow-sm);
}

.map {
  width: 100%;
  height: 520px;
}

/* Listings Panel */
.listings-panel {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  max-height: 520px;
  overflow-y: auto;
}

.loading-card,
.empty-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem;
  border-radius: 1rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-elevated);
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  text-align: center;
}

.empty-card {
  border-style: dashed;
}

.spinner {
  width: 1.5rem;
  height: 1.5rem;
  border: 3px solid var(--color-border-primary);
  border-top-color: var(--color-brand-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.empty-icon {
  width: 2.5rem;
  height: 2.5rem;
  color: var(--color-text-tertiary);
}

/* Listings */
.listings-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.listing-card {
  padding: 1rem;
  border-radius: 1rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-elevated);
  transition: box-shadow var(--transition-fast), border-color var(--transition-fast);
}

.listing-card:hover {
  border-color: var(--color-border-secondary);
  box-shadow: var(--shadow-md);
}

.listing-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
}

.listing-info {
  flex: 1;
  min-width: 0;
}

.listing-city {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  color: var(--color-text-tertiary);
}

.listing-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.listing-price {
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--color-text-primary);
  white-space: nowrap;
}

.price-suffix {
  font-weight: 400;
  color: var(--color-text-secondary);
}

.listing-description {
  margin-top: 0.5rem;
  font-size: 0.75rem;
  line-height: 1.5;
  color: var(--color-text-secondary);
}

.listing-link {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  margin-top: 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-primary);
  transition: color var(--transition-fast);
}

.listing-link:hover {
  color: var(--color-brand-primary);
}

.link-arrow {
  width: 0.875rem;
  height: 0.875rem;
  transition: transform var(--transition-fast);
}

.listing-link:hover .link-arrow {
  transform: translateX(2px);
}
</style>
