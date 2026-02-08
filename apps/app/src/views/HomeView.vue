<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { fetchListings, type Listing } from '@/services/listings'

const router = useRouter()

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
const allCities = ref<string[]>([])
const searchDebounceMs = 300
let searchDebounceTimer: number | null = null

const cities = computed(() => {
  if (allCities.value.length > 0) {
    return allCities.value
  }

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

    if (allCities.value.length === 0) {
      allCities.value = response.meta?.cities ?? []
    }

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

watch(search, () => {
  if (searchDebounceTimer) {
    window.clearTimeout(searchDebounceTimer)
  }
  searchDebounceTimer = window.setTimeout(() => {
    page.value = 1
    load()
  }, searchDebounceMs)
})

watch([selectedCity, minGuests], () => {
  page.value = 1
  load()
})

onUnmounted(() => {
  if (searchDebounceTimer) {
    window.clearTimeout(searchDebounceTimer)
    searchDebounceTimer = null
  }
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
  <section class="home-view">
    <!-- Hero Section -->
    <header class="hero">
      <h1 class="hero-title">
        Partez n'importe où
      </h1>
      <p class="hero-subtitle">
        Découvrez des logements uniques et réservez votre prochaine aventure
      </p>
    </header>

    <!-- Search Filters -->
    <div class="filters-card">
      <div class="filters-grid">
        <div class="filter-group filter-group-large">
          <label class="filter-label">
            Destination
          </label>
          <input
            v-model="search"
            type="text"
            class="filter-input"
            placeholder="Rechercher une destination..."
          />
        </div>
        <div class="filter-group">
          <label class="filter-label">
            Ville
          </label>
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
            <select
              v-model="selectedCity"
              class="filter-select"
            >
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
          <label class="filter-label">
            Voyageurs
          </label>
          <input
            v-model.number="minGuests"
            type="number"
            min="1"
            class="filter-input"
            placeholder="Ajouter"
          />
        </div>
      </div>

      <div class="filters-footer">
        <span class="results-count">
          <strong>{{ total }}</strong>
          {{ total > 1 ? 'logements' : 'logement' }}
        </span>
        <div class="filters-actions">
          <button
            class="clear-filters-btn"
            type="button"
            @click="clearFilters"
          >
            Effacer tout
          </button>
          <button
            class="map-btn"
            type="button"
            @click="router.push('/map')"
          >
            <svg class="map-icon" fill="none" viewBox="0 0 24 24">
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
              />
            </svg>
            Voir la carte
          </button>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-container">
      <div class="loading-content">
        <div class="spinner"></div>
        <p class="loading-text">Chargement...</p>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredListings.length === 0" class="empty-state">
      <svg class="empty-icon" fill="none" viewBox="0 0 24 24">
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
        />
      </svg>
      <p class="empty-title">Aucun logement trouvé</p>
      <p class="empty-subtitle">Essayez de modifier vos critères de recherche</p>
    </div>

    <!-- Listings Grid -->
    <div v-else class="listings-grid">
      <article
        v-for="listing in filteredListings"
        :key="listing.id"
        class="listing-card"
      >
        <RouterLink :to="`/listings/${listing.id}`" class="listing-link">
          <!-- Image Container -->
          <div class="listing-image-container">
            <img
              v-if="listing.images?.length"
              :src="listing.images[0]?.url"
              class="listing-image"
              :alt="listing.title"
            />
            <div v-else class="listing-placeholder">
              <svg class="placeholder-icon" fill="none" viewBox="0 0 24 24">
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
          <div class="listing-content">
            <div class="listing-header">
              <h3 class="listing-city">
                {{ listing.city }}
              </h3>
            </div>
            <p class="listing-title">{{ listing.title }}</p>
            <p class="listing-guests">{{ listing.guest_capacity }} voyageur{{ listing.guest_capacity > 1 ? 's' : '' }}</p>
            <p class="listing-price">
              <span class="price-value">{{ listing.price_per_night }}&nbsp;€</span>
              <span class="price-period"> / nuit</span>
            </p>
          </div>
        </RouterLink>
      </article>
    </div>

    <!-- Pagination -->
    <div v-if="lastPage > 1" class="pagination">
      <button
        class="pagination-btn"
        type="button"
        :disabled="page === 1"
        @click="prevPage"
      >
        Précédent
      </button>
      <span class="pagination-info">
        Page <strong>{{ page }}</strong> sur {{ lastPage }}
      </span>
      <button
        class="pagination-btn"
        type="button"
        :disabled="page === lastPage"
        @click="nextPage"
      >
        Suivant
      </button>
    </div>
  </section>
</template>

<style scoped>
.home-view {
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

/* Hero Section */
.hero {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.hero-title {
  font-size: 3rem;
  font-weight: 600;
  line-height: 1.1;
  letter-spacing: -0.02em;
  color: var(--color-text-primary);
}

@media (min-width: 1024px) {
  .hero-title {
    font-size: 3.75rem;
  }
}

.hero-subtitle {
  max-width: 42rem;
  font-size: 1.125rem;
  color: var(--color-text-secondary);
}

/* Filters Card */
.filters-card {
  padding: 2rem;
  border-radius: 1rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-elevated);
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition-base), background-color var(--transition-base), border-color var(--transition-base);
}

.filters-card:hover {
  box-shadow: var(--shadow-md);
}

.filters-grid {
  display: grid;
  gap: 1.5rem;
}

@media (min-width: 1024px) {
  .filters-grid {
    grid-template-columns: 2fr 1fr 1fr;
  }
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group-large {
  grid-column: span 1;
}

@media (min-width: 1024px) {
  .filter-group-large {
    grid-column: span 2;
  }
}

.filter-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--color-text-tertiary);
}

.filter-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  font-size: 1rem;
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.filter-input::placeholder {
  color: var(--color-text-tertiary);
}

.filter-input:focus {
  outline: none;
  border-color: var(--color-text-primary);
  box-shadow: 0 0 0 2px var(--color-text-primary);
}

/* Select wrapper */
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
}

.select-icon-right {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: var(--color-text-tertiary);
}

.icon {
  width: 1.25rem;
  height: 1.25rem;
}

.filter-select {
  width: 100%;
  padding: 0.75rem 2.5rem 0.75rem 3rem;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  font-size: 1rem;
  cursor: pointer;
  appearance: none;
  transition: border-color var(--transition-fast);
}

.filter-select:hover {
  border-color: var(--color-text-tertiary);
}

.filter-select:focus {
  outline: none;
  border-color: var(--color-text-primary);
  box-shadow: 0 0 0 2px var(--color-text-primary);
}

/* Filters footer */
.filters-footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-border-secondary);
}

