<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { cancelBooking, confirmBooking, fetchBookings, rejectBooking, type Booking } from '@/services/bookings'
import { createConversation } from '@/services/conversations'
import { useRouter } from 'vue-router'
import { fetchCohostListings, fetchMyListings, type Listing } from '@/services/listings'
import { getEcho } from '@/services/echo'
import { useAuthStore } from '@/stores/auth'

const bookings = ref<Booking[]>([])
const listings = ref<Listing[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)
const busyIds = ref<number[]>([])
const cancelBusy = ref<number[]>([])
const cancelError = ref<string | null>(null)
const auth = useAuthStore()
const router = useRouter()
const bookingChannel = ref<string | null>(null)

const listingPermissions = computed(() => {
  const map = new Map<number, { can_edit_listings: boolean }>()
  listings.value.forEach((listing) => {
    const canEdit = listing.cohost_permissions
      ? listing.cohost_permissions.can_edit_listings
      : true
    map.set(listing.id, { can_edit_listings: canEdit })
  })
  return map
})

const accessibleListingIds = computed(() => new Set(listingPermissions.value.keys()))
const hostBookings = computed(() =>
  bookings.value.filter((booking) => accessibleListingIds.value.has(booking.listing_id))
)

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const [bookingsData, hostListingsResponse, cohostListingsResponse] = await Promise.all([
      fetchBookings(),
      fetchMyListings({ per_page: 100 }),
      fetchCohostListings({ per_page: 100 }),
    ])
    bookings.value = bookingsData
    const combined = [
      ...(hostListingsResponse.data ?? []),
      ...(cohostListingsResponse.data ?? []),
    ]
    const uniqueById = new Map<number, Listing>()
    combined.forEach((listing) => uniqueById.set(listing.id, listing))
    listings.value = Array.from(uniqueById.values())
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
  if (status === 'awaiting_payment') return 'Paiement requis'
  if (status === 'completed') return 'Terminée'
  if (status === 'cancelled') return 'Annulée'
  if (status === 'rejected') return 'Refusée'
  return 'En attente'
}

function statusClass(status: Booking['status']) {
  if (status === 'confirmed') return 'bg-emerald-50 text-emerald-600 border-emerald-100'
  if (status === 'awaiting_payment') return 'bg-amber-50 text-amber-600 border-amber-100'
  if (status === 'completed') return 'bg-slate-100 text-slate-600 border-slate-200'
  if (status === 'cancelled') return 'bg-slate-100 text-slate-600 border-slate-200'
  if (status === 'rejected') return 'bg-rose-50 text-rose-600 border-rose-100'
  return 'bg-amber-50 text-amber-600 border-amber-100'
}

function canManageBooking(booking: Booking) {
  const permission = listingPermissions.value.get(booking.listing_id)
  return permission?.can_edit_listings ?? false
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

async function cancelBookingRequest(booking: Booking) {
  if (cancelBusy.value.includes(booking.id)) return
  cancelBusy.value = [...cancelBusy.value, booking.id]
  cancelError.value = null

  try {
    const updated = await cancelBooking(booking.id)
    bookings.value = bookings.value.map((item) => (item.id === updated.id ? updated : item))
  } catch (err) {
    cancelError.value = err instanceof Error ? err.message : 'Impossible d’annuler la réservation.'
  } finally {
    cancelBusy.value = cancelBusy.value.filter((id) => id !== booking.id)
  }
}

async function openConversation(booking: Booking) {
  error.value = null

  try {
    const conversation = await createConversation(booking.listing_id)
    await router.push(`/host/messages/${conversation.id}?listing=${booking.listing_id}`)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de créer la conversation.'
  }
}

onMounted(load)

watch(
  () => auth.user?.id,
  (userId) => {
    const echo = getEcho()
    if (bookingChannel.value) {
      echo.leave(bookingChannel.value)
      bookingChannel.value = null
    }

    if (!userId) return

    const channel = `App.Models.User.${userId}`
    bookingChannel.value = channel
    echo.private(channel).listen('.BookingUpdated', (payload: Booking) => {
      const existing = bookings.value.find((item) => item.id === payload.id)
      if (existing) {
        bookings.value = bookings.value.map((item) => (item.id === payload.id ? payload : item))
      } else {
        bookings.value = [payload, ...bookings.value]
      }
    })
  },
  { immediate: true }
)

onUnmounted(() => {
  const echo = getEcho()
  if (bookingChannel.value) {
    echo.leave(bookingChannel.value)
  }
})
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
              v-if="canManageBooking(booking) && booking.status !== 'cancelled' && booking.status !== 'completed'"
              class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 disabled:opacity-60"
              type="button"
              :disabled="cancelBusy.includes(booking.id)"
              @click="cancelBookingRequest(booking)"
            >
              {{ cancelBusy.includes(booking.id) ? 'Annulation...' : 'Annuler' }}
            </button>
            <button
              v-if="canManageBooking(booking) && booking.status === 'pending'"
              class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
              type="button"
              :disabled="busyIds.includes(booking.id)"
              @click="updateStatus(booking, 'confirm')"
            >
              Confirmer
            </button>
            <button
              v-if="canManageBooking(booking) && booking.status === 'pending'"
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
      <p v-if="cancelError" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
        {{ cancelError }}
      </p>
    </div>
  </section>
</template>
