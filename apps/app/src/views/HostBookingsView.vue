<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { fetchBookings, type Booking } from '@/services/bookings'
import { fetchMyListings, type Listing } from '@/services/listings'

const bookings = ref<Booking[]>([])
const listings = ref<Listing[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)

const hostBookingIds = computed(() => new Set(listings.value.map((listing) => listing.id)))
const hostBookings = computed(() =>
  bookings.value.filter((booking) => hostBookingIds.value.has(booking.listing_id))
)

async function load() {
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
    error.value = err instanceof Error ? err.message : 'Impossible de charger les réservations.'
  } finally {
    isLoading.value = false
  }
}

function listingLabel(listingId: number) {
  const listing = listings.value.find((item) => item.id === listingId)
  return listing ? `${listing.title} — ${listing.city}` : `Annonce #${listingId}`
}

onMounted(load)
</script>

<template>
  <section class="space-y-8">
    <header class="space-y-2">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Espace hôte</p>
      <h1 class="text-3xl font-semibold text-slate-900">Réservations reçues</h1>
      <p class="text-sm text-slate-500">
        Suis les réservations qui concernent tes annonces.
      </p>
    </header>

    <div class="space-y-3">
      <h2 class="text-sm font-semibold text-slate-700">Dernières réservations</h2>
      <div
        v-if="isLoading"
        class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
      >
        Chargement des réservations...
      </div>
      <div
        v-else-if="hostBookings.length === 0"
        class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-sm text-slate-500"
      >
        Aucune réservation reçue pour le moment.
      </div>
      <div v-else class="space-y-2">
        <article
          v-for="booking in hostBookings"
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
          <p class="mt-2">
            Du {{ booking.start_date }} au {{ booking.end_date }}
          </p>
          <p class="mt-1 text-xs text-slate-400">
            Voyageur #{{ booking.guest_user_id }}
          </p>
        </article>
      </div>
      <p v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
        {{ error }}
      </p>
    </div>
  </section>
</template>
