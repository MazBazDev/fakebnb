<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import { useAuthStore } from '@/stores/auth'
import { fetchHostStats, type HostStats } from '@/services/hostStats'

const auth = useAuthStore()
const displayName = computed(() => auth.user?.name ?? auth.user?.email ?? 'Utilisateur')
const stats = ref<HostStats | null>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)

const upcomingArrivals = computed(() => stats.value?.upcoming_arrivals ?? [])
const recentBookings = computed(() => stats.value?.recent_requests ?? [])
const bookingSeries = computed(() => stats.value?.series.booking_counts ?? [])
const payoutSeries = computed(() => stats.value?.series.payout_totals ?? [])

function listingLabel(listingId: number) {
  const listing = stats.value?.recent_requests.find((item) => item.listing_id === listingId)?.listing
  return listing ? `${listing.title} — ${listing.city}` : `Annonce #${listingId}`
}

function formatAmount(value: number) {
  return (value / 100).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' })
}

function sparklinePoints(values: number[], width = 120, height = 32, padding = 4) {
  if (values.length === 0) return ''
  const max = Math.max(...values, 1)
  const min = Math.min(...values, 0)
  const range = max - min || 1
  const step = values.length > 1 ? (width - padding * 2) / (values.length - 1) : 0
  return values
    .map((value, index) => {
      const x = padding + index * step
      const y = height - padding - ((value - min) / range) * (height - padding * 2)
      return `${x},${y}`
    })
    .join(' ')
}

function latestSeriesValue(values: number[]) {
  return values.length ? values[values.length - 1] ?? 0 : 0
}

