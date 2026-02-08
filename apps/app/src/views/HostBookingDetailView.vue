<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { cancelBooking, confirmBooking, fetchBooking, rejectBooking, type Booking } from '@/services/bookings'
import { createConversationForBooking } from '@/services/conversations'
import { useBookingStatus, useDateFormat } from '@/composables'
import { AlertMessage, LoadingSpinner, PageHeader, StatusBadge } from '@/components/ui'

const route = useRoute()
const router = useRouter()
const { canCancel } = useBookingStatus()
const { formatDateRange } = useDateFormat()

const booking = ref<Booking | null>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)
const actionError = ref<string | null>(null)
const isBusy = ref(false)

const bookingId = computed(() => Number(route.params.id))
const listing = computed(() => booking.value?.listing ?? null)
const heroImage = computed(() => listing.value?.images?.[0]?.url ?? null)

const breadcrumbs = computed(() => [
  { label: 'Hôte', to: '/host' },
  { label: 'Réservations', to: '/host/bookings' },
  { label: booking.value ? `Réservation #${booking.value.id}` : 'Réservation' },
])

function formatAmount(value?: number | null) {
  if (value == null) return '—'
  return value.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' })
}

async function load() {
  isLoading.value = true
  error.value = null

  try {
    booking.value = await fetchBooking(bookingId.value)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger la réservation.'
  } finally {
    isLoading.value = false
  }
}

async function updateStatus(action: 'confirm' | 'reject') {
  if (!booking.value || isBusy.value) return

  isBusy.value = true
  actionError.value = null

  try {
    booking.value = action === 'confirm'
      ? await confirmBooking(booking.value.id)
      : await rejectBooking(booking.value.id)
  } catch (err) {
    actionError.value = err instanceof Error ? err.message : "Impossible de mettre à jour la réservation."
  } finally {
    isBusy.value = false
  }
}

async function cancelBookingRequest() {
  if (!booking.value || isBusy.value) return

  isBusy.value = true
  actionError.value = null

  try {
    booking.value = await cancelBooking(booking.value.id)
  } catch (err) {
    actionError.value = err instanceof Error ? err.message : "Impossible d'annuler la réservation."
  } finally {
    isBusy.value = false
  }
}

async function contactGuest() {
  if (!booking.value) return

  actionError.value = null
  try {
    const conversation = await createConversationForBooking(booking.value.id)
    await router.push(`/host/messages/${conversation.id}?listing=${booking.value.listing_id}`)
  } catch (err) {
    actionError.value = err instanceof Error ? err.message : "Impossible de contacter le voyageur."
  }
}

onMounted(load)
</script>

<template>
  <section class="mx-auto max-w-5xl space-y-8">
    <PageHeader
      :title="booking ? `Réservation #${booking.id}` : 'Réservation'"
      subtitle="Détails complets pour l'hôte"
      :breadcrumbs="breadcrumbs"
    />

    <AlertMessage :message="error" type="error" />
    <AlertMessage :message="actionError" type="error" />

    <LoadingSpinner v-if="isLoading" text="Chargement de la réservation..." />

    <div v-else-if="booking" class="space-y-8">
      <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
        <div class="grid gap-6 md:grid-cols-[280px_1fr]">
          <div class="relative aspect-[4/3] overflow-hidden bg-gray-100 md:aspect-auto">
            <img
              v-if="heroImage"
              :src="heroImage"
              :alt="listing?.title ?? 'Annonce'"
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

          <div class="flex flex-col justify-between gap-6 p-6">
            <div class="space-y-3">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <p class="text-sm font-medium text-gray-500">Annonce</p>
                  <h2 class="mt-1 text-2xl font-semibold tracking-tight text-[#222222]">
                    {{ listing ? `${listing.title} — ${listing.city}` : `Annonce #${booking.listing_id}` }}
                  </h2>
                </div>
                <StatusBadge :status="booking.status" />
              </div>

              <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600">
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

              <div class="text-sm text-gray-600">
                Voyageur :
                <span class="font-semibold text-gray-800">
                  {{ booking.guest?.name ?? `Voyageur #${booking.guest_user_id}` }}
                </span>
              </div>
            </div>

            <div class="grid gap-4 rounded-2xl border border-gray-100 bg-gray-50 p-4 text-sm text-gray-700">
              <div class="flex items-center justify-between">
                <span>Montant total</span>
                <span class="font-semibold text-[#222222]">{{ formatAmount(booking.payment?.amount_total ?? null) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span>Payout hôte</span>
                <span>{{ formatAmount(booking.payment?.payout_amount ?? null) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span>Commission</span>
                <span>{{ formatAmount(booking.payment?.commission_amount ?? null) }}</span>
              </div>
            </div>

            <div class="flex flex-wrap gap-3">
              <RouterLink
                :to="`/listings/${booking.listing_id}`"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50"
              >
                Voir l'annonce
              </RouterLink>

              <button
                v-if="booking.status === 'pending'"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"
                :disabled="isBusy"
                @click="updateStatus('confirm')"
              >
                Confirmer
              </button>

              <button
                v-if="booking.status === 'pending'"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="isBusy"
                @click="updateStatus('reject')"
              >
                Refuser
              </button>

              <button
                v-if="booking.status === 'confirmed'"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50"
                @click="contactGuest"
              >
                Contacter le voyageur
              </button>

              <button
                v-if="canCancel(booking.status)"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="isBusy"
                @click="cancelBookingRequest"
              >
                {{ isBusy ? 'Annulation...' : 'Annuler' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <RouterLink
        to="/host/bookings"
        class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 transition hover:text-[#222222]"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 19l-7-7 7-7"
          />
        </svg>
        Retour aux réservations
      </RouterLink>
    </div>
  </section>
</template>
