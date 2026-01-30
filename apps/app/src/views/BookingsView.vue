<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { fetchBookings, type Booking } from '@/services/bookings'
import { fetchListings, type Listing } from '@/services/listings'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const bookings = ref<Booking[]>([])
const listings = ref<Listing[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)
const travelerBookings = computed(() =>
  bookings.value.filter((booking) => booking.guest_user_id === auth.user?.id)
)

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const [bookingsData, listingsData] = await Promise.all([fetchBookings(), fetchListings()])
    bookings.value = bookingsData
    listings.value = listingsData
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger les réservations.'
  } finally {
    isLoading.value = false
  }
}

function listingLabel(listingId: number) {
  const listing = listings.value.find((item) => item.id === listingId)
  return listing ? `${listing.title} — ${listing.city}` : `Annonce #${listingId}`
}

function statusLabel(status: Booking['status']) {
  if (status === 'confirmed') return 'Confirmée'
  if (status === 'rejected') return 'Refusée'
  return 'En attente'
}

function statusClass(status: Booking['status']) {
  if (status === 'confirmed') return 'bg-emerald-50 text-emerald-600 border-emerald-100'
  if (status === 'rejected') return 'bg-rose-50 text-rose-600 border-rose-100'
  return 'bg-amber-50 text-amber-600 border-amber-100'
}

onMounted(load)
</script>

<template>
  <section class="space-y-8">
    <header class="space-y-2">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Réservations</p>
      <h1 class="text-3xl font-semibold text-slate-900">Historique des réservations</h1>
      <p class="text-sm text-slate-500">
        Consulte les réservations effectuées ou reçues.
      </p>
    </header>

    <div class="space-y-3">
      <h2 class="text-sm font-semibold text-slate-700">Réservations effectuées</h2>
      <div
        v-if="isLoading"
        class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
      >
        Chargement des réservations...
      </div>
      <div
        v-else-if="travelerBookings.length === 0"
        class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-sm text-slate-500"
      >
        Aucune réservation enregistrée.
      </div>
      <div v-else class="space-y-2">
        <article
          v-for="booking in travelerBookings"
          :key="booking.id"
          class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-600"
        >
          <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="font-semibold text-slate-800">
              {{ listingLabel(booking.listing_id) }}
            </span>
            <span class="text-xs uppercase tracking-[0.2em] text-slate-400">
              #{{ booking.id }}
            </span>
          </div>
          <div class="mt-2">
            <span
              class="rounded-full border px-3 py-1 text-xs font-semibold"
              :class="statusClass(booking.status)"
            >
              {{ statusLabel(booking.status) }}
            </span>
          </div>
          <p class="mt-2">
            Du {{ booking.start_date }} au {{ booking.end_date }}
          </p>
        </article>
      </div>
    </div>
  </section>
</template>
