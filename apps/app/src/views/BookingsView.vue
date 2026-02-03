<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { cancelBooking, fetchBookings, type Booking } from '@/services/bookings'
import { fetchListings, type Listing } from '@/services/listings'
import { createConversation } from '@/services/conversations'
import { useAuthStore } from '@/stores/auth'
import { getEcho } from '@/services/echo'
import { useAsyncData, useBookingStatus, useDateFormat } from '@/composables'
import {
  AlertMessage,
  EmptyState,
  LoadingSpinner,
  PageHeader,
  StatusBadge,
} from '@/components/ui'

// Stores & Router
const auth = useAuthStore()
const router = useRouter()

// Composables
const { canCancel } = useBookingStatus()
const { formatDateRange } = useDateFormat()

// Data fetching with useAsyncData
const bookings = ref<Booking[]>([])
const listings = ref<Listing[]>([])

const { isLoading, error } = useAsyncData(
  async () => {
    const [bookingsData, listingsResponse] = await Promise.all([
      fetchBookings(),
      fetchListings(),
    ])
    bookings.value = bookingsData
    listings.value = listingsResponse.data
    return { bookings: bookingsData, listings: listingsResponse.data }
  },
  { errorMessage: 'Impossible de charger les réservations.' }
)

// Local state for actions
const contactError = ref<string | null>(null)
const cancelError = ref<string | null>(null)
const cancelBusy = ref<number[]>([])
const bookingChannel = ref<string | null>(null)

// Computed
const travelerBookings = computed(() =>
  bookings.value.filter((booking) => booking.guest_user_id === auth.user?.id)
)

const breadcrumbs = [
  { label: 'Accueil', to: '/' },
  { label: 'Voyages' },
]

// Helper functions
function listingFor(booking: Booking): Listing | undefined {
  return listings.value.find((item) => item.id === booking.listing_id)
}

function listingLabel(listingId: number): string {
  const listing = listings.value.find((item) => item.id === listingId)
  return listing ? `${listing.title} — ${listing.city}` : `Annonce #${listingId}`
}

function isActiveOrFuture(booking: Booking): boolean {
  const today = new Date()
  const todayValue = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate()))
  const end = new Date(booking.end_date)
  return end >= todayValue
}

// Actions
async function contactHost(booking: Booking): Promise<void> {
  contactError.value = null
  try {
    const conversation = await createConversation(booking.listing_id)
    await router.push(`/messages/${conversation.id}`)
  } catch (err) {
    contactError.value = err instanceof Error ? err.message : "Impossible de contacter l'hôte."
  }
}

async function cancelBookingRequest(booking: Booking): Promise<void> {
  if (cancelBusy.value.includes(booking.id)) return

  cancelBusy.value = [...cancelBusy.value, booking.id]
  cancelError.value = null

  try {
    const updated = await cancelBooking(booking.id)
    bookings.value = bookings.value.map((item) => (item.id === updated.id ? updated : item))
  } catch (err) {
    cancelError.value = err instanceof Error ? err.message : "Impossible d'annuler la réservation."
  } finally {
    cancelBusy.value = cancelBusy.value.filter((id) => id !== booking.id)
  }
}

// Real-time updates
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
    <!-- Page Header -->
    <PageHeader
      title="Vos voyages"
      subtitle="Retrouvez vos réservations passées, en cours et à venir"
      :breadcrumbs="breadcrumbs"
    />

    <!-- Error Messages -->
    <AlertMessage :message="error" type="error" />
    <AlertMessage :message="contactError" type="error" />
    <AlertMessage :message="cancelError" type="error" />

    <!-- Loading State -->
    <LoadingSpinner v-if="isLoading" text="Chargement des réservations..." />

    <!-- Empty State -->
    <EmptyState
      v-else-if="travelerBookings.length === 0"
      title="Aucune réservation"
      subtitle="Il est temps de planifier votre prochaine aventure !"
      icon="bookings"
      action-text="Explorer les logements"
      action-to="/listings"
    />

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
                :alt="listingLabel(booking.listing_id)"
                class="h-full w-full object-cover"
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
                  <StatusBadge :status="booking.status" />
                </div>

                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                  <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                  </svg>
                  <span>{{ formatDateRange(booking.start_date, booking.end_date) }}</span>
                </div>
              </div>

              <!-- Actions -->
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
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50"
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
                  v-if="canCancel(booking.status)"
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-40"
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
