<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { cancelBooking, fetchBookings, type Booking } from '@/services/bookings'
import { fetchListings, type Listing } from '@/services/listings'
import { createConversation } from '@/services/conversations'
import { useAuthStore } from '@/stores/auth'
import { useRouter, RouterLink } from 'vue-router'
import { getEcho } from '@/services/echo'

const auth = useAuthStore()
const router = useRouter()
const bookings = ref<Booking[]>([])
const listings = ref<Listing[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)
const contactError = ref<string | null>(null)
const cancelError = ref<string | null>(null)
const cancelBusy = ref<number[]>([])
const bookingChannel = ref<string | null>(null)
const travelerBookings = computed(() =>
  bookings.value.filter((booking) => booking.guest_user_id === auth.user?.id)
)

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const [bookingsData, listingsResponse] = await Promise.all([
      fetchBookings(),
      fetchListings(),
    ])
    bookings.value = bookingsData
    listings.value = listingsResponse.data
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

function listingFor(booking: Booking) {
  return listings.value.find((item) => item.id === booking.listing_id)
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

function isActiveOrFuture(booking: Booking) {
  const today = new Date()
  const todayValue = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate()))
  const end = new Date(booking.end_date)
  return end >= todayValue
}

async function contactHost(booking: Booking) {
  contactError.value = null

  try {
    const conversation = await createConversation(booking.listing_id)
    await router.push(`/messages/${conversation.id}`)
  } catch (err) {
    contactError.value = err instanceof Error ? err.message : 'Impossible de contacter l’hôte.'
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
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Mes réservations</p>
      <h1 class="text-3xl font-semibold text-slate-900">Mes réservations</h1>
      <p class="text-sm text-slate-500">
        Retrouve le détail de tes séjours et contacte ton hôte si besoin.
      </p>
    </header>

    <div class="space-y-3">
      <h2 class="text-sm font-semibold text-slate-700">Toutes mes réservations</h2>
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
      <div v-else class="grid gap-4 lg:grid-cols-2">
        <article
          v-for="booking in travelerBookings"
          :key="booking.id"
          class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
        >
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Réservation</p>
              <p class="text-lg font-semibold text-slate-900">
                {{ listingLabel(booking.listing_id) }}
              </p>
            </div>
            <span class="text-xs uppercase tracking-[0.2em] text-slate-400">#{{ booking.id }}</span>
          </div>

          <div class="mt-3 flex flex-wrap items-center gap-2">
            <span
              class="rounded-full border px-3 py-1 text-xs font-semibold"
              :class="statusClass(booking.status)"
            >
              {{ statusLabel(booking.status) }}
            </span>
            <span class="text-xs text-slate-500">
              Du {{ booking.start_date }} au {{ booking.end_date }}
            </span>
          </div>

          <div class="mt-4 flex flex-wrap items-center gap-3">
            <RouterLink
              :to="`/listings/${booking.listing_id}`"
              class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
            >
              Voir le logement
            </RouterLink>
            <RouterLink
              v-if="booking.status === 'awaiting_payment'"
              :to="`/checkout/${booking.id}`"
              class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white"
            >
              Payer la réservation
            </RouterLink>
            <button
              v-if="booking.status !== 'cancelled' && booking.status !== 'completed'"
              class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 disabled:opacity-60"
              type="button"
              :disabled="cancelBusy.includes(booking.id)"
              @click="cancelBookingRequest(booking)"
            >
              {{ cancelBusy.includes(booking.id) ? 'Annulation...' : 'Annuler' }}
            </button>
            <button
              v-if="isActiveOrFuture(booking)"
              class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white"
              type="button"
              @click="contactHost(booking)"
            >
              Contacter l’hôte
            </button>
            <span v-else class="text-xs text-slate-400">
              Contact disponible avant et pendant le séjour
            </span>
          </div>

          <div v-if="listingFor(booking)?.images?.length" class="mt-4 overflow-hidden rounded-2xl">
            <img
              :src="listingFor(booking)?.images?.[0].url"
              class="h-40 w-full object-cover"
              alt=""
            />
          </div>
        </article>
      </div>
      <p v-if="contactError" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
        {{ contactError }}
      </p>
      <p v-if="cancelError" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
        {{ cancelError }}
      </p>
    </div>
  </section>
</template>
