<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { confirmBooking, fetchBookings, rejectBooking, type Booking } from '@/services/bookings'
import { createConversation } from '@/services/conversations'
import { useRouter } from 'vue-router'
import { fetchMyListings, type Listing } from '@/services/listings'

const bookings = ref<Booking[]>([])
const listings = ref<Listing[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)
const busyIds = ref<number[]>([])
const router = useRouter()

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

async function updateStatus(booking: Booking, action: 'confirm' | 'reject') {
  if (busyIds.value.includes(booking.id)) return
  busyIds.value = [...busyIds.value, booking.id]
  error.value = null

  try {
    const updated =
      action === 'confirm' ? await confirmBooking(booking.id) : await rejectBooking(booking.id)
    bookings.value = bookings.value.map((item) => (item.id === updated.id ? updated : item))
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de mettre à jour la réservation.'
  } finally {
    busyIds.value = busyIds.value.filter((id) => id !== booking.id)
  }
}

async function openConversation(booking: Booking) {
  error.value = null

  try {
    const conversation = await createConversation(booking.listing_id)
    await router.push(`/host/messages/${conversation.id}`)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de créer la conversation.'
  }
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
          <div class="mt-2 flex flex-wrap items-center gap-2">
            <span
              class="rounded-full border px-3 py-1 text-xs font-semibold"
              :class="statusClass(booking.status)"
            >
              {{ statusLabel(booking.status) }}
            </span>
            <span class="text-xs text-slate-400">
              {{ booking.guest?.name ?? `Voyageur #${booking.guest_user_id}` }}
            </span>
          </div>
          <p class="mt-2">
            Du {{ booking.start_date }} au {{ booking.end_date }}
          </p>
          <div class="mt-4 flex flex-wrap items-center gap-2">
            <button
              class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
              type="button"
              @click="openConversation(booking)"
            >
              Contacter le voyageur
            </button>
            <button
              v-if="booking.status === 'pending'"
              class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
              type="button"
              :disabled="busyIds.includes(booking.id)"
              @click="updateStatus(booking, 'confirm')"
            >
              Confirmer
            </button>
            <button
              v-if="booking.status === 'pending'"
              class="rounded-full border border-rose-200 px-4 py-2 text-xs font-semibold text-rose-600 disabled:cursor-not-allowed disabled:opacity-60"
              type="button"
              :disabled="busyIds.includes(booking.id)"
              @click="updateStatus(booking, 'reject')"
            >
              Refuser
            </button>
          </div>
        </article>
      </div>
      <p v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
        {{ error }}
      </p>
    </div>
  </section>
</template>
