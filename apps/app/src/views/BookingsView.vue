<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { cancelBooking, fetchBookings, type Booking } from '@/services/bookings'
import { fetchListings, type Listing } from '@/services/listings'
import { createConversation } from '@/services/conversations'
import { useAuthStore } from '@/stores/auth'
import { useRouter, RouterLink } from 'vue-router'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
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
  if (status === 'confirmed') return 'bg-green-50 text-green-700 border-green-200'
  if (status === 'awaiting_payment') return 'bg-amber-50 text-amber-700 border-amber-200'
  if (status === 'completed') return 'bg-gray-100 text-gray-700 border-gray-200'
  if (status === 'cancelled') return 'bg-gray-100 text-gray-700 border-gray-200'
  if (status === 'rejected') return 'bg-red-50 text-red-700 border-red-200'
  return 'bg-blue-50 text-blue-700 border-blue-200'
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
    contactError.value = err instanceof Error ? err.message : 'Impossible de contacter l\'hôte.'
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
    cancelError.value = err instanceof Error ? err.message : 'Impossible d\'annuler la réservation.'
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
  <section class="mx-auto max-w-5xl space-y-8">
    <header class="space-y-4">
      <Breadcrumbs :items="[{ label: 'Accueil', to: '/' }, { label: 'Voyages' }]" />
      <h1 class="text-5xl font-semibold tracking-tight text-[#222222]">Vos voyages</h1>
      <p class="text-lg text-gray-600">
        Retrouvez vos réservations passées, en cours et à venir
      </p>
    </header>

    <!-- Error Messages -->
    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-6 py-4 text-sm text-red-700">
      {{ error }}
    </div>
    <div v-if="contactError" class="rounded-xl border border-red-200 bg-red-50 px-6 py-4 text-sm text-red-700">
      {{ contactError }}
    </div>
    <div v-if="cancelError" class="rounded-xl border border-red-200 bg-red-50 px-6 py-4 text-sm text-red-700">
      {{ cancelError }}
    </div>

    <!-- Loading State -->
    <div
      v-if="isLoading"
      class="flex items-center justify-center rounded-2xl border border-gray-100 bg-gray-50 py-20"
    >
      <div class="text-center">
        <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-[#FF385C]"></div>
        <p class="mt-4 text-sm text-gray-600">Chargement des réservations...</p>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="travelerBookings.length === 0"
      class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-gray-50 py-20"
    >
      <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gray-100">
        <svg class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24">
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
          />
        </svg>
      </div>
      <h3 class="text-xl font-semibold text-[#222222]">Aucune réservation</h3>
      <p class="mt-2 text-sm text-gray-600">Il est temps de planifier votre prochaine aventure !</p>
      <RouterLink
        to="/listings"
        class="mt-6 rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:shadow-md"
      >
        Explorer les logements
      </RouterLink>
    </div>

    <!-- Bookings Grid -->
    <div v-else class="space-y-6">
      <div class="grid gap-6">
        <article
          v-for="booking in travelerBookings"
          :key="booking.id"
          class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
        >
          <div class="grid gap-6 md:grid-cols-[300px_1fr]">
            <!-- Image -->
            <div class="relative aspect-[4/3] overflow-hidden bg-gray-100 md:aspect-auto">
              <img
                v-if="listingFor(booking)?.images?.length"
                :src="listingFor(booking)?.images?.[0]?.url"
                class="h-full w-full object-cover"
                :alt="listingLabel(booking.listing_id)"
              />
              <div v-else class="flex h-full w-full items-center justify-center">
                <svg class="h-16 w-16 text-gray-300" fill="none" viewBox="0 0 24 24">
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
            <div class="flex flex-col justify-between p-6">
              <div class="space-y-4">
                <div class="flex items-start justify-between gap-4">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Réservation #{{ booking.id }}</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-[#222222]">
                      {{ listingLabel(booking.listing_id) }}
                    </h2>
                  </div>
                  <span
                    class="inline-flex rounded-full border px-3 py-1.5 text-xs font-semibold"
                    :class="statusClass(booking.status)"
                  >
                    {{ statusLabel(booking.status) }}
                  </span>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                  <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                      <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                      />
                    </svg>
                    <span>{{ booking.start_date }}</span>
                  </div>
                  <span class="text-gray-400">→</span>
                  <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                      <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                      />
                    </svg>
                    <span>{{ booking.end_date }}</span>
                  </div>
                </div>
              </div>

              <div class="mt-6 flex flex-wrap gap-3 border-t border-gray-100 pt-6">
                <RouterLink
                  :to="`/listings/${booking.listing_id}`"
                  class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                  </svg>
                  Voir le logement
                </RouterLink>

                <RouterLink
                  v-if="booking.status === 'awaiting_payment'"
                  :to="`/checkout/${booking.id}`"
                  class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                    />
                  </svg>
                  Payer
                </RouterLink>

                <button
                  v-if="isActiveOrFuture(booking) && booking.status === 'confirmed'"
                  class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50"
                  type="button"
                  @click="contactHost(booking)"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                    />
                  </svg>
                  Contacter l'hôte
                </button>

                <button
                  v-if="booking.status !== 'cancelled' && booking.status !== 'completed'"
                  class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-40"
                  type="button"
                  :disabled="cancelBusy.includes(booking.id)"
                  @click="cancelBookingRequest(booking)"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                  {{ cancelBusy.includes(booking.id) ? 'Annulation...' : 'Annuler' }}
                </button>
              </div>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
