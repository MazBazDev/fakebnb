<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
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
  return values.length ? values[values.length - 1] : 0
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
  <section class="space-y-6">
    <header class="space-y-2">
      <Breadcrumbs :items="[{ label: 'Hôte', to: '/host' }, { label: 'Tableau de bord' }]" />
      <h1 class="text-3xl font-semibold text-slate-900">Bienvenue {{ displayName }}</h1>
      <p class="text-sm text-slate-500">
        Centralise tes annonces, réservations et messages dans un espace dédié.
      </p>
    </header>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Chargement des statistiques...
    </div>
    <div
      v-else-if="error"
      class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-600"
    >
      {{ error }}
    </div>

    <div v-else class="space-y-6">
      <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Annonces</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ stats?.listings_count ?? 0 }}
          </p>
          <p class="text-xs text-slate-500">Actives actuellement</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">En attente</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ stats?.pending_count ?? 0 }}
          </p>
          <p class="text-xs text-slate-500">Demandes à traiter</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Paiements</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ stats?.awaiting_payment_count ?? 0 }}
          </p>
          <p class="text-xs text-slate-500">En attente de paiement</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Revenus</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ formatAmount(stats?.total_payout ?? 0) }}
          </p>
          <p class="text-xs text-slate-500">Payouts confirmés</p>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Réservations</p>
              <p class="mt-2 text-xl font-semibold text-slate-900">
                {{ latestSeriesValue(bookingSeries) }}
              </p>
              <p class="text-xs text-slate-500">Ce mois-ci</p>
            </div>
            <svg width="140" height="40" viewBox="0 0 140 40" class="text-slate-300">
              <polyline
                :points="sparklinePoints(bookingSeries, 140, 40, 6)"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              />
            </svg>
          </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Revenu attendu</p>
              <p class="mt-2 text-xl font-semibold text-slate-900">
                {{ formatAmount(latestSeriesValue(payoutSeries)) }}
              </p>
              <p class="text-xs text-slate-500">Ce mois-ci</p>
            </div>
            <svg width="140" height="40" viewBox="0 0 140 40" class="text-emerald-300">
              <polyline
                :points="sparklinePoints(payoutSeries, 140, 40, 6)"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              />
            </svg>
          </div>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
          <h2 class="text-sm font-semibold text-slate-700">Dernières demandes</h2>
          <p class="mt-1 text-sm text-slate-500">
            Les réservations les plus récentes sur tes annonces.
          </p>
          <div v-if="recentBookings.length === 0" class="mt-4 text-sm text-slate-400">
            Aucune réservation pour le moment.
          </div>
          <div v-else class="mt-4 space-y-3">
            <div
              v-for="booking in recentBookings"
              :key="booking.id"
              class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 text-sm text-slate-600"
            >
              <p class="font-semibold text-slate-800">{{ listingLabel(booking.listing_id) }}</p>
              <p class="text-xs text-slate-500">
                Du {{ booking.start_date }} au {{ booking.end_date }}
              </p>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
          <h2 class="text-sm font-semibold text-slate-700">Prochaines arrivées</h2>
          <p class="mt-1 text-sm text-slate-500">Réservations confirmées à venir.</p>
          <div v-if="upcomingArrivals.length === 0" class="mt-4 text-sm text-slate-400">
            Aucune arrivée programmée.
          </div>
          <div v-else class="mt-4 space-y-3">
            <div
              v-for="booking in upcomingArrivals"
              :key="booking.id"
              class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 text-sm text-slate-600"
            >
              <p class="font-semibold text-slate-800">{{ listingLabel(booking.listing_id) }}</p>
              <p class="text-xs text-slate-500">
                Arrivée le {{ booking.start_date }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