async function loadDashboard() {
  isLoading.value = true
  error.value = null

  try {
    stats.value = await fetchHostStats()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger les statistiques.'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <section class="space-y-12">
    <header class="space-y-4">
      <Breadcrumbs :items="[{ label: 'Hôte', to: '/host' }, { label: 'Tableau de bord' }]" />
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="space-y-2">
          <h1 class="text-5xl font-semibold tracking-tight text-[#222222]">
            Bienvenue, {{ displayName }}
          </h1>
          <p class="text-lg text-gray-600">Gérez vos annonces et réservations</p>
        </div>
        <RouterLink
          to="/host/listings/new"
          class="rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:shadow-md"
        >
          Créer une annonce
        </RouterLink>
      </div>
    </header>

    <!-- Loading State -->
    <div
      v-if="isLoading"
      class="flex items-center justify-center rounded-2xl border border-gray-100 bg-gray-50 py-20"
    >
      <div class="text-center">
        <div
          class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-[#FF385C]"
        ></div>
        <p class="mt-4 text-sm text-gray-600">Chargement des statistiques...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 px-6 py-4 text-sm text-red-700">
      {{ error }}
    </div>

    <!-- Dashboard Content -->
    <div v-else class="space-y-8">
      <!-- Stats Cards Grid -->
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card 1: Annonces -->
        <div class="group overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-slate-800">
          <div class="flex items-start justify-between">
            <div class="space-y-1">
              <p class="text-sm font-medium text-gray-500">Annonces actives</p>
              <p class="text-4xl font-semibold tracking-tight text-[#222222]">
                {{ stats?.listings_count ?? 0 }}
              </p>
            </div>
            <div class="rounded-lg bg-blue-50 p-3">
              <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                />
              </svg>
            </div>
          </div>
          <RouterLink
            to="/host/listings"
            class="mt-4 inline-flex items-center text-sm font-medium text-gray-600 transition hover:text-[#222222]"
          >
            Voir tout
            <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24">
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

        <!-- Card 2: En attente -->
        <div class="group overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-slate-800">
          <div class="flex items-start justify-between">
            <div class="space-y-1">
              <p class="text-sm font-medium text-gray-500">En attente</p>
              <p class="text-4xl font-semibold tracking-tight text-[#222222]">
                {{ stats?.pending_count ?? 0 }}
              </p>
            </div>
            <div class="rounded-lg bg-amber-50 p-3">
              <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
          </div>
          <p class="mt-4 text-sm text-gray-600">Demandes à traiter</p>
        </div>

        <!-- Card 3: Paiements -->
        <div class="group overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
          <div class="flex items-start justify-between">
            <div class="space-y-1">
              <p class="text-sm font-medium text-gray-500">À payer</p>
              <p class="text-4xl font-semibold tracking-tight text-[#222222]">
                {{ stats?.awaiting_payment_count ?? 0 }}
              </p>
            </div>
            <div class="rounded-lg bg-purple-50 p-3">
              <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                />
              </svg>
            </div>
          </div>
          <p class="mt-4 text-sm text-gray-600">En attente de paiement</p>
        </div>

        <!-- Card 4: Revenus -->
        <div class="group overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
          <div class="flex items-start justify-between">
            <div class="space-y-1">
              <p class="text-sm font-medium text-gray-500">Revenus totaux</p>
              <p class="text-4xl font-semibold tracking-tight text-[#222222]">
                {{ formatAmount(stats?.total_payout ?? 0) }}
              </p>
            </div>
            <div class="rounded-lg bg-green-50 p-3">
              <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
          </div>
          <p class="mt-4 text-sm text-gray-600">Paiements confirmés</p>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid gap-6 lg:grid-cols-2">
        <!-- Bookings Chart -->
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
          <div class="mb-6 flex items-start justify-between">
            <div class="space-y-1">
              <h2 class="text-lg font-semibold text-[#222222]">Réservations</h2>
              <p class="text-sm text-gray-600">Évolution mensuelle</p>
            </div>
            <div class="rounded-lg bg-blue-50 px-3 py-1">
              <p class="text-2xl font-bold text-blue-600">{{ latestSeriesValue(bookingSeries) }}</p>
              <p class="text-xs text-blue-600">Ce mois</p>
            </div>
          </div>
          <div class="flex h-32 items-end justify-center">
            <svg width="100%" height="128" viewBox="0 0 400 128" class="w-full">
              <defs>
                <linearGradient id="bookingGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" style="stop-color: rgb(59, 130, 246); stop-opacity: 0.5" />
                  <stop offset="100%" style="stop-color: rgb(59, 130, 246); stop-opacity: 0" />
                </linearGradient>
              </defs>
              <polyline
                :points="sparklinePoints(bookingSeries, 400, 128, 8) + ' 400,128 0,128'"
                fill="url(#bookingGradient)"
              />
              <polyline
                :points="sparklinePoints(bookingSeries, 400, 128, 8)"
                fill="none"
                stroke="rgb(59, 130, 246)"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
        </div>

        <!-- Revenue Chart -->
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
          <div class="mb-6 flex items-start justify-between">
            <div class="space-y-1">
              <h2 class="text-lg font-semibold text-[#222222]">Revenus attendus</h2>
              <p class="text-sm text-gray-600">Évolution mensuelle</p>
            </div>
            <div class="rounded-lg bg-green-50 px-3 py-1">
              <p class="text-2xl font-bold text-green-600">
                {{ formatAmount(latestSeriesValue(payoutSeries)) }}
              </p>
              <p class="text-xs text-green-600">Ce mois</p>
            </div>
          </div>
          <div class="flex h-32 items-end justify-center">
            <svg width="100%" height="128" viewBox="0 0 400 128" class="w-full">
              <defs>
                <linearGradient id="revenueGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" style="stop-color: rgb(34, 197, 94); stop-opacity: 0.5" />
                  <stop offset="100%" style="stop-color: rgb(34, 197, 94); stop-opacity: 0" />
                </linearGradient>
              </defs>
              <polyline
                :points="sparklinePoints(payoutSeries, 400, 128, 8) + ' 400,128 0,128'"
                fill="url(#revenueGradient)"
              />
              <polyline
                :points="sparklinePoints(payoutSeries, 400, 128, 8)"
                fill="none"
                stroke="rgb(34, 197, 94)"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
        </div>
      </div>

      <!-- Activity Section -->
      <div class="grid gap-6 lg:grid-cols-5">
        <!-- Recent Bookings -->
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm lg:col-span-3">
          <div class="mb-6 flex items-center justify-between">
            <div>
              <h2 class="text-lg font-semibold text-[#222222]">Dernières demandes</h2>
              <p class="mt-1 text-sm text-gray-600">Les réservations les plus récentes</p>
            </div>
            <RouterLink
              to="/host/bookings"
              class="text-sm font-medium text-gray-600 underline transition hover:text-[#222222]"
            >
              Voir tout
            </RouterLink>
          </div>

          <div v-if="recentBookings.length === 0" class="py-12 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
              <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                />
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-900">Aucune réservation</p>
            <p class="mt-1 text-xs text-gray-500">Les nouvelles demandes apparaîtront ici</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="booking in recentBookings"
              :key="booking.id"
              class="group rounded-xl border border-gray-100 bg-gray-50 p-4 transition hover:border-gray-200 hover:bg-white hover:shadow-sm"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="flex-1 space-y-1">
                  <p class="font-semibold text-[#222222]">{{ listingLabel(booking.listing_id) }}</p>
                  <p class="text-sm text-gray-600">
                    Du {{ booking.start_date }} au {{ booking.end_date }}
                  </p>
                </div>
                <svg class="h-5 w-5 text-gray-400 transition group-hover:text-gray-600" fill="none" viewBox="0 0 24 24">
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

        <!-- Upcoming Arrivals -->
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm lg:col-span-2">
          <div class="mb-6">
            <h2 class="text-lg font-semibold text-[#222222]">Prochaines arrivées</h2>
            <p class="mt-1 text-sm text-gray-600">Réservations confirmées</p>
          </div>

          <div v-if="upcomingArrivals.length === 0" class="py-12 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
              <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-900">Aucune arrivée</p>
            <p class="mt-1 text-xs text-gray-500">Programmée pour le moment</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="booking in upcomingArrivals"
              :key="booking.id"
              class="rounded-xl border border-gray-100 bg-gray-50 p-4 transition hover:border-gray-200 hover:bg-white hover:shadow-sm"
            >
              <div class="space-y-1">
                <p class="font-semibold text-[#222222]">{{ listingLabel(booking.listing_id) }}</p>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                  </svg>
                  {{ booking.start_date }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