.results-count {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.results-count strong {
  font-weight: 600;
  color: var(--color-text-primary);
}

.clear-filters-btn {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-primary);
  text-decoration: underline;
  background: none;
  border: none;
  cursor: pointer;
  transition: color var(--transition-fast), transform var(--transition-fast);
}

.clear-filters-btn:hover {
  color: var(--color-text-secondary);
  transform: scale(1.05);
}

.filters-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.map-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  border-radius: 0.5rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.map-btn:hover {
  border-color: var(--color-text-primary);
  background-color: var(--color-bg-hover);
  transform: scale(1.02);
}

.map-icon {
  width: 1.25rem;
  height: 1.25rem;
}

/* Error message */
.error-message {
  padding: 1rem 1.5rem;
  border-radius: 0.75rem;
  background-color: var(--color-error-bg);
  color: var(--color-error);
  font-size: 0.875rem;
  animation: fade-in 0.3s ease-out;
}

/* Loading container */
.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 5rem 0;
  border-radius: 1rem;
  border: 1px solid var(--color-border-secondary);
  background-color: var(--color-bg-secondary);
}

.loading-content {
  text-align: center;
}

.spinner {
  width: 2.5rem;
  height: 2.5rem;
  margin: 0 auto;
  border: 4px solid var(--color-border-primary);
  border-top-color: var(--color-brand-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading-text {
  margin-top: 1rem;
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

/* Empty state */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem 0;
  border-radius: 1rem;
  border: 2px dashed var(--color-border-primary);
  background-color: var(--color-bg-secondary);
}

.empty-icon {
  width: 3rem;
  height: 3rem;
  margin-bottom: 1rem;
  color: var(--color-text-tertiary);
}

.empty-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.empty-subtitle {
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: var(--color-text-tertiary);
}

/* Listings grid */
.listings-grid {
  display: grid;
  gap: 1.5rem 1.5rem;
}

@media (min-width: 640px) {
  .listings-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .listings-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 1280px) {
  .listings-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 2.5rem 1.5rem;
  }
}

/* Listing card */
.listing-card {
  cursor: pointer;
  transition: transform var(--transition-base);
}

.listing-card:hover {
  transform: translateY(-4px);
}

.listing-link {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.listing-image-container {
  position: relative;
  aspect-ratio: 1 / 1;
  overflow: hidden;
  border-radius: 0.75rem;
  background-color: var(--color-bg-secondary);
}

.listing-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease-out;
}

.listing-card:hover .listing-image {
  transform: scale(1.1);
}

.listing-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  color: var(--color-text-tertiary);
}

.placeholder-icon {
  width: 4rem;
  height: 4rem;
}

/* Listing content */
.listing-content {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.listing-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
}

.listing-city {
  flex: 1;
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--color-text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.listing-title {
  font-size: 0.9375rem;
  color: var(--color-text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.listing-guests {
  font-size: 0.9375rem;
  color: var(--color-text-secondary);
}

.listing-price {
  margin-top: 0.25rem;
}

.price-value {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.price-period {
  font-size: 0.9375rem;
  color: var(--color-text-secondary);
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 2rem;
  border-top: 1px solid var(--color-border-primary);
}

.pagination-btn {
  padding: 0.625rem 1.25rem;
  border-radius: 0.5rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: border-color var(--transition-fast), background-color var(--transition-fast), transform var(--transition-fast);
}

.pagination-btn:hover:not(:disabled) {
  border-color: var(--color-text-primary);
  background-color: var(--color-bg-hover);
  transform: scale(1.05);
}

.pagination-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.pagination-info strong {
  font-weight: 600;
  color: var(--color-text-primary);
}
</style>
