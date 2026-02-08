<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  cancelBooking,
  confirmBooking,
  fetchBookings,
  rejectBooking,
  type Booking,
} from '@/services/bookings'
import { createConversationForBooking } from '@/services/conversations'
import { fetchCohostListings, fetchMyListings, type Listing } from '@/services/listings'
import { getEcho } from '@/services/echo'
import { useAuthStore } from '@/stores/auth'
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

// Data
const bookings = ref<Booking[]>([])
const listings = ref<Listing[]>([])

// Data fetching
const { isLoading, error } = useAsyncData(
  async () => {
    const [bookingsData, hostListingsResponse, cohostListingsResponse] = await Promise.all([
      fetchBookings(),
      fetchMyListings({ per_page: 100 }),
      fetchCohostListings({ per_page: 100 }),
    ])
    bookings.value = bookingsData

    // Combine host and cohost listings, dedupe by ID
    const combined = [
      ...(hostListingsResponse.data ?? []),
      ...(cohostListingsResponse.data ?? []),
    ]
    const uniqueById = new Map<number, Listing>()
    combined.forEach((listing) => uniqueById.set(listing.id, listing))
    listings.value = Array.from(uniqueById.values())

    return { bookings: bookingsData, listings: listings.value }
  },
  { errorMessage: 'Impossible de charger les réservations.' }
)

// Local state for actions
const busyIds = ref<number[]>([])
const cancelBusy = ref<number[]>([])
const cancelError = ref<string | null>(null)
const actionError = ref<string | null>(null)
const bookingChannel = ref<string | null>(null)

// Computed
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

const breadcrumbs = [
  { label: 'Hôte', to: '/host' },
  { label: 'Réservations' },
]

// Helper functions
function listingLabel(listingId: number): string {
  const listing = listings.value.find((item) => item.id === listingId)
  return listing ? `${listing.title} — ${listing.city}` : `Annonce #${listingId}`
}

function canManageBooking(booking: Booking): boolean {
  const permission = listingPermissions.value.get(booking.listing_id)
  return permission?.can_edit_listings ?? false
}

// Actions
async function updateStatus(booking: Booking, action: 'confirm' | 'reject'): Promise<void> {
  if (busyIds.value.includes(booking.id)) return

  busyIds.value = [...busyIds.value, booking.id]
  actionError.value = null

  try {
    const updated =
      action === 'confirm' ? await confirmBooking(booking.id) : await rejectBooking(booking.id)
    bookings.value = bookings.value.map((item) => (item.id === updated.id ? updated : item))
  } catch (err) {
    actionError.value =
      err instanceof Error ? err.message : 'Impossible de mettre à jour la réservation.'
  } finally {
    busyIds.value = busyIds.value.filter((id) => id !== booking.id)
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
    cancelError.value =
      err instanceof Error ? err.message : "Impossible d'annuler la réservation."
  } finally {
    cancelBusy.value = cancelBusy.value.filter((id) => id !== booking.id)
  }
}

async function openConversation(booking: Booking): Promise<void> {
  actionError.value = null

  try {
    const conversation = await createConversationForBooking(booking.id)
    await router.push(`/host/messages/${conversation.id}?listing=${booking.listing_id}`)
  } catch (err) {
    actionError.value =
      err instanceof Error ? err.message : 'Impossible de créer la conversation.'
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
  <section class="space-y-8">
    <!-- Page Header -->
    <PageHeader
      title="Réservations reçues"
      subtitle="Suis les réservations qui concernent tes annonces."
      :breadcrumbs="breadcrumbs"
    />

    <!-- Error Messages -->
    <AlertMessage :message="error" type="error" />
    <AlertMessage :message="actionError" type="error" />
    <AlertMessage :message="cancelError" type="error" />

    <div class="space-y-3">
      <h2 class="text-sm font-semibold text-slate-700">Dernières réservations</h2>

      <!-- Loading State -->
      <LoadingSpinner v-if="isLoading" text="Chargement des réservations..." />

      <!-- Empty State -->
      <EmptyState
        v-else-if="hostBookings.length === 0"
        title="Aucune réservation"
        subtitle="Aucune réservation reçue pour le moment."
        icon="bookings"
        :dashed="true"
      />

      <!-- Bookings List -->
      <div v-else class="space-y-2">
        <article
          v-for="booking in hostBookings"
          :key="booking.id"
          class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-600"
        >
          <!-- Header -->
          <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="font-semibold text-slate-800">
              {{ listingLabel(booking.listing_id) }}
            </span>
            <span class="text-xs uppercase tracking-[0.2em] text-slate-400">
              #{{ booking.id }}
            </span>
          </div>

          <!-- Status & Guest -->
          <div class="mt-2 flex flex-wrap items-center gap-2">
            <StatusBadge :status="booking.status" size="sm" />
            <span class="text-xs text-slate-400">
              {{ booking.guest?.name ?? `Voyageur #${booking.guest_user_id}` }}
            </span>
          </div>

          <!-- Dates -->
          <p class="mt-2">
            {{ formatDateRange(booking.start_date, booking.end_date) }}
          </p>

          <!-- Actions -->
          <div class="mt-4 flex flex-wrap items-center gap-2">
            <RouterLink
              :to="`/host/bookings/${booking.id}`"
              class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
            >
              Voir la réservation
            </RouterLink>

            <button
              type="button"
              class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
              @click="openConversation(booking)"
            >
              Contacter le voyageur
            </button>

            <button
              v-if="canManageBooking(booking) && canCancel(booking.status)"
              type="button"
              class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 disabled:opacity-60"
              :disabled="cancelBusy.includes(booking.id)"
              @click="cancelBookingRequest(booking)"
            >
              {{ cancelBusy.includes(booking.id) ? 'Annulation...' : 'Annuler' }}
            </button>

            <button
              v-if="canManageBooking(booking) && booking.status === 'pending'"
              type="button"
              class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="busyIds.includes(booking.id)"
              @click="updateStatus(booking, 'confirm')"
            >
              Confirmer
            </button>

            <button
              v-if="canManageBooking(booking) && booking.status === 'pending'"
              type="button"
              class="rounded-full border border-rose-200 px-4 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="busyIds.includes(booking.id)"
              @click="updateStatus(booking, 'reject')"
            >
              Refuser
            </button>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
