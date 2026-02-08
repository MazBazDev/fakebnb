<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { cancelBooking, fetchBooking, type Booking } from '@/services/bookings'
import { createConversationForBooking } from '@/services/conversations'
import { createReview } from '@/services/reviews'
import { useAuthStore } from '@/stores/auth'
import { useBookingStatus, useDateFormat } from '@/composables'
import { AlertMessage, LoadingSpinner, PageHeader, StatusBadge } from '@/components/ui'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { canCancel } = useBookingStatus()
const { formatDateRange } = useDateFormat()

const booking = ref<Booking | null>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)
const cancelError = ref<string | null>(null)
const contactError = ref<string | null>(null)
const reviewError = ref<string | null>(null)
const isCancelling = ref(false)
const isSubmittingReview = ref(false)
const reviewRating = ref(0)
const reviewComment = ref('')

const bookingId = computed(() => Number(route.params.id))
const listing = computed(() => booking.value?.listing ?? null)
const heroImage = computed(() => listing.value?.images?.[0]?.url ?? null)
const existingReview = computed(() => booking.value?.review ?? null)

const canReview = computed(() => {
  if (!booking.value) return false
  if (existingReview.value) return false
  if (!['confirmed', 'completed'].includes(booking.value.status)) return false
  const end = new Date(booking.value.end_date)
  const today = new Date()
  const todayValue = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate()))
  return end < todayValue
})

const breadcrumbs = computed(() => [
  { label: 'Accueil', to: '/' },
  { label: 'Voyages', to: '/bookings' },
  { label: booking.value ? `Réservation #${booking.value.id}` : 'Réservation' },
])

function formatAmount(value?: number | null) {
  if (value == null) return '—'
  return value.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' })
}

function setRating(value: number) {
  reviewRating.value = value
}

async function submitReview() {
  if (!booking.value || isSubmittingReview.value) return
  if (reviewRating.value < 1 || reviewComment.value.trim().length === 0) {
    reviewError.value = 'Veuillez laisser une note et un commentaire.'
    return
  }

  isSubmittingReview.value = true
  reviewError.value = null

  try {
    const review = await createReview(booking.value.id, {
      rating: reviewRating.value,
      comment: reviewComment.value.trim(),
    })
    booking.value = { ...booking.value, review }
    reviewRating.value = 0
    reviewComment.value = ''
  } catch (err) {
    reviewError.value = err instanceof Error ? err.message : 'Impossible de publier votre avis.'
  } finally {
    isSubmittingReview.value = false
  }
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

async function cancelBookingRequest() {
  if (!booking.value || isCancelling.value) return

  isCancelling.value = true
  cancelError.value = null

  try {
    booking.value = await cancelBooking(booking.value.id)
  } catch (err) {
    cancelError.value = err instanceof Error ? err.message : "Impossible d'annuler la réservation."
  } finally {
    isCancelling.value = false
  }
}

async function contactHost() {
  if (!booking.value) return

  contactError.value = null
  try {
    const conversation = await createConversationForBooking(booking.value.id)
    await router.push(`/messages/${conversation.id}`)
  } catch (err) {
    contactError.value = err instanceof Error ? err.message : "Impossible de contacter l'hôte."
  }
}

onMounted(load)
</script>

<template>
  <section class="mx-auto max-w-5xl space-y-8">
    <PageHeader
      :title="booking ? `Réservation #${booking.id}` : 'Réservation'"
      subtitle="Tous les détails de votre séjour"
      :breadcrumbs="breadcrumbs"
    />

    <AlertMessage :message="error" type="error" />
    <AlertMessage :message="cancelError" type="error" />
    <AlertMessage :message="contactError" type="error" />

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
            </div>

            <div class="grid gap-4 rounded-2xl border border-gray-100 bg-gray-50 p-4 text-sm text-gray-700">
              <div class="flex items-center justify-between">
                <span>Montant total</span>
                <span class="font-semibold text-[#222222]">{{ formatAmount(booking.payment?.amount_total ?? null) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span>Base</span>
                <span>{{ formatAmount(booking.payment?.amount_base ?? null) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span>TVA</span>
                <span>{{ formatAmount(booking.payment?.amount_vat ?? null) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span>Frais de service</span>
                <span>{{ formatAmount(booking.payment?.amount_service ?? null) }}</span>
              </div>
            </div>

            <div class="flex flex-wrap gap-3">
              <RouterLink
                :to="`/listings/${booking.listing_id}`"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50"
              >
                Voir l'annonce
              </RouterLink>

              <RouterLink
                v-if="booking.status === 'awaiting_payment'"
                :to="`/checkout/${booking.id}`"
                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"
              >
                Payer
              </RouterLink>

              <button
                v-if="booking.status === 'confirmed'"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50"
                @click="contactHost"
              >
                Contacter l'hôte
              </button>

              <button
                v-if="canCancel(booking.status)"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="isCancelling"
                @click="cancelBookingRequest"
              >
                {{ isCancelling ? 'Annulation...' : 'Annuler' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-start justify-between">
          <div>
            <h2 class="text-lg font-semibold text-[#222222]">Votre avis</h2>
            <p class="text-sm text-gray-600">Partagez votre expérience sur cette annonce.</p>
          </div>
          <StatusBadge :status="booking.status" />
        </div>

        <AlertMessage :message="reviewError" type="error" />

        <div v-if="existingReview" class="space-y-4">
          <div class="flex items-center gap-2 text-rose-600">
            <span v-for="star in 5" :key="star" class="text-lg">
              {{ star <= existingReview.rating ? '★' : '☆' }}
            </span>
          </div>
          <p class="text-sm text-gray-700">{{ existingReview.comment }}</p>

          <div v-if="existingReview.reply_body" class="rounded-xl border border-gray-100 bg-gray-50 p-4 text-sm text-gray-700">
            <p class="font-semibold text-gray-800">Réponse de l'hôte</p>
            <p class="mt-1">{{ existingReview.reply_body }}</p>
          </div>
        </div>

        <form v-else-if="canReview" class="space-y-4" @submit.prevent="submitReview">
          <div class="flex items-center gap-2 text-rose-600">
            <button
              v-for="star in 5"
              :key="star"
              type="button"
              class="text-2xl transition hover:scale-105"
              :class="star <= reviewRating ? 'text-rose-500' : 'text-gray-300'"
              @click="setRating(star)"
            >
              ★
            </button>
          </div>

          <textarea
            v-model="reviewComment"
            rows="4"
            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 shadow-sm focus:border-rose-400 focus:outline-none focus:ring-4 focus:ring-rose-100"
            placeholder="Racontez votre séjour..."
          ></textarea>

          <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="isSubmittingReview"
          >
            {{ isSubmittingReview ? 'Publication...' : 'Publier mon avis' }}
          </button>
        </form>

        <p v-else class="text-sm text-gray-500">
          Vous pourrez laisser un avis une fois la réservation terminée.
        </p>
      </div>

      <RouterLink
        to="/bookings"
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
        Retour aux voyages
      </RouterLink>
    </div>
  </section>
</template>
