<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { fetchBookings, type Booking } from '@/services/bookings'
import { fetchMyListings, type Listing } from '@/services/listings'

const auth = useAuthStore()
const displayName = computed(() => auth.user?.name ?? auth.user?.email ?? 'Utilisateur')
const bookings = ref<Booking[]>([])
const listings = ref<Listing[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)

const hostListingIds = computed(() => new Set(listings.value.map((listing) => listing.id)))
const hostBookings = computed(() =>
  bookings.value.filter((booking) => hostListingIds.value.has(booking.listing_id))
)
const pendingBookings = computed(() =>
  hostBookings.value.filter((booking) => booking.status === 'pending')
)
const awaitingPaymentBookings = computed(() =>
  hostBookings.value.filter((booking) => booking.status === 'awaiting_payment')
)
const confirmedBookings = computed(() =>
  hostBookings.value.filter((booking) => booking.status === 'confirmed')
)
const totalPayout = computed(() => {
  return confirmedBookings.value.reduce((sum, booking) => {
    return sum + (booking.payment?.payout_amount ?? 0)
  }, 0)
})
const upcomingArrivals = computed(() => {
  const today = new Date()
  return confirmedBookings.value
    .filter((booking) => new Date(booking.start_date) >= today)
    .sort((a, b) => a.start_date.localeCompare(b.start_date))
    .slice(0, 3)
})
const recentBookings = computed(() => {
  return [...hostBookings.value]
    .sort((a, b) => (b.created_at ?? '').localeCompare(a.created_at ?? ''))
    .slice(0, 4)
})

function listingLabel(listingId: number) {
  const listing = listings.value.find((item) => item.id === listingId)
  return listing ? `${listing.title} — ${listing.city}` : `Annonce #${listingId}`
}

function formatAmount(value: number) {
  return (value / 100).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' })
}

async function loadDashboard() {
  isLoading.value = true
  error.value = null

  try {
    const [bookingsData, listingsData] = await Promise.all([
      fetchBookings(),
      fetchMyListings(),
    ])
    bookings.value = bookingsData
    listings.value = listingsData
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
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Espace hôte</p>
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
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ listings.length }}</p>
          <p class="text-xs text-slate-500">Actives actuellement</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">En attente</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ pendingBookings.length }}
          </p>
          <p class="text-xs text-slate-500">Demandes à traiter</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Paiements</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ awaitingPaymentBookings.length }}
          </p>
          <p class="text-xs text-slate-500">En attente de paiement</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Revenus</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ formatAmount(totalPayout) }}
          </p>
          <p class="text-xs text-slate-500">Payouts confirmés</p>
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
